<?php

namespace Kelunik\TicTacToe\Websocket;

use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use Aerys\Websocket;
use Amp\Promise;
use Kelunik\TicTacToe\Event\Publisher;
use Kelunik\TicTacToe\Event\Subscriber;
use Kelunik\TicTacToe\Storage\Counter;
use Kelunik\TicTacToe\Storage\GameStorage;
use function Amp\coroutine;
use function Amp\once;
use function Amp\resolve;

class Handler implements Websocket {
    /** @var Websocket\Endpoint */
    private $endpoint;

    /** @var string[] */
    private $origins;

    /** @var string[] */
    private $clients;

    /** @var int[][] */
    private $users;

    /** @var Subscriber */
    private $subscriber;

    /** @var Command[] */
    private $commands;

    /** @var Counter */
    private $counter;

    /** @var GameStorage */
    private $gameStorage;

    /** @var Publisher */
    private $publisher;

    public function __construct(Subscriber $subscriber, Counter $counter, GameStorage $gameStorage, Publisher $publisher, array $origins) {
        $this->subscriber = $subscriber;
        $this->counter = $counter;
        $this->gameStorage = $gameStorage;
        $this->publisher = $publisher;
        $this->origins = $origins;
        $this->clients = [];
        $this->users = [];
        $this->commands = [];
    }

    public function registerCommand(string $name, Command $command) {
        if (isset($this->commands[$name])) {
            throw new \InvalidArgumentException("Command is already registered.");
        }

        $this->commands[$name] = $command;
    }

    public function onStart(Websocket\Endpoint $endpoint) {
        $this->endpoint = $endpoint;
    }

    public function onHandshake(Request $request, Response $response) {
        $origin = $request->getHeader("origin");

        if (!in_array($origin, $this->origins, true)) {
            $response->setStatus(400);
            $response->setReason("Invalid Origin");
            $response->send("");

            return null;
        }

        /** @var Session $session */
        $session = yield (new Session($request))->read();

        if (!$session->has("user")) {
            $response->setStatus(400);
            $response->setReason("No Session");
            $response->send("");
        }

        return $session->get("user");
    }

    public function onOpen(int $clientId, $userId) {
        $newUser = !isset($this->users[$userId]);

        $this->clients[$clientId] = $userId;
        $this->users[$userId][$clientId] = $clientId;

        yield $this->counter->increment("user.{$userId}");

        if ($newUser) {
            $this->subscribeUser($userId)->when(coroutine(function ($error) use ($userId) {
                if ($error) {
                    yield $this->unsubscribeUser($userId);

                    foreach ($this->users[$userId] as $client) {
                        yield $this->endpoint->close($client);
                    }
                }
            }));
        }
    }

    public function onData(int $clientId, Websocket\Message $message) {
        $decoded = json_decode(yield $message);
        $userId = $this->clients[$clientId];

        if (!is_object($decoded)) {
            return;
        }

        foreach ($decoded as $command => $data) {
            if (isset($this->commands[$command])) {
                $promise = $this->commands[$command]->execute($userId, $data);

                /** @var Message|null $result */
                $result = yield $promise;

                if ($result) {
                    $this->endpoint->send($clientId, $result->getPayload());
                }
            }

            break;
        }
    }

    public function onClose(int $clientId, int $code, string $reason) {
        $userId = $this->clients[$clientId];

        $connectionCount = yield $this->counter->decrement("user.{$userId}");

        if ($connectionCount === 0) {
            once(function () use ($userId) {
                $count = yield $this->counter->get("user.{$userId}");

                if ($count === 0) {
                    $players = yield $this->gameStorage->findByUser($userId);

                    if ($players) {
                        yield $this->gameStorage->delete($players);
                        yield $this->publisher->publish("user.{$players[0]}", (new Message("game.abort"))->getPayload());
                        yield $this->publisher->publish("user.{$players[1]}", (new Message("game.abort"))->getPayload());
                    }
                }
            }, 5000);
        }

        unset($this->clients[$clientId]);
        unset($this->users[$userId][$clientId]);

        if (empty($this->users[$userId])) {
            unset($this->users[$userId]);

            yield $this->unsubscribeUser($userId);
        }
    }

    public function onStop() {
        // we don't need to free any resources
    }

    private function subscribeUser($userId): Promise {
        return $this->subscriber->subscribe("user.{$userId}")->watch(coroutine(function ($data) use ($userId) {
            yield $this->endpoint->send(array_values($this->users[$userId]), $data);
        }));
    }

    private function unsubscribeUser($userId): Promise {
        return $this->subscriber->unsubscribe("user.{$userId}");
    }
}
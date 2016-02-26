<?php

namespace Kelunik\TicTacToe\Websocket;

use Amp\Promise;
use Kelunik\TicTacToe\Event\Publisher;
use Kelunik\TicTacToe\GameException;
use Kelunik\TicTacToe\Storage\GameCodec;
use Kelunik\TicTacToe\Storage\GameStorage;
use function Amp\resolve;

class SetCommand implements Command {
    private $gameStorage;
    private $gameCodec;
    private $publisher;

    public function __construct(GameStorage $gameStorage, GameCodec $gameCodec, Publisher $publisher) {
        $this->gameStorage = $gameStorage;
        $this->gameCodec = $gameCodec;
        $this->publisher = $publisher;
    }

    public function execute(string $userId, $data = null): Promise {
        $fn = function () use ($userId, $data) {
            if (!is_array($data) || count($data) !== 2 || !is_int($data[0]) || !is_int($data[1])) {
                return;
            }

            try {
                $players = yield $this->gameStorage->findByUser($userId);

                if ($players === null) {
                    return;
                }

                $encodedGame = yield $this->gameStorage->load($players);
                $game = $this->gameCodec->decode($encodedGame);

                $game->set($data[0], $data[1], $userId);

                $encodedGame = $this->gameCodec->encode($game);
                yield $this->gameStorage->store($players, $encodedGame);

                yield $this->publisher->publish("user.{$players[0]}", (new Message("game.state", [
                    "fields" => $game->getFields(),
                    "turn" => $game->getNext() === $players[0],
                ]))->getPayload());

                yield $this->publisher->publish("user.{$players[1]}", (new Message("game.state", [
                    "fields" => $game->getFields(),
                    "turn" => $game->getNext() === $players[1],
                ]))->getPayload());

                if ($winner = $game->getWinner()) {
                    yield from $this->publishWinner($players, $winner);
                    yield $this->gameStorage->delete($players);
                }
            } catch (GameException $e) {
                // ignore
            }
        };

        return resolve($fn());
    }

    private function publishWinner(array $players, int $winner) {
        yield $this->publisher->publish("user." . $players[0], (new Message("game.end", $winner === -1 ? null : $winner === 1))->getPayload());
        yield $this->publisher->publish("user." . $players[1], (new Message("game.end", $winner === -1 ? null : $winner === 2))->getPayload());
    }
}
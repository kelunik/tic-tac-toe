<?php

namespace Kelunik\TicTacToe\Websocket;

use Amp\Promise;
use Kelunik\TicTacToe\Event\Publisher;
use Kelunik\TicTacToe\Game;
use Kelunik\TicTacToe\Lobby;
use Kelunik\TicTacToe\Storage\GameCodec;
use Kelunik\TicTacToe\Storage\GameStorage;
use function Amp\resolve;

class StartCommand implements Command {
    private $gameStorage;
    private $gameCodec;
    private $lobby;
    private $publisher;

    public function __construct(GameStorage $gameStorage, GameCodec $gameCodec, Lobby $lobby, Publisher $publisher) {
        $this->gameStorage = $gameStorage;
        $this->gameCodec = $gameCodec;
        $this->lobby = $lobby;
        $this->publisher = $publisher;
    }

    public function execute(string $userId, $data = null): Promise {
        $fn = function () use ($userId, $data) {
            $players = yield $this->gameStorage->findByUser($userId);

            if ($players !== null) {
                return;
            }

            $found = yield $this->lobby->findMatch($userId);

            if ($found === null) {
                return;
            }

            $players = [$found, $userId];
            sort($players);

            $next = random_int(0, 1);

            $fields = [
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0],
            ];

            $encodedGame = $this->gameCodec->encode(new Game($players, $fields, $players[$next]));
            yield $this->gameStorage->store($players, $encodedGame);

            yield $this->publisher->publish("user.{$players[0]}", (new Message("game.state", [
                "fields" => $fields,
                "turn" => $next === 0,
            ]))->getPayload());

            yield $this->publisher->publish("user.{$players[1]}", (new Message("game.state", [
                "fields" => $fields,
                "turn" => $next === 1,
            ]))->getPayload());
        };

        return resolve($fn());
    }
}
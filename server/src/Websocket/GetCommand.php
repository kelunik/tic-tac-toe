<?php

namespace Kelunik\TicTacToe\Websocket;

use Amp\Promise;
use Kelunik\TicTacToe\GameException;
use Kelunik\TicTacToe\Storage\GameCodec;
use Kelunik\TicTacToe\Storage\GameStorage;
use function Amp\resolve;

class GetCommand implements Command {
    private $gameStorage;
    private $gameCodec;

    public function __construct(GameStorage $gameStorage, GameCodec $gameCodec) {
        $this->gameStorage = $gameStorage;
        $this->gameCodec = $gameCodec;
    }

    public function execute(string $userId, $data = null): Promise {
        $fn = function () use ($userId, $data) {
            try {
                $players = yield $this->gameStorage->findByUser($userId);

                if ($players === null) {
                    return new Message("game.state", [
                        "fields" => null,
                        "turn" => false,
                    ]);
                }

                $encodedGame = yield $this->gameStorage->load($players);
                $game = $this->gameCodec->decode($encodedGame);

                return new Message("game.state", [
                    "fields" => $game->getFields(),
                    "turn" => $userId === $game->getNext(),
                ]);
            } catch (GameException $e) {
                return null;
            }
        };

        return resolve($fn());
    }
}
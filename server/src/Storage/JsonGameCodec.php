<?php

namespace Kelunik\TicTacToe\Storage;

use Kelunik\TicTacToe\Game;

class JsonGameCodec implements GameCodec {
    public function encode(Game $game): string {
        return json_encode([
            "players" => $game->getPlayers(),
            "fields" => $game->getFields(),
            "next" => $game->getNext(),
        ]);
    }

    public function decode(string $data): Game {
        $decoded = json_decode($data);

        if (!isset($decoded->players, $decoded->fields, $decoded->next)) {
            throw new \InvalidArgumentException("Invalid JSON.");
        }

        if (!is_array($decoded->players) || count($decoded->players) !== 2) {
            throw new \InvalidArgumentException("Invalid players.");
        }

        if (!is_array($decoded->fields) || count($decoded->fields) !== 3) {
            throw new \InvalidArgumentException("Invalid fields.");
        }

        if (!is_string($decoded->next)) {
            throw new \InvalidArgumentException("Invalid next.");
        }

        return new Game($decoded->players, $decoded->fields, $decoded->next);
    }
}
<?php

namespace Kelunik\TicTacToe\Storage;

use Kelunik\TicTacToe\Game;

interface GameCodec {
    public function encode(Game $game): string;

    public function decode(string $data): Game;
}
<?php

namespace Kelunik\TicTacToe\Websocket;

use Amp\Promise;

interface Command {
    public function execute(string $userId, $data = null): Promise;
}
<?php

namespace Kelunik\TicTacToe\Event;

interface Publisher {
    public function publish(string $event, string $data = "");
}
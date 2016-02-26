<?php

namespace Kelunik\TicTacToe\Event;

use Amp\Promise;

interface Subscriber {
    public function subscribe(string $channel): Promise;
    public function unsubscribe(string $channel): Promise;
}
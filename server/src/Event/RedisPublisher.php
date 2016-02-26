<?php

namespace Kelunik\TicTacToe\Event;

use Amp\Redis\Client;
use Amp\Redis\RedisException;
use function Amp\resolve;

class RedisPublisher implements Publisher {
    private $redis;

    public function __construct(Client $redis) {
        $this->redis = $redis;
    }

    public function publish(string $event, string $data = "") {
        $fn = function () use ($event, $data) {
            try {
                yield $this->redis->publish($event, $data);
            } catch (RedisException $e) {
                throw new EventException("Couldn't publish event.", 0, $e);
            }
        };

        return resolve($fn());
    }
}
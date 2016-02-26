<?php

namespace Kelunik\TicTacToe\Event;

use Amp\Deferred;
use Amp\Promise;
use Amp\Redis\RedisException;
use Amp\Redis\SubscribeClient;
use function Amp\resolve;

class RedisSubscriber implements Subscriber {
    private $redis;

    public function __construct(SubscribeClient $redis) {
        $this->redis = $redis;
    }

    public function subscribe(string $channel): Promise {
        $promisor = new Deferred;

        $promise = $this->redis->subscribe($channel);
        $promise->watch(function ($event) use ($promisor) {
            $promisor->update($event);
        });

        $promise->when(function ($error, $result) use ($promisor) {
            if ($error) {
                $promisor->fail(new EventException("Subscription failed.", 0, $e));
            } else {
                $promisor->succeed($result);
            }
        });

        return $promisor->promise();
    }

    public function unsubscribe(string $channel): Promise {
        $fn = function () use ($channel) {
            try {
                yield $this->redis->unsubscribe($channel);
            } catch (RedisException $e) {
                throw new EventException("Couldn't unsubscribe from channel.", 0, $e);
            }
        };

        return resolve($fn());
    }
}
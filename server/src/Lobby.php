<?php

namespace Kelunik\TicTacToe;

use Amp\Promise;
use Amp\Redis\Client;
use function Amp\resolve;

class Lobby {
    const SCRIPT_FIND_MATCH = <<<SCRIPT
local current = redis.call('get',KEYS[1])

if current == ARGV[1] then
    return nil
end

if not current then
	redis.call('set',KEYS[1],ARGV[1])
	return nil
else
	redis.call('del',KEYS[1])
	return current
end
SCRIPT;

    const SCRIPT_CLEAR_MATCH = <<<SCRIPT
local current = redis.call('get',KEYS[1])

if current == ARGV[1] then
	redis.call('del',KEYS[1],ARGV[1])
	return 1
else
	return 0
end
SCRIPT;

    const LOBBY_KEY = "lobby";

    private $redis;

    public function __construct(Client $redis) {
        $this->redis = $redis;
    }

    public function findMatch(string $userId): Promise {
        $fn = function () use ($userId) {
            $response = yield $this->redis->eval(self::SCRIPT_FIND_MATCH, [self::LOBBY_KEY], [$userId]);

            return $response;
        };

        return resolve($fn());
    }

    public function clearMatch(string $userId): Promise {
        $fn = function () use ($userId) {
            $response = yield $this->redis->eval(self::SCRIPT_CLEAR_MATCH, [self::LOBBY_KEY], [$userId]);

            return $response;
        };

        return resolve($fn());
    }
}
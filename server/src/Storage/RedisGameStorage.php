<?php

namespace Kelunik\TicTacToe\Storage;

use Amp\Promise;
use Amp\Redis\Client;
use Amp\Redis\RedisException;
use function Amp\resolve;

class RedisGameStorage implements GameStorage {
    private $redis;

    public function __construct(Client $redis) {
        $this->redis = $redis;
    }

    public function findByUser(string $id): Promise {
        $fn = function () use ($id) {
            try {
                $other = yield $this->redis->get("match.{$id}");

                if (empty($other)) {
                    return null;
                }

                $players = [$id, $other];
                sort($players);

                return $players;
            } catch (RedisException $e) {
                return new StorageException("Couldn't query game by userId.");
            }
        };

        return resolve($fn());
    }

    public function load(array $players): Promise {
        $fn = function () use ($players) {
            try {
                $data = yield $this->redis->get("game." . implode(":", $players));
            } catch (RedisException $e) {
                throw new StorageException("Couldn't load game.", 0, $e);
            }

            if (empty($data)) {
                throw new StorageException("Game not found.");
            }

            return $data;
        };

        return resolve($fn());
    }

    public function store(array $players, string $data): Promise {
        $fn = function () use ($players, $data) {
            try {
                yield $this->redis->set("match.{$players[0]}", $players[1]);
                yield $this->redis->set("match.{$players[1]}", $players[0]);
                yield $this->redis->set("game." . implode(":", $players), $data);
            } catch (RedisException $e) {
                throw new StorageException("Couldn't store game.", 0, $e);
            }
        };

        return resolve($fn());
    }

    public function delete(array $players): Promise {
        $fn = function () use ($players) {
            try {
                yield $this->redis->del("match.{$players[0]}", $players[1]);
                yield $this->redis->del("match.{$players[1]}", $players[0]);
                yield $this->redis->del("game." . implode(":", $players));
            } catch (RedisException $e) {
                throw new StorageException("Couldn't delete game.", 0, $e);
            }
        };

        return resolve($fn());
    }
}
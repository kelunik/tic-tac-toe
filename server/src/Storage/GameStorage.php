<?php

namespace Kelunik\TicTacToe\Storage;

use Amp\Promise;

interface GameStorage {
    public function findByUser(string $id): Promise;

    public function load(array $players): Promise;

    public function store(array $players, string $data): Promise;

    public function delete(array $players): Promise;
}
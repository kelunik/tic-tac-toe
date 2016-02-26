<?php

namespace Kelunik\TicTacToe;

class Game {
    private $fields;
    private $players;
    private $next;

    public function __construct(array $players, array $fields, string $next) {
        $this->players = array_values($players);
        $this->fields = $fields;
        $this->next = $next;

        sort($this->players);
    }

    public function set($x, $y, $player) {
        if ($x < 0 || $x > 2) {
            throw new GameException('$x must be 0, 1, or 2.');
        }

        if ($y < 0 || $y > 2) {
            throw new GameException('$y must be 0, 1, or 2.');
        }

        if (!in_array($player, $this->players, true)) {
            throw new GameException('$player is unknown.');
        }

        if ($this->fields[$y][$x] > 0) {
            throw new GameException('$x, $y is already taken.');
        }

        if ($player !== $this->next) {
            throw new GameException('it\'s not $player\'s turn.');
        }

        if ($this->getWinner()) {
            throw new GameException('game already finished.');
        }

        $id = array_search($player, $this->players);
        $this->fields[$y][$x] = $id + 1;
        $this->next = $this->players[1 - $id];
    }

    public function getFields(): array {
        return $this->fields;
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function getNext(): string {
        return $this->next;
    }

    public function getWinner(): int {
        for ($y = 0; $y < 3; $y++) {
            $player = $this->fields[$y][0] & $this->fields[$y][1] & $this->fields[$y][2];

            if ($player === 1 || $player === 2) {
                return $player;
            }
        }

        for ($x = 0; $x < 3; $x++) {
            $player = $this->fields[0][$x] & $this->fields[1][$x] & $this->fields[2][$x];

            if ($player === 1 || $player === 2) {
                return $player;
            }
        }

        $player = $this->fields[0][0] & $this->fields[1][1] & $this->fields[2][2];

        if ($player === 1 || $player === 2) {
            return $player;
        }

        $player = $this->fields[0][2] & $this->fields[1][1] & $this->fields[2][0];

        if ($player === 1 || $player === 2) {
            return $player;
        }

        for ($y = 0; $y < 3; $y++) {
            if (!$this->fields[$y][0] || !$this->fields[$y][1] || !$this->fields[$y][2]) {
                return 0;
            }
        }

        return -1; // NONE, TIE
    }
}
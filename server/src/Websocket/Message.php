<?php

namespace Kelunik\TicTacToe\Websocket;

class Message {
    private $payload;

    public function __construct(string $type, $data = null) {
        $this->payload = json_encode([
            "type" => $type,
            "data" => $data,
        ]);
    }

    public function getPayload(): string {
        return $this->payload;
    }
}
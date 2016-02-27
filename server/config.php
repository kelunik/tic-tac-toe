<?php

use Aerys\Host;
use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use Amp\Redis\Client;
use Amp\Redis\Mutex;
use Amp\Redis\SubscribeClient;
use Auryn\Injector;
use Kelunik\TicTacToe\Event\Publisher;
use Kelunik\TicTacToe\Event\RedisPublisher;
use Kelunik\TicTacToe\Event\RedisSubscriber;
use Kelunik\TicTacToe\Event\Subscriber;
use Kelunik\TicTacToe\Storage\Counter;
use Kelunik\TicTacToe\Storage\GameCodec;
use Kelunik\TicTacToe\Storage\GameStorage;
use Kelunik\TicTacToe\Storage\JsonGameCodec;
use Kelunik\TicTacToe\Storage\RedisCounter;
use Kelunik\TicTacToe\Storage\RedisGameStorage;
use Kelunik\TicTacToe\Websocket\GetCommand;
use Kelunik\TicTacToe\Websocket\Handler;
use Kelunik\TicTacToe\Websocket\SetCommand;
use Kelunik\TicTacToe\Websocket\StartCommand;
use function Aerys\root;
use function Aerys\router;
use function Aerys\websocket;

$injector = new Injector;

$injector->alias(GameCodec::class, JsonGameCodec::class);
$injector->alias(GameStorage::class, RedisGameStorage::class);
$injector->alias(Publisher::class, RedisPublisher::class);
$injector->alias(Subscriber::class, RedisSubscriber::class);
$injector->alias(Counter::class, RedisCounter::class);

$injector->define(Mutex::class, [":uri" => "tcp://localhost:6379", ":options" => []]);
$injector->define(Client::class, [":uri" => "tcp://localhost:6379"]);
$injector->define(SubscribeClient::class, [":uri" => "tcp://localhost:6379"]);
$injector->define(Handler::class, [":origins" => [
    "http://localhost:8765",
    "http://localhost:3000",
]]);

$websocket = $injector->make(Handler::class);
$websocket->registerCommand("set", $injector->make(SetCommand::class));
$websocket->registerCommand("get", $injector->make(GetCommand::class));
$websocket->registerCommand("start", $injector->make(StartCommand::class));

$router = router()
    ->route("GET", "/", function (Request $request, Response $response) {
        /** @var Session $session */
        $session = yield (new Session($request))->open();

        if (!$session->has("user")) {
            $user = bin2hex(random_bytes(32));
            $session->set("user", $user);
        }

        yield $session->save();

        // actual response will be sent by the webroot handler
    })
    ->route("GET", "/ws", websocket($websocket))
    ->route("GET", "/index.html", function (Request $request, Response $response) {
        // redirect to avoid duplicate content and to ensure clean URLs
        $response->setStatus(301);
        $response->setHeader("location", "/");
        $response->send("");
    });

$root = root(__DIR__ . "/../public");

(new Host)
    ->expose("127.0.0.1", 8765)
    ->use(Aerys\session(["driver" => $injector->make(Session\Redis::class)]))
    ->use($router)
    ->use($root);
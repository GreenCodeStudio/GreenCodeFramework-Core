<?php

namespace Core\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class Server implements MessageComponentInterface
{
    private $conncections = [];

    public static function init()
    {
        $ws = new WsServer(new self());

        // Make sure you're running this as root
        $server = IoServer::factory(new HttpServer($ws), getenv('websocketPort'));
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "onOpen\r\n";
        if ($conn->remoteAddress == "127.0.0.1" && $conn->httpRequest->getUri()->getPath() == "/server") {//first is for security, second is to allow connect client on localhost
            $conn->extended = new ConnectionServer();
        } else {
            $conn->extended = new ConnectionUser();
        }
        $this->conncections[] = $conn;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "onMessage\r\n";
        if ($from->extended instanceof ConnectionServer) {
            $this->distributeMessage($msg);
        }
        var_dump($msg);
        print_r(count($this->conncections));
    }

    private function distributeMessage(string $msg)
    {
        $msgParsed=json_decode($msg, true);
        $sendMsg = json_encode(['path' => $msgParsed['path'], 'message' => $msgParsed['message']]);
        $connections = array_filter($this->conncections, function ($x) {
            return $x->extended instanceof ConnectionUser;
        });
        if($msgParsed['users']!==null){
          //todo
        }
        foreach ($connections as $conn) {
            $conn->send($sendMsg);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "onClose\r\n";
        array_splice($this->conncections, array_search($conn, $this->conncections), 1);

        print_r(count($this->conncections));
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "onError\r\n";
    }
}
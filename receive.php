<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$queue = 'test';

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
$callback = function ($msg) {
    echo " [x] Received ", $msg->body;

    // Negative acknowlegde message with body 10 so it's getting redelivered, acknowledge the rest
    if ($msg->body == 10) {
        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, false);
        echo " - NACK \n";
    } else {
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        echo " - ACK \n";
    }
};

$channel->basic_consume($queue, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

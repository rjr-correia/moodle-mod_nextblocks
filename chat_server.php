<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package     mod_nextblocks
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 *  Based on https://github.com/iabhinavr/php-socket-chat
 *  and RFC 6455: https://datatracker.ietf.org/doc/html/rfc6455
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class Chat_Server {

    /** @var string Address to the chat server */
    private $address;  
    /** @var int port to the chat server */
    private $port;
    /** @var null null representation */
    private $null;

    /**
     * Constructs the chat server with default parameters
     */
    function __construct() {
        $this->address = "0.0.0.0";
        $this->port = 8060;
        $this->null = null;
    }

    /**
     * Initializes the chat server.
     *
     * This method sets up a WebSocket server that listens for incoming connections and messages.
     * It runs in an infinite loop, so it will keep the server running indefinitely.
     * If the server encounters an error (e.g., if socket_select() returns false), it will restart the server.
     */
    public function init_chat_server() {
        // Outer loop to restart the server if there is an error.
        while (true) {
            // Create new TCP socket.
            $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            // Bind socket to specified address and port.
            socket_bind($sock, $this->address, $this->port);
            // Listen for incoming connections on socket.
            socket_listen($sock);

            // Holds the main socket and any client sockets.
            $connections = [$sock];

            // Inner loop to handle incoming connections and messages.
            while (true) {
                // Declaring arrays needed for socket_select().
                $reads = $connections;
                $writes = $exceptions = $this->null;

                // Wait for change in status of any socket in the $reads array.
                $changedsockets = socket_select($reads, $writes, $exceptions, 0);

                // If socket_select() returns false, an error occurred.
                if ($changedsockets === false) {
                    // Print the error message and exit the inner loop to restart the server.
                    echo "socket_select() failed, reason: " . socket_strerror(socket_last_error()) . "\n";
                    break;
                }

                // If the main socket is ready to read, a new connection is incoming.
                if (in_array($sock, $reads)) {
                    // Accept new connection.
                    $newconnection = socket_accept($sock);
                    // Read initial request from client.
                    $header = socket_read($newconnection, 1024);
                    // Perform WebSocket handshake.
                    $this->handshake($header, $newconnection);

                    // Add new connection to connection array.
                    $connections[] = $newconnection;

                    // Remove main socket from the $reads array for this iteration.
                    $sockindex = array_search($sock, $reads);
                    unset($reads[$sockindex]);
                }

                // Handle incoming messages from clients.
                foreach ($reads as $key => $value) {
                    // Read message from the client.
                    $data = @socket_read($value, 1024);

                    // Check if socket_read() failed.
                    if ($data === false) {
                        $errorcode = socket_last_error();
                        $errormessage = socket_strerror($errorcode);

                        echo "socket_read() failed: [$errorcode] $errormessage\n";

                        // Handle error (e.g., disconnect client, log error, etc.).
                        echo "disconnecting client $key due to error\n";
                        unset($connections[$key]);
                        socket_close($value);
                        continue;
                    }

                    // If client sent a message.
                    if (!empty($data)) {
                        // Unmask message.
                        $message = $this->unmask($data);

                        // Skip invalid JSON. when the client disconnects sometimes a bit of junk is sent,
                        // which is not valid JSON.
                        json_decode($message);
                        if (json_last_error() === JSON_ERROR_SYNTAX) {
                            continue;
                        }

                        // Pack message for sending.
                        $packedmessage = $this->pack_data($message);

                        // Send message to all connected clients.
                        foreach ($connections as $ckey => $cvalue) {
                            // Skip main socket
                            if ($ckey === 0) {
                                continue;
                            }
                            socket_write($cvalue, $packedmessage, strlen($packedmessage));
                        }
                    } else if ($data === '') { // If client closed the connection.
                        echo "disconnecting client $key\n";
                        unset($connections[$key]);
                        socket_close($value);
                    }
                }
            }

            // Close main socket before restarting server.
            socket_close($sock);
            echo "Restarting server...\n";
        }
    }

    /**
     * Unmasks the text sent in a message
     *
     * @param $text string masked text
     * @return string unmasked text
     */
    private function unmask($text): string {
        if (strlen($text) < 2) {
            // Handle error: $text is too short.
            return "";
        }
        $length = @ord($text[1]) & 127; // Converts 8-bit to 7-bit, because payload length is 7-bit.
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } else if ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";

        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }

        // Check if the unmasked data is valid UTF-8.
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Handle error: $cdtext is not valid UTF-8.
            return "";
        }

        return $text;
    }

    /**
     * Packs the data from a message
     *
     * @param $text string message
     * @return string data
     */
    private function pack_data($text): string {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } else if ($length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }

        return $header.$text;
    }

    /**
     * Handshakes a connection in a socket
     *
     * @param $requestheader string information about the request
     * @param $sock Socket socket
     */
    private function handshake($requestheader, $sock) {
        $headers = [];
        $lines = preg_split("/\r\n/", $requestheader);

        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        if (!isset($headers['Sec-WebSocket-Key'])) {
            // Handle error: 'Sec-WebSocket-Key' header not present.
            $responseheader = "HTTP/1.1 400 Bad Request\r\n\r\n";
            socket_write($sock, $responseheader, strlen($responseheader));
            return;
        }

        $seckey = $headers['Sec-WebSocket-Key'];
        $secaccept = base64_encode(pack('H*', sha1($seckey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $responseheader = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $secaccept\r\n\r\n";

        socket_write($sock, $responseheader, strlen($responseheader));
    }
}

$chat = new Chat_Server();
$chat->init_chat_server();

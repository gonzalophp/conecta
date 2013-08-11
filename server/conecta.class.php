<?php
namespace conecta;

class server {
    CONST CONECTA_PORT=55555;
    public $rServerSocket;
    public $nPort;
    public $aClients;

    public function __construct(){

    }

    public function startServer(){
        $sAddress = "tcp://0.0.0.0:55555";
        $this->rServerSocket = stream_socket_server($sAddress, $errno, $errstr);
        stream_set_blocking($this->rServerSocket,0);
        $this->aClients = array('streams'   => array($this->rServerSocket)
                              , 'address'   => array($sAddress));
    }

    public function handShake($rStream){
        echo "Handshake - 1\n";

        $sHeaders = $this->read($rStream);
        echo "Received\n$sHeaders\n";

        $aHeaderLines = explode("\r\n",$sHeaders);

        $aHeaders = array();
        foreach($aHeaderLines as $sHeaderLine){
            $nColonPosition = strpos($sHeaderLine,':');
            $aHeaders[trim(substr($sHeaderLine,0,$nColonPosition))] = trim(substr($sHeaderLine,$nColonPosition+1));
        }

        echo "Handshake - 2\n";

        $keyPlusMagic = $aHeaders['Sec-WebSocket-Key'] . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $shaAcceptKey = sha1($keyPlusMagic, true);
        $socketAccept = base64_encode($shaAcceptKey);

        if(isset($aHeaders['Origin'])) $origin = $aHeaders['Origin'];
        if(isset($aHeaders["Sec-WebSocket-Origin"])) $origin = $aHeaders["Sec-WebSocket-Origin"];

        $upgrade  = "HTTP/1.1 101 Switching Protocols\r\n" .
                    "Upgrade: WebSocket\r\n" .
                    "Connection: Upgrade\r\n" .
                    "Sec-WebSocket-Version: 8\r\n";

        if(isset($origin)) {
            $upgrade .= "Sec-WebSocket-Origin: " . $origin . "\r\n";
        }

        $upgrade = $upgrade."Sec-WebSocket-Accept: " . $socketAccept . "\r\n\r\n";

        fwrite($rStream, $upgrade);

        echo "Handshake - 3\n";
    }

    function encode($text){
        // 0x1 text frame (FIN + opcode)
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif($length > 125 && $length < 65536)
            $header = pack('CCS', $b1, 126, $length);
        elseif($length >= 65536)
            $header = pack('CCN', $b1, 127, $length);

        return $header.$text;
    }


    

    function decode ($msg) {
        $aDecoded = array();

        do{
            $data = $decoded = $index = null;
            $len = ord($msg[1]) & 127;

            if ($len === 126) { // 65536 - 2,147,486,647 bytes
                $initFrame = substr($msg, 0, 4);
                $len = unpack('S',substr($msg, 2, 2));
                $masks = substr ($msg, 4, 4);
                $data = substr ($msg, 8);
            }
            else if ($len === 127) { // 128-65,535 bytes
                $initFrame = substr($msg, 0, 10);
                $len = unpack('N',substr($msg, 2, 4));
                $masks = substr ($msg, 10, 4);
                $data = substr ($msg, 14);
            }
            else { // < 0-127 bytes
                $initFrame = substr($msg, 0, 2);
                $masks = substr ($msg, 2, 4);
                $data = substr ($msg, 6);
            }

            for ($index = 0; $index < $len; $index++) {
                $decoded .= $data[$index] ^ $masks[$index % 4];
            }

            $aDecoded[] = $decoded;

            $msg = substr($msg,strlen($initFrame)+4+$len);

        }while(strlen($msg));

        return $aDecoded;
    }


    public function clientRegister(){
        $rStream = stream_socket_accept($this->rServerSocket,0,$sAddress);
        stream_set_chunk_size($rStream, 35000);
        $this->aClients['streams'][] = $rStream;
        $this->aClients['address'][] = $sAddress;

        echo "New Client ($sAddress). Clients connected: ".$this->clientCount()."\n";

        $this->handShake($rStream);
    }

    public function clientRemove($rStream){
        $index = $this->clientIndex($rStream);
        unset($this->aClients['streams'][$index]);
        unset($this->aClients['address'][$index]);
    }

    public function clientCount(){
        return count($this->aClients['streams'])-1;
    }

    public function clientAddress($rStream){
        return $this->aClients['address'][$this->clientIndex($rStream)];
    }

    public function clientIndex($rStream){
        return array_search($rStream, $this->aClients['streams']);
    }

    public function read($rSocket){
        $sReceived = fread($rSocket,25000);

//        echo "Received {$this->clientAddress($rSocket)} - (length): ".strlen($sReceived)." -- \\0: ". strpos($sReceived,"\0")."-- ".print_r(feof($rSocket),false);
//        echo "Received \n$sReceived\n";


        return $sReceived;
    }

    public function write($rSocket){

    }

    public function daemon(){
        echo "Server daemon - 1\n";
        $this->startServer();
        while (true) {
            $read = $this->aClients['streams'];
            usleep(1000300);
            if (stream_select($read, $write = NULL, $except = NULL, 0) < 1) continue;

            if (in_array($this->rServerSocket, $read)) {
//                $newClient = stream_socket_accept($this->rServerSocket,0,$sSocketName);

                $this->clientRegister();
//                $sNewClientMessage = "New Client ({$this->clientAddress($newClient)}). Clients connected: ".$this->clientCount()."\n";

//                $this->read($newClient);
//                fwrite($newClient, $sNewClientMessage."\0");

//                echo $sNewClientMessage;
                unset($read[array_search($this->rServerSocket, $read)]);
            }

            foreach ($read as $read_sock) {
                $data = $this->read($read_sock);
                if (feof($read_sock)) {
                    $sAddress = $this->clientAddress($read_sock);
                    $this->clientRemove($read_sock);
                    echo "client disconnected ($sAddress). Clients connected:".$this->clientCount()."\n";

                    continue;
                }
                if (!empty($data)) {
//                    echo "Received===".$this->decode($data)."===\n";
//                    echo "Received decrypt===".$this->decrypt($data)."===\n";
//                    echo "Received decode===".$this->decode($data)."===\n";
                    $aDecoded = $this->decode($data);
                    var_dump($aDecoded);
                    for($i=rand(1,4);$i>0;$i--){
                        if (@fwrite($read_sock, $this->encode("RESPUESTA"))===false){
                            $this->clientRemove($read_sock);
                        }
                    }

                }
            }
        }
        fclose($this->rServerSocket);
        echo "Server daemon - 2\n";
    }


    public function run(){
        global $argv;
        var_dump($argv);
        $this->daemon($argv[3]);
    }
}

$oConectaServer = new \conecta\server();
$oConectaServer->run();
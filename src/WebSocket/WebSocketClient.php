<?php

namespace Pxlswrite\WebSocket;

class WebSocketClient
{
    protected $client;
    const SOCKET_HOST = 'ws://127.0.0.1:9502';
    public $m_receiverFd = null;//消息接收者客户端id

    /**
     * WebSocketClient constructor.
     * @param null $_url websocket服务器地址
     * @param int|null $_receiverFd 消息接收者客户端ID
     */
    public function __construct($_url = null, int $_receiverFd = null)
    {
        $this->m_receiverFd = $_receiverFd;
        $host = $_url ? $_url : self::SOCKET_HOST;
        $this->client = new \WebSocket\Client($host); //实例化
    }

//    public static function getInstance($_url = null)
//    {
//        if (!(self::$m_instance instanceof self)) {
//            $host = $_url ? $_url : self::SOCKET_HOST;
//            self::$m_instance = new static($host);
//        }
//        return self::$m_instance;
//    }

    public function send($_data)
    {
        if ($this->m_receiverFd) {
            $_data = array_merge($_data, ['fd' => $this->m_receiverFd]);
        }
        return $this->client->send(json_encode($_data)); //发送数据
    }

    public function __destruct()
    {
        return $this->client->close();//关闭连接
    }

    public function __call($_name, $_arguments)
    {
        return $this->client->{$_name}(...$_arguments);
    }
}
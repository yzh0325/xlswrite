<?php

namespace Pxlswrite\WebSocket;

class WebSocketServer
{
    CONST HOST = "0.0.0.0";
    CONST PORT = 9502;

    public $ws = null;

    public function __construct()
    {
        $this->ws = new Swoole\Websocket\Server(self::HOST, self::PORT);
        // $this->ws->listen(self::HOST, self::CHART_PORT, SWOOLE_SOCK_TCP);
//        $this->ws->set(
//            [
//                'enable_static_handler' => true,
//                'document_root' => "/www/wwwroot/192.168.18.192/static",
//                'worker_num' => 4,
//                'task_worker_num' => 4,
//                'enable_coroutine' =>  true,
//            ]
//        );

        $this->ws->on("start", [$this, 'onStart']);
        $this->ws->on("open", [$this, 'onOpen']);
        $this->ws->on("message", [$this, 'onMessage']);
        $this->ws->on("workerstart", [$this, 'onWorkerStart']);
//        $this->ws->on("request", [$this, 'onRequest']);
        $this->ws->on("task", [$this, 'onTask']);
        $this->ws->on("finish", [$this, 'onFinish']);
        $this->ws->on("close", [$this, 'onClose']);

        $this->ws->start();
    }

    /**
     * @param $server
     */
    public function onStart($server)
    {
        swoole_set_process_name("websocket_server");
    }

    /**
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStart($server, $worker_id)
    {
        // 定义应用目录
        define('APP_PATH', __DIR__);
    }

    /**
     * request回调
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response)
    {
        $response->header('Access-Control-Allow-Origin', '*');
        if ($request->server['request_uri'] == '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }
        $_SERVER = [];
        if (isset($request->server)) {
            foreach ($request->server as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        if (isset($request->header)) {
            foreach ($request->header as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }

        $_GET = [];
        if (isset($request->get)) {
            foreach ($request->get as $k => $v) {
                $_GET[$k] = $v;
            }
        }
        $_FILES = [];
        if (isset($request->files)) {
            foreach ($request->files as $k => $v) {
                $_FILES[$k] = $v;
            }
        }
        $_POST = [];
        if (isset($request->post)) {
            foreach ($request->post as $k => $v) {
                $_POST[$k] = $v;
            }
        }

        $this->writeLog();
        $_POST['http_server'] = $this->ws;

        ob_start();

        list($controller, $action) = explode('/', trim($request->server['request_uri'], '/'));
        $controller = ucfirst($controller);
        $action = $action ? $action : 'index';
        require_once($controller . '.php');
        (new $controller)->$action($request, $response);

        $res = ob_get_contents();
        ob_end_clean();
        $response->end($res);
    }

    /**
     * @param $serv
     * @param $taskId
     * @param $workerId
     * @param $data
     * @return
     */
    public function onTask($serv, $taskId, $workerId, $data)
    {

        // 分发 task 任务机制，让不同的任务 走不同的逻辑
        $obj = new app\common\lib\task\Task;

        $method = $data['method'];
        $flag = $obj->$method($data['data'], $serv);
        /*$obj = new app\common\lib\ali\Sms();
        try {
            $response = $obj::sendSms($data['phone'], $data['code']);
        }catch (\Exception $e) {
            // todo
            echo $e->getMessage();
        }*/

        return $flag; // 告诉worker
    }

    /**
     * @param $serv
     * @param $taskId
     * @param $data
     */
    public function onFinish($serv, $taskId, $data)
    {
        echo "taskId:{$taskId}\n";
        echo "finish-data-sucess:{$data}\n";
    }

    /**
     * 监听ws连接事件
     * @param $ws
     * @param $request
     */
    public function onOpen($ws, $request)
    {
        // \app\common\lib\redis\Predis::getInstance()->sAdd(config('redis.live_game_key'), $request->fd);
        //var_dump($request->fd, $request->get, $request->server);
        $ws->push($request->fd, json_encode(['status' => 'onopen', 'fd' => $request->fd]));
    }

    /**
     * 监听ws消息事件
     * @param $ws
     * @param $frame
     */
    public function onMessage($ws, $frame)
    {
        echo "Message: {$frame->data}\n";
        $data = json_decode($frame->data, 'true');
        if (isset($data['fd']) && $data['fd']) {
            if ($ws->isEstablished($data['fd'])) {//判断是否是正确的websocket连接，否则有可能会push失败
                $ws->push($data['fd'], $frame->data);
            }
        }
    }

    /**
     * close
     * @param $ws
     * @param $fd
     */
    public function onClose($ws, $fd)
    {
        // \app\common\lib\redis\Predis::getInstance()->sRem(config('redis.live_game_key'), $fd);
        echo "closed-clientid:{$fd}\n";
    }

    /**
     * 记录日志
     */
    public function writeLog()
    {
        $datas = array_merge(['date' => date("Ymd H:i:s")], $_GET, $_POST, $_SERVER);

        $logs = "";
        foreach ($datas as $key => $value) {
            $logs .= $key . ":" . $value . " ";
        }
        $dir = APP_PATH . '/log/' . date("Ym") . "/";
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        file_put_contents($dir . date("d") . "_access.log", $logs . PHP_EOL, FILE_APPEND);
        // swoole_async_writefile(APP_PATH.'../runtime/log/'.date("Ym")."/".date("d")."_access.log", $logs.PHP_EOL, function($filename){
        //     // todo
        // }, FILE_APPEND);
    }
}

new WebSocketServer();

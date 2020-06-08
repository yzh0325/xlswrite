<?php
/**
 * 游标读取Excel
 * 分段插入数据库
 */
require_once(__DIR__.'/../vendor/autoload.php');

use Pxlswrite\DB\DB;
use Pxlswrite\Pxlswrite;
use Pxlswrite\WebSocket\WebSocketClient;

$time = time();

$fileObj = new Pxlswrite(['path' => dirname($_GET['file'])]);
$fileInfo = explode('/', $_GET['file']);
$fileName = end($fileInfo);
//实例化WebSocketClient
$pushHandle = new WebSocketClient('ws://192.168.18.192:9502',$_GET['fd']);
//打开excel文件  setSkipRows(1)跳过一行数据
$fileObj->openFile($fileName)->openSheet()->setSkipRows(1)->importData('insert_data',$pushHandle);

//数据插入逻辑
function insert_data($data)
{
    $db = DB::getInstance();
    $sql = '';
    foreach ($data as $v) {
        $sql .= "(" . implode(",", $v) . "),";
    }
    $sql = trim($sql, ',');
    $db->execute("insert into sheet2 (id,c1,c2,c3,c4) values " . $sql);
}


$memory = floor((memory_get_peak_usage()) / 1024 / 1024) . "MB";#10M 12S
$execute_time = time() - $time . 's';
echo json_encode(['code' => 1, 'data' => ['memory' => $memory, 'execute_time' => $execute_time], 'msg' => '导入完毕']);
#5MB 57s
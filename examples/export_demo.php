<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Pxlswrite\DB\DB;
use Pxlswrite\Pxlswrite;
use Pxlswrite\WebSocket\WebSocketClient;

$time = time();


//实例化pxlswrite
$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);
//实例化WebSocketClient--需要推送进度才实例化
$pushHandle = new WebSocketClient('ws://192.168.18.192:9502', $_GET['fd']);
//创建excel文件
$fileObj->fileName('1234.xlsx');
//定义样式
$style = [
    'align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER],//对齐 [x,y]
    'border' => Pxlswrite::BORDER_SLANT_DASH_DOT,//单元格边框
    'background' => Pxlswrite::COLOR_RED,//单元格背景色
    'fontColor' => Pxlswrite::COLOR_BLUE,//字体颜色
    'fontSize' => 30,//字体大小
    'font' => 'FontName',//设置字体 字体名称，字体必须存在于本机
    'number' => '#,##0',//数字格式化
    'bold' => true,//粗题
    'strikeout' => false,//文本删除线
    'wrap' => true,//文本换行
    'italic' => true,//斜体
];
//定义字段
$field = [
    'id' => ['name' => 'title'],
    'c1' => ['name' => 'age'],
    'c2' => ['name' => 'year'],
    'c3' => ['name' => 'kk'],
    'c4' => ['name' => 'll'],
    'c5' => ['name' => 'aa', 'callback' => 'myFormat']//callback 回调处理格式化值 可以是函数/对象方法
];
$orderField =  [
    'order'=>['name'=>'订单号'] ,
    'time'=>['name'=>'下单时间'],
    'username'=>['name'=>'用户名'],
    'address'=>['name'=>'地址'],
    'phone'=>['name'=>'手机号'],
    'itemnumer'=>['name'=>'子订单号'],
    'productname'=>['name'=>'商品名称'],
    'amount'=>['name'=>'数量'],
    'mark'=>['name'=>'备注'],
];

//注意:设置行与行/列与列样式 交集范围会覆盖；行样式优先于列样式
/**
 * 一般数据导出demo
 */
//$filePath = $fileObj->field($field)//设置字段&表格头
//    ->setDataByGenerator('generateData', ['c1', 'c2'], ['align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]], $pushHandle)//设置数据 回调生成器方法获取数据，$pushHandle 用于推送，可不传
//    ->setRow('A1:A3', 80, $style)//设置范围行样式 80行高
//    ->setColumn('A:F', 20, ['background' => Pxlswrite::COLOR_GRAY, 'align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//设置范围列样式 20列宽
//    ->setRow('A1', 50, ['background' => Pxlswrite::COLOR_PINK, 'align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//设置指定某一行样式
//    ->setColumn('F:F', 60, ['background' => Pxlswrite::COLOR_YELLOW])//指定某一列样式
//    ->mergeCells('A1:C1', 'Merge cells', ['align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//合并单元格
//    ->defaultFormat(['align'=>[Pxlswrite::FORMAT_ALIGN_CENTER,Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//全局默认样式
//    ->output();//输出excel文件到磁盘

/**
 * 订单数据导出demo
// */
//$filePath = $fileObj
//    ->defaultFormat(['align'=>[Pxlswrite::FORMAT_ALIGN_CENTER,Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//全局默认样式
//    ->field($orderField)//设置字段&表格头
//    ->setOrderData('generateOrderData',['order','time'],[],$pushHandle)
//    ->setColumn('A:B',50)
//    ->setColumn('F:F',50)
//    ->output();//输出excel文件到磁盘

//单元格插入文本
//for ($index = 0; $index < 10; $index++) {
//    $fileObj->insertText($index, 0, 'viest');
//    $fileObj->insertText($index, 1, 10000, '#,##0');
//}
//$filePath = $fileObj->output();

$memory = floor((memory_get_peak_usage()) / 1024 / 1024) . "MB";#10M 22S
$execute_time = time() - $time . 's';

//同步下载
//$fileObj->download($filePath);
//ajax请求返回下载地址
echo json_encode(['code' => 1, 'msg' => '导出完毕', 'url' => '/download.php?file=' . $filePath, 'data' => ['memory' => $memory, 'excute_time' => $execute_time]]);

//数据生成器--封装模拟数据获取的方法
function generateData()
{
    $db = DB::getInstance();
    $step = 10000;
    for ($i = 0; $i < 10000; $i = $i + $step) {
        yield $db->get_records_sql("select * from sheet1 limit {$i},{$step}", null, PDO::FETCH_ASSOC);
    }
}

//格式化字段值
function myFormat($v, $values)
{
    return $v . '自定义格式化-' . $values['id'];
}

//数据生成器--获取订单数据，数据格式如下，字段数量名称不限，只支持一个item数组
//$data = [
//    [
//        'order'=>'20200632555' ,
//        'time'=>'2020-06-30 12:30:10',
//        'username'=>'张三',
//        'address'=>'成都',
//        'phone'=>'17756891562',
//        'item'=> [
//            [
//                'itemnumer'=>'2020515',
//                'productname'=>'商品1',
//                'mark'=>'备注',
//            ],
//        ],
//    ]
//];
function generateOrderData(){
    for ($i=0;$i<100;$i++){
        $order = [];
        $range = mt_rand(0,5);
        for($k = 0;$k<$range;$k++){
            $orderId = date('YmdHis').rand(1000,9999);
            $range2 = mt_rand(1,5);
            $item = [];
            for($j = 0;$j<$range2;$j++){
                $item[] = [
                    'itemnumer'=>$orderId,
                    'productname'=>'商品'.rand(10,99),
                    'amount'=>'1',
                    'mark'=>'备注',
                ];
            }
            $order[] = [
                'order'=>$orderId ,
                'time'=>date('Y-m-d H:i:s'),
                'username'=>'张三',
                'address'=>'成都',
                'phone'=>'17756891562',
                'item'=> $item,
            ];
        }
        yield $order;
    }
}
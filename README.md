# xlswriter+yield+websocket
这是一个基于xlswriter+yield高性能操作excel的库，集成了websocket用于excel操作时的进度推送。  
本类继承自xlswriter扩展， 重新封装了一些常用的方法，同时保持基类的用法。  
xlswriter文档<https://xlswriter-docs.viest.me/>
# 目录
* [功能特性](#功能特性)
* 安装
	* [installing](#Installing)
	* [安装xlswrite扩展](#安装xlswriter扩展)
	* [WebSocket](#WebSocket)
* [examples](#examples)
	* excel导出
		* [导出excel快速上手](#导出excel快速上手)
		* [下载excel文件](#下载excel文件)
		* [使用WebSocket推送excel处理进度](#使用WebSocket推送excel处理进度)
		* [设置字段&表格头](#设置字段&表格头)
		* [样式设置](#样式设置)
		* [合并单元格](#合并单元格)
			* [动态合并单元格](#动态合并单元格)
				* [通用合并demo](#通用合并demo)
				* [订单类型合并demo](#订单类型合并demo)
	* excel读取
		* [游标读取excel分段写入数据库](#游标读取excel分段写入数据库)


## 功能特性
* 高性能读写百万级别的excel数据
* 支持动态合并单元格
* 字段定义和过滤
* excel处理进度条

## Installing
```
composer require yzh0325/xlswrite
```
## 安装xlswriter扩展 
* php>=7.0
* windows环境下 php>=7.2 ,xlswriter版本号大于等于 1.3.4.1 [安装帮助](https://xlswriter-docs.viest.me/zh-cn/an-zhuang)
```
pecl install xlswriter
```

## WebSocket 
* 服务端采用swoole搭建，需要安装[swoole](https://wiki.swoole.com/#/environment)扩展 swoole>=4.4.*,需在cli模式下运行
* 客户端采用textalk/websocket的client作为websocket客户端,可在php-fpm模式下运行

## examples
见 examples/ 
* xlswrite_demo.html 前端demo
* export_demo.php excel导出demo
* import_demo.php excel导入demo

### 导出excel快速上手
```
use Pxlswrite\Pxlswrite;
$fileObj = new Pxlswrite(['path' => __DIR__]);
$filePath = $fileObj
    ->fileName('123.xlsx','sheet1') //fileName 会自动创建一个工作表，你可以自定义该工作表名称，工作表名称为可选参数
    ->field([
        'username'=>['name'=>'用户名'],
        'age'=>['name'=>'年龄']
    ]) //设置字段&表格头
    ->setDataByGenerator('generateData') //通过回调生成器方法向excel填充数据
    ->output(); //输出excel文件到磁盘，返回文件路径
$fileObj->download($filePath); //下载excel文件
/**
* 数据生成器方法 封装数据获取逻辑 通过yield返回 节省内存
*/
function generateData(){
    for($i=0;$i<10000;$i++){
        yield [
                [
                    'username' => '匿名用户'.rand(1,9999),
                    'age' => rand(1,100),  
                 ]
              ];
    }
}
```
### 下载excel文件
可以设置文件下载完成后是否自动删除原文件，download方法参数二默认值为true自动删除原文件。

函数原型
```
/**
 * 文件下载
 * @param string $_filePath 文件绝对路径
 * @param bool $_isDelete 下载后是否删除原文件
 * @throws PathException
 */
download($_filePath, $_isDelete = true)
```
示例
```
$fileObj->download($filePath); 
//false 文件下载后不会自动删除原文件，true 默认值，下载后自动删除原文件
//$fileObj->download($filePath,false); 
```
### 使用WebSocket推送excel处理进度
实现过程：前端与websocket服务器先建立连接，连接建立好之后服务端会发送一条消息 {'status' => 'onopen', 'fd' => $request->fd},
前端将fd保存起来，在请求处理excel接口时将fd参数传给后台，后台在推送进度消息时将fd参数封装到消息体里在发送给websocket服务器
，websocket服务器根据fd参数在推送消息给指定的前端，这就完成了web前端、websocket服务器、应用后端之间的数据交互。

* 前端首次连接时websocket服务器发送的消息体结构：['status' => 'onopen', 'fd' => 1]
* 处理过程中websocket服务器发送的消息体结构：['status' => 'processing', 'process' => 100, 'fd'=>1]

前端可根据status状态进行相应的处理，status = onopen 时保存fd，status = processing 时进行进度条渲染。

配置:
	 WebSocket服务端默认使用9502端口,可自行修改./src/WebSocket/WebsocketServer的 CONST PORT = 9502  
	 
WebSocketClient使用：
```
use Pxlswrite\WebSocket\WebSocketClient;
//ws://127.0.0.1:9502 服务端地址
//$_GET['fd'] 前端传过来的 web端与websocket服务端连接的唯一标识客户端fd
$pushHandle = new WebSocketClient('ws://127.0.0.1:9502', $_GET['fd']);
//发送websocket消息
$pushHandle->send(['status' => 'rocessing', 'process' => 100, 'fd'=>1]);
```
处理excel时自动发送消息（在推送信息失败时不会影响、终止代码的执行，会生成相应的日志文件，日志文件存放于实例化Pxlswrite所设置的路径下的log目录，日志文件按日期存放）
```
//通用数据设置 generateData回调生成器方法获取数据，$pushHandle 用于推送，可选参数
setDataByGenerator('generateData', [], [], $pushHandle)
//订单类型数据设置 generateOrderData回调生成器方法获取数据，$pushHandle 用于推送，可选参数
setOrderData('generateOrderData',[],[],pushHandle)
```
示例

1.开启websocket 服务端
```
php ./src/WebSocket/WebSocketServer.php
```
2.web前端实现demo

```
var layer = layui.layer, $ = layui.jquery, upload = layui.upload;
var loading, fileLoading;
var wsServer = 'ws://127.0.0.1:9502';
var websocket = new WebSocket(wsServer);
var fd = 0;//客户端socket id  
websocket.onopen = function (evt) {
     console.log("Connected to WebSocket server.");
};
websocket.onclose = function (evt) {
    console.log("Disconnected");
};
websocket.onmessage = function (evt) {
    var data = JSON.parse(evt.data);
    if (data.status == 'onopen') {
        fd = data.fd;
    } else {
        $('#process').text('已处理' + data.process + '条数据...');
        // console.log(data.process);
    }
    // console.log('Retrieved data from server: ' + data);
};
websocket.onerror = function (evt, e) {
    console.log('Error occured: ' + evt.data);
};

//导出excel
    function exporData() {
        loading = layer.load(2, {
            shade: [0.1, '#fff'],
            content: '正在到导出，请耐心等待...',
            id: 'process',
            success: function (layero) {
                layero.find('.layui-layer-content').css({
                    'padding-top': '40px',//图标与样式会重合，这样设置可以错开
                    'width': '200px',//文字显示的宽度
                    'text-indent': '-4rem',
                });
            }
        });
        //请求后台excel文件生成接口
        $.get('/export_demo.php', {fd: fd}, function (res) {
            if (res.code == 1) {
                layer.close(loading);
                layer.msg(res.msg, {time: '1000'}, function () {
                    //请求后台下载地址
                    window.location.href = res.url;
                });
            }
        }, 'json');
    }
```
3.后端处理excel 并调用 WebSocketClient 推送消息
```
use Pxlswrite\Pxlswrite;
use Pxlswrite\WebSocket\WebSocketClient;
$fileObj = new Pxlswrite(['path' => __DIR__]);
//实例化WebSocketClient--需要推送进度才实例化
$pushHandle = new WebSocketClient('ws://127.0.0.1:9502', $_GET['fd']);
$filePath = $fileObj->fileName('123.xlsx','sheet1')
    ->field($field)//设置字段&表格头
    ->setDataByGenerator('generateData', [], [], $pushHandle)//设置数据 回调生成器方法获取数据，$pushHandle 用于推送，可选参数
    ->output();//输出excel文件到磁盘
//ajax请求返回下载地址
echo json_encode(['code' => 1, 'msg' => '导出完毕', 'url' => '/download.php?file=' . $filePath]);
```

### 设置字段&表格头
通过field()可以进行字段的定义&表格头的设置，使用header()定义的表格头会覆盖field()定义的表格头；  
使用field()定义字段后在使用setDataByGenerator()和setOrderData()时会进行字段的过滤，设置了回调的方法还会调用字段的回调方法，进行字段的格式化处理等操作。**但并不推荐设置字段的回调，因为效率不高，可以在传入数据的时候就处理好字段的值**。**推荐使field()设置表格头,设置过field才支持动态单元格行合并。**

函数原型
```
/**
 * 设置字段&表格头
 * @param array $field 字段定义数组 数据格式如下
 * [
 *  'name' => ['name' => '姓名','callback'=>'functionName'],
 *  'age' => ['name' => '年龄'],
 * ]
 * @return $this
 * @throws DataFormatException
 */
function field($field)
```
示例
```
use Pxlswrite\Pxlswrite;
//定义字段
$field = [
    'name' => ['name' => '姓名'],
    'year' => ['name' => '出生年份'],
    'age' => ['name' => '年龄','callback'=>'ageFormat']//callback 回调方法处理格式化值 可以是函数/对象方法
];
$fileObj = new Pxlswrite(['path' => __DIR__ ]);
$fileObj->fileName('1234.xlsx')
    ->field($field)//设置字段&表格头
    ->data([
        ['Rent', 1999,0],
        ['Gas',  1996,0],
        ['Food', 1998,0],
        ['Gym',  1995,0],
    ])
    ->output();//输出excel文件到磁盘
//格式化字段值
function ageFormat($v, $values)
{
    return date('Y') - $values['year'];
}
```
### 样式设置

支持的样式如下：
```
$style = [
    'align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER],//对齐 [x,y]
    'border' => Pxlswrite::BORDER_SLANT_DASH_DOT,//单元格边框
    'background' => Pxlswrite::COLOR_RED,//单元格背景色
    'fontColor' => Pxlswrite::COLOR_BLUE,//字体颜色
    'underline' => Pxlswrite::UNDERLINE_SINGLE,//下划线
    'fontSize' => 30,//字体大小
    'font' => 'FontName',//设置字体 字体名称，字体必须存在于本机
    'number' => '#,##0',//数字格式化
    'bold' => true,//粗题
    'strikeout' => false,//文本删除线
    'wrap' => true,//文本换行
    'italic' => true,//斜体
];
```
 样式相关常量
```
    const FORMAT_ALIGN_LEFT = Format::FORMAT_ALIGN_LEFT;                                    // 水平左对齐
    const FORMAT_ALIGN_CENTER = Format::FORMAT_ALIGN_CENTER;                                // 水平剧中对齐
    const FORMAT_ALIGN_RIGHT = Format::FORMAT_ALIGN_RIGHT;                                  // 水平右对齐
    const FORMAT_ALIGN_FILL = Format::FORMAT_ALIGN_FILL;                                    // 水平填充对齐
    const FORMAT_ALIGN_JUSTIFY = Format::FORMAT_ALIGN_JUSTIFY;                              // 水平两端对齐
    const FORMAT_ALIGN_CENTER_ACROSS = Format::FORMAT_ALIGN_CENTER_ACROSS;                  // 横向中心对齐
    const FORMAT_ALIGN_DISTRIBUTED = Format::FORMAT_ALIGN_DISTRIBUTED;                      // 分散对齐
    const FORMAT_ALIGN_VERTICAL_TOP = Format::FORMAT_ALIGN_VERTICAL_TOP;                    // 顶部垂直对齐
    const FORMAT_ALIGN_VERTICAL_BOTTOM = Format::FORMAT_ALIGN_VERTICAL_BOTTOM;              // 底部垂直对齐
    const FORMAT_ALIGN_VERTICAL_CENTER = Format::FORMAT_ALIGN_VERTICAL_CENTER;              // 垂直剧中对齐
    const FORMAT_ALIGN_VERTICAL_JUSTIFY = Format::FORMAT_ALIGN_VERTICAL_JUSTIFY;            // 垂直两端对齐
    const FORMAT_ALIGN_VERTICAL_DISTRIBUTED = Format::FORMAT_ALIGN_VERTICAL_DISTRIBUTED;    // 垂直分散对齐

    const UNDERLINE_SINGLE = Format::UNDERLINE_SINGLE;                                      // 单下划线
//    const UNDERLINE_DOUBLE = Format::UNDERLINE_DOUBLE;                                      // 双下划线
    const UNDERLINE_SINGLE_ACCOUNTING = Format::UNDERLINE_SINGLE_ACCOUNTING;                // 会计用单下划线
    const UNDERLINE_DOUBLE_ACCOUNTING = Format::UNDERLINE_DOUBLE_ACCOUNTING;                // 会计用双下划线

    const BORDER_THIN = Format::BORDER_THIN;                                                // 薄边框风格
    const BORDER_MEDIUM = Format::BORDER_MEDIUM;                                            // 中等边框风格
    const BORDER_DASHED = Format::BORDER_DASHED;                                            // 虚线边框风格
    const BORDER_DOTTED = Format::BORDER_DOTTED;                                            // 虚线边框样式
    const BORDER_THICK = Format::BORDER_THICK;                                              // 厚边框风格
    const BORDER_DOUBLE = Format::BORDER_DOUBLE;                                            // 双边风格
    const BORDER_HAIR = Format::BORDER_HAIR;                                                // 头发边框样式
    const BORDER_MEDIUM_DASHED = Format::BORDER_MEDIUM_DASHED;                              // 中等虚线边框样式
    const BORDER_DASH_DOT = Format::BORDER_DASH_DOT;                                        // 短划线边框样式
    const BORDER_MEDIUM_DASH_DOT = Format::BORDER_MEDIUM_DASH_DOT;                          // 中等点划线边框样式
    const BORDER_DASH_DOT_DOT = Format::BORDER_DASH_DOT_DOT;                                // Dash-dot-dot边框样式
    const BORDER_MEDIUM_DASH_DOT_DOT = Format::BORDER_MEDIUM_DASH_DOT_DOT;                  // 中等点划线边框样式
    const BORDER_SLANT_DASH_DOT = Format::BORDER_SLANT_DASH_DOT;                            // 倾斜的点划线边框风格

    const COLOR_BLACK = Format::COLOR_BLACK;
    const COLOR_BLUE = Format::COLOR_BLUE;
    const COLOR_BROWN = Format::COLOR_BROWN;
    const COLOR_CYAN = Format::COLOR_CYAN;
    const COLOR_GRAY = Format::COLOR_GRAY;
    const COLOR_GREEN = Format::COLOR_GREEN;
    const COLOR_LIME = Format::COLOR_LIME;
    const COLOR_MAGENTA = Format::COLOR_MAGENTA;
    const COLOR_NAVY = Format::COLOR_NAVY;
    const COLOR_ORANGE = Format::COLOR_ORANGE;
    const COLOR_PINK = Format::COLOR_PINK;
    const COLOR_PURPLE = Format::COLOR_PURPLE;
    const COLOR_RED = Format::COLOR_RED;
    const COLOR_SILVER = Format::COLOR_SILVER;
    const COLOR_WHITE = Format::COLOR_WHITE;
    const COLOR_YELLOW = Format::COLOR_YELLOW;
```
 样式设置的相关方法
```
/**
 * 行单元格样式
 * @param string $range  单元格范围
 * @param double $height 单元格高度
 * @param resource|array $formatHandler  单元格样式
 * @return $this
 * @throws \Exception
 */
setRow($range, $height, $formatHandler = null);
/**
 * 列单元格样式
 * @param $range string 单元格范围
 * @param $width double 单元格宽度
 * @param null $formatHandler resource|array 单元格样式
 * @return $this
 * @throws \Exception
 */
setColumn($range, $width, $formatHandler = null)
/**
 * 全局默认样式
 * @param resource|array $formatHandler style
 * @return $this
 * @throws DataFormatException
 */
defaultFormat($formatHandler)
 /**
 * 合并单元格
 * @param string $scope   单元格范围
 * @param string $data    data
 * @param resource|array $formatHandler 合并单元格的样式
 * @return $this
 * @throws DataFormatException
 */
mergeCells($scope, $data, $formatHandler = null)
```
 示例
```
use Pxlswrite\Pxlswrite;
$fileObj = new Pxlswrite(['path' => __DIR__]);
$filePath = $fileObj->fileName('123.xlsx','sheet1')
    ->field($field)//设置字段&表格头
    ->setDataByGenerator('generateData')//设置数据
    ->setRow('A1:A3', 80, ['bold'=>true]) //设置单元行样式 A1:A3 单元格范围 80行高 ['blod'=>true] 加粗
    ->setColumn('A:F', 20, ['background' => Pxlswrite::COLOR_GRAY, 'align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]]) //设置单元列样式
    ->output();//输出excel文件到磁盘
```
注意:设置行与行/列与列样式 交集范围会覆盖；行样式优先于列样式；全局默认样式不会被覆盖，谨慎使用

### 合并单元格
 函数原型
```
/**
 * 合并单元格
 * @param string $scope   单元格范围
 * @param string $data    data
 * @param resource|array $formatHandler  合并单元格的样式
 * @return $this
 * @throws DataFormatException
 */
mergeCells($scope, $data, $formatHandler = null)
```
 示例
```
$fileObj->fileName("test.xlsx")
  ->mergeCells('A1:C1', 'Merge cells')
  ->output();
```
#### 动态合并单元格
* 通用合并，根据数据的值比较 自动进行 行合并（调用方法时需要指定要自动合并的字段，才会根据字段值进行自动合并）

优点：数据层不需要怎么处理，将数据库查询出来的二维数组直接传入即可。  
缺点：无法满足像订单一样的 存在多个订单时间值是一样的，合并就会存在问题。

* 订单类型合并，将原始数据处理成指定的数据格式在传入此类自动进行 行合并（调用方法时需要指定要自动合并的字段，才会根据字段值进行自动合并）

优点：根据传入的数据格式进行合并，合并更加精准不会存在问题。  
缺点：对数据格式有要求，在数据层需要处理成指定的数据格式,数据格式如下：
```
$data = [
    [
        'order'=>'20200632555' ,
        'time'=>'2020-06-30 12:30:10',
        'username'=>'张三',
        'address'=>'成都',
        'phone'=>'17756891562',
        'item'=> [
            [
                'itemnumer'=>'2020515',
                'productname'=>'商品1',
                'mark'=>'备注',
            ],
        ],
    ]
];
```
字段数量，名称没有限制，只支持一个item(也可以叫其他名字)二维数组，item数组里面的个数没有限制。
**当数据字段的值能明显区分时推荐使用通用合并，当数据字段的值不能明显区分时（如：时间字段）推荐订单类型的合并**
##### 通用合并demo
 函数原型
```
/**
 * @todo 通过生成器逐行向表格插入数据，
 * 设置过field才支持动态单元格合并，
 * 可以根据指定的字段 通过值比较自动进行 行合并
 * @param callable $_generator 回调数据生成器方法 返回的数据格式是二维数组 如下字段名数量不限
 * [['id'=>1,'name'=>'张三','age'=>'18']]
 * @param array $_mergeColumn 需要合并的字段
 * @param array $_mergeColumnStyle 合并单元格的样式
 * @param int $_index 单元格行偏移量 合并单元格的起始位置
 * @param WebSocketClient|null $_pushHandle
 * @return Pxlswrite
 * @throws DataFormatException 数据格式错误
 * @throws CellOutOfRangeException 超出单元列的范围(A-ZZ)
 */
setDataByGenerator($_generator, array $_mergeColumn = [], array $_mergeColumnStyle = [], WebSocketClient $_pushHandle = null, $_index = 1)
```
 示例
```
use Pxlswrite\Pxlswrite;
//定义字段
$field = [
    'id' => ['name' => 'title'],
    'c1' => ['name' => 'age'],
    'c2' => ['name' => 'year'],
    'c3' => ['name' => 'kk'],
];
$fileObj = new Pxlswrite(['path' => __DIR__ ]);
$filePath = $fileObj->fileName('123.xlsx');
    ->field($field)//设置字段&表格头
    ->setDataByGenerator('generateData', ['c1', 'c2'], ['align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//设置数据 并自动合并单元格
    ->output();//输出excel文件到磁盘
//数据生成器
function generateData(){
    for($i=0;$i<10000;$i++){
        yield [
                ['id'=>$i,'c1'=>$i+1,'c2'=>$i+2,'c3'=>$i+3];
            ];
    }
}
```
##### 订单类型合并demo
 函数原型
```
/**
 * 设置订单数据 根据数据可以合并指定的字段,需要遵循以下数据格式
 * @param callable $_generator 数据生成器方法 返回数据格式如下，字段数量名称不限，只支持一个item二维数组
 * [
 *    [
 *        'order'=>'20200632555' ,
 *        'time'=>'2020-06-30 12:30:10',
 *        'username'=>'张三',
 *        'address'=>'成都',
 *        'phone'=>'17756891562',
 *        'item'=> [
 *            [
 *                'itemnumer'=>'2020515',
 *                'productname'=>'商品1',
 *                'mark'=>'备注',
 *            ],
 *        ],
 *    ]
 * ];
 * @param array $_mergeColumn 需要合并的字段
 * @param array $_mergeColumnStyle 合并单元格样式
 * @param WebSocketClient|null $_pushHandle WebSocketClient对象 用于推送进度
 * @param int $_index 单元格行偏移量 合并单元格的起始位置
 * @return $this
 * @throws DataFormatException 数据格式错误
 * @throws CellOutOfRangeException 超出单元列的范围(A-ZZ)
 */
setOrderData($_generator, array $_mergeColumn = [], array $_mergeColumnStyle = [], WebSocketClient $_pushHandle = null, $_index = 1)
```
 示例
```
use Pxlswrite\Pxlswrite;
//定义字段
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
$fileObj = new Pxlswrite(['path' => __DIR__ ]);
$filePath = $fileObj->fileName('123.xlsx');
    ->field($orderField)//设置字段&表格头
    ->setOrderData('generateOrderData',['order','time'],['align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//设置订单类型数据并自动合并单元格
    ->output();//输出excel文件到磁盘
//订单数据生成器 一个订单有多个item
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
```

### 游标读取excel分段写入数据库
 函数原型
```
/**
 * 游标读取excel，分段插入数据库
 * @param callable $_func 方法名 回调数据插入的方法
 * @param WebSocketClient|null $_pushHandle
 * @param array $_dataType 可指定每个单元格数据类型进行读取
 */
importDataByCursor($_func, WebSocketClient $_pushHandle = null, array $_dataType = [])
```
示例
```
use Pxlswrite\Pxlswrite;
$fileObj = new Pxlswrite(['path' => dirname($_GET['file'])]);
$fileInfo = explode('/', $_GET['file']);
$fileName = end($fileInfo);
//实例化WebSocketClient
$pushHandle = new WebSocketClient('ws://127.0.0.1:9502',$_GET['fd']);
//          打开excel文件       打开工作表   跳过一行数据      读取并导入数据
$fileObj->openFile($fileName)->openSheet()->setSkipRows(1)->importDataByCursor('insert_data',$pushHandle);

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
```
更多使用方法见 [xlswrite](https://xlswriter-docs.viest.me/zh-cn)
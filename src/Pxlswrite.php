<?php
/**
 * xlswriter简单封装
 */

namespace Pxlswrite;
set_time_limit(0);

use Pxlswrite\WebSocket\WebSocketClient;
use \Vtiful\Kernel\Format;
use \Vtiful\Kernel\Excel;

class Pxlswrite extends Excel
{
    /**********************************************样式常量*****************************************************/
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
    /**********************************************样式常量*****************************************************/
    protected $m_config = [
        'path' => __DIR__,
        'maxColumnWidth' => 50,
    ];
    /**
     * [$fieldsCallback 设置字段回调函数]
     * @var array
     */
    public $m_fieldsCallback = [];
    /**
     * 表格头
     * @var array
     */
    public $m_header = [];
    /**
     * @var array 默认样式
     */
    public $m_defaultStyle = [];
    /**
     * excel 行索引
     * @var int
     */
    public static $s_rowIndex = 1;
    /**
     * 自适应列宽（各字段最大长度）
     * @var array
     */
    public $m_autoSize = [];
    /**
     * excel 列索引
     * @var int
     */
    public static $s_colIndex = 0;
    /**
     * Pxlswrite constructor.
     * @param array $_config
     */
    public function __construct($_config = array())
    {
        foreach ($_config as $k => $v) {
            $this->m_config[$k] = $v;
        }
        parent::__construct($this->m_config);
    }

    /**
     *  创建工作表
     * @param string $_fileName
     * @param string $_tableName
     * @return $this
     */
    public function fileName($_fileName, $_tableName = 'sheet1')
    {
        parent::fileName($_fileName, $_tableName);
        return $this;
    }

    /**
     *  设置字段
     * @param array $_field 字段定义数组 数据格式如下
     * [
     *  'name' => ['name' => '姓名','callback'=>'functionName'],
     *  'age' => ['name' => '年龄'],
     * ]
     * @return $this
     * @throws DataFormatException
     */
    public function field($_field)
    {
        if (!empty($_field)) {
            $this->m_fieldsCallback = array_merge($this->m_fieldsCallback, $_field);
        }
        if (empty($this->m_header)) {
            $this->header(array_column($_field, 'name'));
        }
        return $this;
    }

    /**
     *  设置表格头
     * @param array $_header
     * @param null $_formatHandler
     * @return mixed
     * @throws DataFormatException
     */
    public function header($_header, $_formatHandler = NULL)
    {
        if (count($_header) !== count($_header, 1)) {
            throw new DataFormatException('header数据格式错误,必须是一位数索引数组');
        }
        foreach ($_header as $k=>$v){
            //初始化列宽
            $this->m_autoSize[$k] = strlen($v);
        }
        $this->m_header = $_header;
        if(!empty($_formatHandler)){
            if (!is_resource($_formatHandler)) {
                $_formatHandler = $this->styleFormat($_formatHandler);
            }
            parent::header($_header, $_formatHandler);
        }else{
            parent::header($_header);
        }
        return $this;
    }

    /**
     *  设置表格数据
     * @param array $_data 二维索引数组
     * @return
     */
    public function data($_data)
    {
        $this->calculateColumnWidth($_data);
        return parent::data($_data);
    }

    /**
     * 计算单元格字段宽度
     * @param $_data
     */
    public function calculateColumnWidth($_data)
    {
        foreach ($_data as $k=>$v){
            foreach ($v as $key=>$value){
                $length = strlen($value);
                $size = $this->m_autoSize[$key] >= $length ? $this->m_autoSize[$key] : $length;
                if($size > $this->m_config['maxColumnWidth']){
                    $size = $this->m_config['maxColumnWidth'];
                }
                $this->m_autoSize[$key] = $size;
            }
        }
    }

    /**
     * 设置单元格自适应列宽
     * @param array $_range 单元列范围  e.g. ['A:B','C'] 为空则默认所有单元列
     * @return $this
     * @throws DataFormatException
     */
    public function setAutoSize(array $_range = [])
    {
        if(!empty($_range)){
            //指定列自适应最大宽度
            foreach($_range as $columns){
                $columnArr = explode(':',$columns);
                $start = strtoupper($columnArr[0]);
                $end = strtoupper(end($columnArr));
                for($i = $start;$i <= $end;$i++){
                    $width = $this->getColumnMaxWidth($i);
                    $this->setColumn($i.':'.$i,$width * 1.05);
                }
            }
        }else{
            //所有列自适应最大宽度
            foreach ($this->m_autoSize as $key => $value){
                $column = self::stringFromColumnIndex($key);
                $this->setColumn($column.':'.$column,$value * 1.05);
            }
        }
        return $this;
    }

    /**
     * 获取单元列最大宽度
     * @param string $_column
     * @return mixed
     */
    public function getColumnMaxWidth(string $_column)
    {
        $columnIndex = self::columnIndexFromString($_column);
        return $this->m_autoSize[$columnIndex];
    }
    /**
     *  设置一般数据 通过生成器逐行向表格插入数据，
     * 设置过field才支持动态单元格合并，
     * 可以根据指定的字段 通过值比较自动进行 行合并
     * @param callable $_generator 回调数据生成器方法 返回的数据格式是二维数组 如下字段名数量不限
     * [['id'=>1,'name'=>'张三','age'=>'18']]
     * @param array $_mergeColumn 需要合并的字段
     * @param array $_mergeColumnStyle 统一设置合并单元格的样式，设置后将无法修改样式，若要单独设置样式，参数应为空值，后续可用setColumn方式设置样式
     * @param int $_index 单元格行偏移量 合并单元格的起始位置
     * @param WebSocketClient|null $_pushHandle
     * @return Pxlswrite
     * @throws DataFormatException 数据格式错误
     */
    public function setGeneralData($_generator, array $_mergeColumn = [], array $_mergeColumnStyle = [], WebSocketClient $_pushHandle = null, $_index = 1)
    {
        $count = 0;//统计数据处理条数
        $cellKey = [];//装载需要合并的字段
        foreach ($_mergeColumn as $k => $v) {
            $key = array_search($v, array_keys($this->m_fieldsCallback));
            $cellKey[$v] = self::stringFromColumnIndex($key);
            //临时存放需要合并的值
            $tempValue[$v] = [
                'count' => 0,
                'value' => null,
            ];
        }
        //判断是否有定义字段 有则进行字段格式化&字段过滤
        if (!empty($this->m_fieldsCallback)) {
            foreach (call_user_func($_generator) as $item) {
                foreach ($item as $value) {
                    $_index++;//单元行自增
                    $temp = $this->filter($value);
                    $this->data([array_values($temp)]);//二维索引数组
                    //处理需要合并的单元格
                    foreach ($cellKey as $c => $cell) {
                        if ($tempValue[$c]['count'] == 0) {
                            $tempValue[$c]['value'] = $temp[$c];
                        }
                        if ($temp[$c] == $tempValue[$c]['value']) {
                            $cellMerge[$c][] = $cell . $_index;
                            $tempValue[$c]['count']++;
                        }
                        //当前单元格与前一单元格值不在相等时合并单元格
                        if ($temp[$c] != $tempValue[$c]['value']) {
                            if (!empty($cellMerge[$c])) {
                                $startPosition = $cellMerge[$c][0];
                                $endPosition = end($cellMerge[$c]);
                                $this->mergeCells($startPosition . ':' . $endPosition, $tempValue[$c]['value'], $_mergeColumnStyle);
                            }
                            $cellMerge[$c] = [];
                            $tempValue[$c]['count'] = 1;
                            $tempValue[$c]['value'] = $temp[$c];
                            $cellMerge[$c][] = $cell . $_index;
                        }
                    }
                }
                //推送消息
                $count += count($item);
                $this->push($_pushHandle, $count);
            }
            //遍历到最后一行数据时，进行剩余还未合并单元行的单元行合并
            foreach($cellKey as $c => $cell){
                if($tempValue[$c]['count'] > 1){
                    $startPosition = $cellMerge[$c][0];
                    $endPosition = end($cellMerge[$c]);
                    $this->mergeCells($startPosition . ':' . $endPosition, $tempValue[$c]['value'], $_mergeColumnStyle);
                }
            }
        } else {
            foreach (call_user_func($_generator) as $item) {
                //循环逐行写入excel
                foreach ($item as $value) {
                    $this->data([array_values($value)]);//二维索引数组
                }
                //推送消息
                $count += count($item);
                $this->push($_pushHandle, $count);
            }
        }
        return $this;
    }

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
     * @param array $_mergeColumnStyle 统一设置合并单元格的样式，设置后将无法修改样式，若要单独设置样式，参数应为空值，后续可用setColumn方式设置样式
     * @param WebSocketClient|null $_pushHandle WebSocketClient对象 用于推送进度
     * @param int $_index 单元格行偏移量 合并单元格的起始位置
     * @return $this
     * @throws DataFormatException 数据格式错误
     */
    public function setOrderData($_generator, array $_mergeColumn = [], array $_mergeColumnStyle = [], WebSocketClient $_pushHandle = null, $_index = 1)
    {
        $count = 0;//统计数据处理条数
        $cellKey = [];//装载需要合并的字段
        foreach ($_mergeColumn as $k => $v) {
            $key = array_search($v, array_keys($this->m_fieldsCallback));
            $cellKey[$v] = self::stringFromColumnIndex($key);
        }
        foreach (call_user_func($_generator) as $item) {
            foreach ($item as $key => $value) {
                $i = 0;//标记数组指针位置
                foreach ($value as $k1 => $v1) {
                    $i++;
                    //判断当前值是否是数组
                    if (!is_array($v1)) {
                        $orderInfo[$k1] = $v1;//存放order的信息
                    } else {
                        //是数组则进行遍历格式化值
                        $temp = [];//存放处理后的item值
                        if (!empty($v1)) {
                            foreach ($v1 as $k2 => $v2) {
                                $temp[] = $this->filter($v2);
                            }
                        } else {
                            $temp[] = $this->filter([]);
                        }
                    }
                    //遍历到数组最后一个时，进行逐行插入、合并单元格
                    if (count($value) == $i) {
                        //处理订单相关字段过滤
                        $orderTemp = $this->filter($orderInfo);
                        foreach ($orderTemp as $k4 => $v4) {
                            if (!key_exists($k4, $orderInfo)) {
                                unset($orderTemp[$k4]);
                            }
                        }
                        //循环插入订单item  一个order对应多个item
                        foreach ($temp as $k3 => $v3) {
                            $_index++;//单元行自增
                            $data = array_merge($v3, $orderTemp);
                            $this->data([array_values($data)]);
                        }
                        //合并单元格
                        foreach ($cellKey as $column => $cell) {
                            $offset = $_index - count($temp) + 1;
                            $startPosition = $cell . $offset;
                            $endPosition = $cell . $_index;
                            $this->mergeCells($startPosition . ':' . $endPosition, $data[$column], $_mergeColumnStyle);
                        }
                    }
                }
            }
            //推送消息
            $count += count($item);
            $this->push($_pushHandle, $count);
        }
        return $this;
    }


    /**
     *  字段过滤&格式化
     * @param array $_value 一维数组
     * @return array 处理之后的结果数组
     */
    public function filter($_value)
    {
        $temp = [];
        foreach ($this->m_fieldsCallback as $k => $v) {
            $temp[$k] = isset($_value[$k]) ? $_value[$k] : '';
            //回调字段处理方法
            if (isset($v['callback'])) {
                $temp[$k] = call_user_func($v['callback'], $temp[$k], $_value);
            }
        }
        return $temp;
    }

    /**
     *  游标读取excel，分段插入数据库
     * @param callable $_func 方法名 回调数据插入的方法
     * @param WebSocketClient|null $_pushHandle
     * @param array $_dataType 可指定每个单元格数据类型进行读取
     */
    public function importDataByCursor($_func, WebSocketClient $_pushHandle = null, array $_dataType = [])
    {
        $count = 0;
        //游标读取excel数据 每一万条数据执行一次插入数据库 防止数据装载在内存过大
        while ($res = $this->nextRow($_dataType)) {
            $data[] = $res;
            $count++;
            if ($count % 10000 == 0) {
                //回调数据插入的方法
                call_user_func($_func, $data);
                //消息推送
                $this->push($_pushHandle, $count);
                unset($data);
            }
        }
        if (!empty($data)) {
            call_user_func($_func, $data);
            $this->push($_pushHandle, $count);
        }
    }

    /**
     *  消息推送
     * @param $_pushHandle
     * @param $_count
     */
    public function push($_pushHandle, $_count)
    {
        try {
            if ($_pushHandle && $_pushHandle->m_receiverFd) {
                $_pushHandle->send(['status' => 'processing', 'process' => $_count]);
            }
        } catch (\Exception $exception) {
            $this->writeLog($exception->getMessage(), [$exception->getTraceAsString()]);
        }

    }

    /**
     *  文件下载
     * @param string $_filePath 文件绝对路径
     * @param bool $_isDelete 下载后是否删除原文件
     * @throws PathException
     */
    public function download($_filePath, $_isDelete = true)
    {
        if (dirname($_filePath) != $this->m_config['path']) {
            throw new PathException('未知文件路径:' . dirname($_filePath));
        }
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Disposition: attachment;filename="' . end(explode('/', $_filePath)) . '"');
        header('Content-Length: ' . filesize($_filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        ob_clean();
        flush();

        if (copy($_filePath, 'php://output') === false) {
            // Throw exception
        }
        if ($_isDelete) {
            @unlink($_filePath);
        }
    }

    /**
     *  打开文件
     * @param string $_fileName 文件名称
     * @return mixed
     */
    public function openFile($_fileName)
    {
        return parent::openFile($_fileName);
    }

    /**
     *  读取表格
     * @param string $_fileName
     * @return mixed
     */
    public function import($_fileName)
    {
        $data = $this
            ->openFile($_fileName)
            ->openSheet()
            ->getSheetData();
        return $data;
    }

    /**
     *  写日志
     * @param string $_message
     * @param array $_arr
     */
    public function writeLog($_message, array $_arr)
    {
        $dir = rtrim($this->m_config['path'], '/') . '/log/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $time = date('Y-m-d H:i:s');
        file_put_contents($dir . date("Y-m-d") . "_error.log", "[{$time}] " . $_message . PHP_EOL . serialize($_arr) . PHP_EOL, FILE_APPEND);
    }

    /**
     *  格式化样式
     * @param array $_style 样式列表数组
     * @return Format resource
     * @throws DataFormatException
     */
    public function styleFormat($_style)
    {
        $format = new Format($this->getHandle());
        $_style = empty($_style) ? [] : $_style;
        //合并全局样式
        $_style = array_merge($this->m_defaultStyle,$_style);
        foreach ($_style as $key => $value) {
            switch ($key) {
                case 'align':
                    if (!is_array($value) || count($value) != 2) {
                        throw new DataFormatException('align 数据格式错误');
                    }
                    $format->align($value[0], $value[1]);
                    break;
                default:
                    if (is_bool($value)) {
                        if ($value === true) {
                            $format->$key();
                        }
                    } else {
                        $format->$key($value);
                    }
            }
        }
        return $format->toResource();
    }

    /**
     *  行单元格样式
     * @param string $_range 单元格范围
     * @param int|double $_height 单元格高度  -1 默认行高13.5镑
     * @param resource|array $_formatHandler 单元格样式
     * @return $this
     * @throws DataFormatException
     */
    public function setRow($_range, $_height = -1, $_formatHandler = null)
    {
        if (!is_resource($_formatHandler)) {
            $_formatHandler = $this->styleFormat($_formatHandler);
        }
        if($_height == -1){
            parent::setRow($_range, 13.5, $_formatHandler);
        }else{
            parent::setRow($_range, $_height, $_formatHandler);
        }

        return $this;
    }

    /**
     * 列单元格样式
     * @param string $_range 单元格范围  e.g.  'A:C'
     * @param int|double $_width 单元格宽度  -1 自适列宽
     * @param resource|array $_formatHandler 单元格样式
     * @return $this
     * @throws DataFormatException
     */
    public function setColumn($_range, $_width = -1, $_formatHandler = null)
    {
        if (!is_resource($_formatHandler)) {
            $_formatHandler = $this->styleFormat($_formatHandler);
        }
        if($_width == -1){
            //自适应列宽
            $columnArr = explode(':',$_range);
            $start = strtoupper($columnArr[0]);
            $end = strtoupper(end($columnArr));
            for($i = $start;$i <= $end;$i++) {
                $_width = $this->getColumnMaxWidth($i) * 1.05;
                parent::setColumn($i.':'.$i,$_width,$_formatHandler);
            }
        }else{
            parent::setColumn($_range, $_width, $_formatHandler);
        }

        return $this;
    }

    /**
     *  合并单元格
     * @param string $_scope 单元格范围
     * @param string $_data data
     * @param resource|array $_formatHandler 合并单元格的样式
     * @return $this
     * @throws DataFormatException
     */
    public function mergeCells($_scope, $_data, $_formatHandler = null)
    {
        if(!empty($_formatHandler)){
            if (!is_resource($_formatHandler)) {
                $_formatHandler = $this->styleFormat($_formatHandler);
            }
            parent::mergeCells($_scope, $_data, $_formatHandler);
        }else{
            parent::mergeCells($_scope, $_data);
        }

        return $this;
    }

    /**
     *  全局默认样式 对setRow,setColumn,insertUrl,insertText方法有效
     * @param resource|array $_formatHandler style
     * @return $this
     * @throws DataFormatException
     */
    public function setDefaultStyle($_formatHandler)
    {
        if (!is_resource($_formatHandler)) {
            $this->m_defaultStyle = $_formatHandler;
            $_formatHandler = $this->styleFormat($_formatHandler);
        }

//        parent::defaultFormat($_formatHandler);
        return $this;
    }

    /**
     *	String from columnindex
     *
     *	@param	int $_columnIndex Column index (base 0 !!!)
     *	@return	string
     */
    public static function stringFromColumnIndex($_columnIndex = 0)
    {
        //	Using a lookup cache adds a slight memory overhead, but boosts speed
        //	caching using a static within the method is faster than a class static,
        //		though it's additional memory overhead
        static $s_indexCache = array();

        if (!isset($s_indexCache[$_columnIndex])) {
            // Determine column string
            if ($_columnIndex < 26) {
                $s_indexCache[$_columnIndex] = chr(65 + $_columnIndex);
            } elseif ($_columnIndex < 702) {
                $s_indexCache[$_columnIndex] = chr(64 + ($_columnIndex / 26)) .
                    chr(65 + $_columnIndex % 26);
            } else {
                $s_indexCache[$_columnIndex] = chr(64 + (($_columnIndex - 26) / 676)) .
                    chr(65 + ((($_columnIndex - 26) % 676) / 26)) .
                    chr(65 + $_columnIndex % 26);
            }
        }
        return $s_indexCache[$_columnIndex];
    }

    /**
     * @param int $_row 行 从0开始
     * @param int $_col 列 从0开始
     * @param string $_data 数据
     * @param string $_format 数据格式
     * @param array $_formatHandler 单元格样式
     * @return $this
     * @throws DataFormatException
     */
    public function insertText($_row, $_col, $_data, $_format = '', $_formatHandler=[])
    {
        if (!is_resource($_formatHandler)) {
            $_formatHandler = $this->styleFormat($_formatHandler);
        }
        parent::insertText($_row,$_col,$_data,$_format,$_formatHandler);
        return $this;
    }

    /**
     * 插入链接
     * @param int $_row 行 从0开始
     * @param int $_col 列 从0开始
     * @param string $_url  链接地址
     * @param array $_formatHandler 单元格样式
     * @return $this
     * @throws DataFormatException
     */
    public function insertUrl($_row,$_col,$_url, $_formatHandler = [])
    {
        if (!is_resource($_formatHandler)) {
            $_formatHandler = $this->styleFormat($_formatHandler);
        }
        parent::insertUrl($_row, $_col, $_url, $_formatHandler);
        return $this;
    }

    /**
     * 设置数据，逐行逐列插入数据，可以区分文本插入和超链接插入
     * @param $_data
     * @param int $_rowIndex 单元行索引(起始位置为0)
     * @param int $_coleIndex 单元列索引(起始位置为0)
     * @throws DataFormatException
     */
    public function setData($_data,$_rowIndex = 1,$_coleIndex = 0)
    {
        if($_rowIndex != 1){
            self::$s_rowIndex = $_rowIndex;
        }
        if($_coleIndex !== 0){
            self::$s_colIndex = $_coleIndex;
        }
        foreach($_data as $item){
            self::$s_colIndex = $_coleIndex;
            foreach ($item as $key=>$value){
                if($isMatched = preg_match('/http(?:s?):\/\//', $value)){
                    $this->insertUrl(self::$s_rowIndex,self::$s_colIndex,$value);
                }else{
                    $this->insertText(self::$s_rowIndex,self::$s_colIndex,$value);
                }
                self::$s_colIndex++;
            }
            self::$s_rowIndex++;
        }
    }
}
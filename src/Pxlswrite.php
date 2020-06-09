<?php
/**
 * xlsxwriter简单封装
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
    ];
    /**
     * [$fieldsCallback 设置字段回调函数]
     * @var array
     */
    public $fieldsCallback = [];
    public $header = [];

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
//        $this->m_instance = new \Vtiful\Kernel\Excel($this->m_config);
    }

    /**
     * 创建工作表
     * @param $_fileName
     * @param string $_tableName
     * @return mixed
     */
    public function fileName($_fileName, $_tableName = 'sheet1')
    {
        return parent::fileName($_fileName, $_tableName);
    }

    /**
     * 设置字段
     * @param $field
     * @return $this
     */
    public function field($field)
    {
        if (!empty($field)) {
            $this->fieldsCallback = array_merge($this->fieldsCallback, $field);
        }
        if (empty($this->header)) {
            $this->header(array_column($field, 'name'));
        }
        return $this;
    }

    /**
     * 设置表格头
     * @param $header
     * @param null $format_handle
     * @return mixed
     */
    public function header($header, $format_handle = NULL)
    {
        if(count($header) !== count($header, 1)){
            echo '数据格式错误,必须是一位数索引数组';exit();
        }
        $this->header = $header;
        if ($format_handle) {
            return parent::header($header, $format_handle);
        } else {
            return parent::header($header);
        }
    }

    /**
     * 设置表格数据
     * @param array $_data 二维索引数组
     * @return
     */
    public function data($_data)
    {
        return parent::data($_data);
    }

    /**
     * 通过生成器按行向表格插入数据
     * @param $_generator
     * @param WebSocketClient $_pushHandle
     * @return Pxlswrite
     */
    public function setDataByGenerator($_generator, WebSocketClient $_pushHandle = null)
    {
        $count = 0;
        //判断是否有定义字段
        if(!empty($this->fieldsCallback)){
            foreach (call_user_func($_generator) as $item){
                foreach ($item as $value){
                    $temp = [];
                    //字段过滤
                    foreach ($this->fieldsCallback as $k=>$v){
                        $temp[$k] = isset($value[$k]) && !empty($value[$k]) ? $value[$k] : '';
                        //回调字段处理方法
                        if (isset($v['callback'])) {
                            $temp[$k] = call_user_func($v['callback'], $temp[$k], $value);
                        }
                    }
                    $this->data([array_values($temp)]);//二维索引数组
                }
                //推送消息
                $count += count($item);
                $this->push($_pushHandle,$count);
            }
        }else{
            foreach (call_user_func($_generator) as $item){
                //循环逐行写入excel
                foreach ($item as $value){
                    $this->data([array_values($value)]);//二维索引数组
                }
                //推送消息
                $count += count($item);
                $this->push($_pushHandle,$count);
            }
        }
        return $this;
    }

    /**
     * @param $_func string 方法名 回调数据插入的方法
     * @param WebSocketClient|null $_pushHandle
     * @param array $_dataType 可指定每个单元格数据类型进行读取
     */
    public function importData($_func, WebSocketClient $_pushHandle = null,array $_dataType = [])
    {
        $count = 0;
        //游标读取excel数据 每一万条数据执行一次插入数据库 防止数据装载在内存过大
        while ($res = $this->nextRow($_dataType)) {
            $data[] = $res;
            $count++;
            if ($count % 10000 == 0) {
                //回调数据插入的方法
                call_user_func($_func,$data);
                //消息推送
                $this->push($_pushHandle,$count);
                unset($data);
            }
        }
        if (!empty($data)) {
            call_user_func($_func,$data);
            $this->push($_pushHandle,$count);
        }
    }

    /**
     * 消息推送
     * @param $_pushHandle
     * @param $count
     */
    public function push($_pushHandle,$count){
        if ($_pushHandle && $_pushHandle->m_receiverFd) {
            $_pushHandle->send(['status' => 'processing', 'process' => $count]);
        }
    }
    /**
     * 文件下载
     * @param $_filePath 文件绝对路径
     * @param bool $_isDelete 下载后是否删除原文件
     * @return string
     */
    public function download($_filePath, $_isDelete = true)
    {
//        setcookie("loadingFlag",1);
        if (dirname($_filePath) != $this->m_config['path']) {
            echo '未知文件路径';
            exit();
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
        // echo file_get_contents($filePath);
        if (copy($_filePath, 'php://output') === false) {
            // Throw exception
        }
        if ($_isDelete) {
            @unlink($_filePath);
        }
    }

    /**
     * 打开文件
     * @param $zs_file_name 文件名称
     * @return mixed
     */
    public function openFile($zs_file_name)
    {
        return parent::openFile($zs_file_name);
    }

    /**
     * 读取表格
     * @param $_fileName
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
     * 样式容器
     * @return Format
     */
    public function styleFormat()
    {
        return new Format($this->getHandle());
    }
}
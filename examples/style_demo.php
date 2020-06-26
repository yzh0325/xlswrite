<?php
/**
 * 样式设置demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');

use Pxlswrite\DB\DB;
use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);
$field = [
    'id' => ['name' => 'title'],
    'c1' => ['name' => 'age'],
    'c2' => ['name' => 'year'],
    'c3' => ['name' => 'kk'],
    'c4' => ['name' => 'll'],
    'c5' => ['name' => 'aa']//callback 回调处理格式化值 可以是函数/对象方法
];
$filePath = $fileObj->fileName('style.xlsx', 'sheet1')
    ->field($field)//设置字段&表格头
    ->setGeneralData('generateData')//设置数据
    ->setRow('A1:A3', 80, ['bold' => true]) //设置单元行样式 A1:A3 单元格范围 80行高 ['blod'=>true] 加粗
    ->setColumn('A:F', 20, ['background' => Pxlswrite::COLOR_GRAY, 'align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]]) //设置单元列样式
    ->output();//输出excel文件到磁盘

function generateData()
{
    $db = DB::getInstance();
    $step = 10000;
    for ($i = 0; $i < 10000; $i = $i + 1) {
        yield [
            [
                'id' => $i + 1,
                'c1' => $i,
                'c2' => $i,
                'c3' => $i,
                'c4' => $i,
                'c5' => $i
            ]
        ];
    }
}
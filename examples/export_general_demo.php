<?php
/**
 * 一般通用数据自动合并导出demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;
//定义字段
$field = [
    'id' => ['name' => 'title'],
    'c1' => ['name' => 'age'],
    'c2' => ['name' => 'year'],
    'c3' => ['name' => 'kk'],
];
$fileObj = new Pxlswrite(['path' => __DIR__  . '/uploads' ]);
$filePath = $fileObj->fileName('general.xlsx')
    ->field($field)//设置字段&表格头
    ->setGeneralData('generateData', ['c1', 'c2'], ['align' => [Pxlswrite::FORMAT_ALIGN_CENTER, Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]])//设置数据 并自动合并单元格
    ->output();//输出excel文件到磁盘
//数据生成器
function generateData(){
    for($i=0;$i<10000;$i++){
        yield [
            ['id'=>$i,'c1'=>$i+1,'c2'=>$i+2,'c3'=>$i+3],
            ['id'=>$i,'c1'=>$i+1,'c2'=>$i+2,'c3'=>$i+3],
        ];
    }
}
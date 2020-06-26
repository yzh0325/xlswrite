<?php
/**
 * 简单导出demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;
$fileObj = new Pxlswrite(['path' => __DIR__. '/uploads']);
$filePath = $fileObj
    ->fileName('simple.xlsx','sheet1') //fileName 会自动创建一个工作表，你可以自定义该工作表名称，工作表名称为可选参数
    ->field([
        'username'=>['name'=>'用户名'],
        'age'=>['name'=>'年龄']
    ]) //设置字段&表格头
    ->setGeneralData('generateData') //通过回调生成器方法向excel填充数据
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
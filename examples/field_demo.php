<?php
/**
 * 字段定义demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;
//定义字段
$field = [
    'name' => ['name' => '姓名'],
    'year' => ['name' => '出生年份'],
    'age' => ['name' => '年龄','callback'=>'ageFormat']//callback 回调方法处理格式化值 可以是函数/对象方法
];
$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads' ]);
$fileObj->fileName('field.xlsx')
    ->field($field)//设置字段&表格头
    ->setGeneralData(function(){
       yield [
            ['name'=>'Rent', 'year'=>1999,'age'=>0],
            ['name'=>'Gas',  'year'=>1996,'age'=>0],
            ['name'=>'Food', 'year'=>1998,'age'=>0],
            ['name'=>'Gym',  'year'=>1995,'age'=>0],
        ];
    }
    )
    ->output();//输出excel文件到磁盘
//格式化字段值
function ageFormat($v, $values)
{
    return date('Y') - $values['year'];
}
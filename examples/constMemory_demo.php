<?php
/**
 * 固定内存模式demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);

$fileObj->constMemory('constMemory.xlsx');

$fileObj
    ->setRow('A1', 50, ['bold'=>true,'align'=>[Pxlswrite::FORMAT_ALIGN_CENTER,Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]]) // 写入数据前设置行样式
    ->header(['name', 'age'])
    ->data([['viest', 21]])
    ->output();
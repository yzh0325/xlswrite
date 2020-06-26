<?php
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);

$fileObj->fileName('filename.xlsx');

$fileObj
    ->header(['name', 'age'])
    ->data([['viest', 21]])
    ->setRow('A1', 50, ['bold'=>true,'align'=>[Pxlswrite::FORMAT_ALIGN_CENTER,Pxlswrite::FORMAT_ALIGN_VERTICAL_CENTER]]) // 可以在写入数据后设置行样式
    ->output();
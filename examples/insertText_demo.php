<?php
/**
 * 文本插入demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);

$fileObj->fileName("insertText.xlsx")
    ->header(['name', 'money']);

for ($index = 0; $index < 10; $index++) {
    $fileObj->insertText($index+1, 0, 'viest');
    $fileObj->insertText($index+1, 1, 10000, '#,##0'); // #,##0 为单元格数据样式
}

$fileObj->output();
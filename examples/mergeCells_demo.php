<?php
/**
 * 合并单元格demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);
$fileObj->fileName("mergeCells.xlsx")
    ->mergeCells('A1:C1', 'Merge cells')
    ->output();
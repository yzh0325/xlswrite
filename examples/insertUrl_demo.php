<?php
/**
 * 插入超链接demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');
use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);

$fileObj->fileName("insertUrl.xlsx");
$fileObj->insertUrl(1, 0, 'https://github.com', ['underline'=>Pxlswrite::UNDERLINE_SINGLE,'fontColor'=>Pxlswrite::COLOR_GREEN]);
$fileObj->insertText(1, 0, 'View');

$fileObj->output();
<?php
require_once(__DIR__.'/../vendor/autoload.php');

$dir = __DIR__.'/uploads';
$filePath = $_GET['file'];
//$fileName = end(explode('/',$filePath));
$fileObj = new \Pxlswrite\Pxlswrite(['path'=>$dir]);
$fileObj->download($filePath);
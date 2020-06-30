<?php
/**
 * 单元格自适应demo
 */
require_once(__DIR__ . '/../vendor/autoload.php');

use Pxlswrite\Pxlswrite;

$fileObj = new Pxlswrite(['path' => __DIR__ . '/uploads']);
$fileObj->fileName("setAutoSize.xlsx")
    ->field([
        'username' => ['name' => '用户名'],
        'age' => ['name' => '年龄'],
    ])
    ->setGeneralData(function () {
        yield [
            ['username' => '焚膏继晷焚膏继晷', 'age' => 15],
            ['username' => '演员', 'age' => 15],
        ];
    })
    ->setAutoSize(['A'])//单元格自适应列宽
    ->output();
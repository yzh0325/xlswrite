# xlswriter+yield+websocket
这是一个基于xlswriter+yield高性能操作excel的库，集成了websocket用于excel操作时的进度推送。
##Installing
```
composer require yzh0325/xlswrite
```
##安装xlswriter扩展 
* php>=7.0
* windows环境下 php>=7.2 ,xlswriter版本号大于等于 1.3.4.1
```
pecl install xlswriter
```
##WebSocket 
* 服务端采用swoole搭建，需要安装swoole扩展 swoole>=4.4.*,需在cli模式下运行
* 客户端采用textalk/websocket的client作为websocket客户端,可在php-fpm模式下运行
##examples
见 examples/ 
* xlswrite_demo.html 前端demo
* export_demo.php excel导出demo
* import_demo.php excel导入demo

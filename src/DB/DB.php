<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * Date: 2019/10/21
 * Time: 21:34
 */

namespace Pxlswrite\DB;

/**
 * moodle数据库
 * Class Model
 * @package DB
 */
class DB extends DataBase
{
    protected static $instance;

    public function __construct()
    {
        parent::__construct('127.0.0.1','test','root','123456');
    }

    /**
     * 获取单例
     * @return DB
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}
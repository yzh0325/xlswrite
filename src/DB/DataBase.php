<?php
/**
 * Created by PHPStrom.
 * User: Yan
 * Date: 2019/11/15
 * Time: 11:03
 */

namespace Pxlswrite\DB;


use PDO;
use PDOException;
use Traversable;

abstract class DataBase
{
    protected $pdo;
    protected $_transTimes = 0;
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct($host, $dbname, $username, $userpass)
    {
        try {
            $this->pdo = new PDO('mysql:host=' . $host . ';dbname=' . $dbname, $username, $userpass, $this->params);
            $this->pdo->exec("set names utf8");
//            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            info("连接'{$host}'数据库失败", [$e]);
            throw new \Exception('数据库连接失败');
        }
    }

    /**
     * 获取单例
     * @return Model
     * @throws \Exception
     */
    abstract public static function getInstance();


    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction()
    {
        ++$this->_transTimes;
        if ($this->_transTimes == 1) {
            return $this->pdo->beginTransaction();
        }

        $this->pdo->exec('SAVEPOINT trans' . $this->_transTimes);
        return $this->_transTimes >= 0;
//        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit()
    {
        --$this->_transTimes;
        if ($this->_transTimes == 0) {
            return $this->pdo->commit();
        }

        return $this->_transTimes >= 0;
//        $this->pdo->commit();
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollBack()
    {
        --$this->_transTimes;
        if ($this->_transTimes == 0) {
            return $this->pdo->rollBack();
        }
        $transTimes = $this->_transTimes + 1;
        $this->pdo->exec('ROLLBACK TO trans' . $transTimes);
        return true;
//        $this->pdo->rollBack();
    }

    /**
     * 查询
     * @param $sql
     * @param string $paginate
     * @return array
     * @throws \Exception
     */
    public function select($sql, $paginate = "")
    {
        try {
            if (!empty($paginate)) {
                $pageSize = $paginate['pageSize'];   // 每页显示数量
                //第几页
                if (isset($paginate["page"])) {
                    $page = $paginate["page"];
                } else {
                    $page = 1;
                };
                $start_from = ($page - 1) * $pageSize;
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $total = $stmt->rowCount();
                $sql .= " LIMIT " . $start_from . "," . $pageSize;
            }
            if (is_array($sql)) {
                foreach ($sql as $k => $v) {
                    $stmt = $this->pdo->prepare($v);
                    $stmt->execute();
                    $data[$k] = $this->fetchAll($stmt);
                }
            } else {
                // 执行传入的sql语句
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                // 解析结果
                // 0=>xxx,1=>xxx,2=>xx
                $data = $this->fetchAll($stmt);
            }
        } catch (\Exception $exception) {
            info($exception->getMessage(), [$exception->getTraceAsString()]);
            if (ini_get("display_errors")) {
                throw $exception;
            } else {
                throw new \Exception("数据库读取异常");
            }
        }

        // 返回
        if (!empty($paginate)) return array('data' => $data, 'total' => $total, 'sql' => $sql);
        return $data;
    }

    /**
     * 插入
     * @param $sql
     * @return string
     */
    public function insert($sql)
    {
        if (is_array($sql)) {
            foreach ($sql as $k => $v) {
                $stmt = $this->pdo->prepare($v);
                $stmt->execute();
            }
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * 更新
     * @param $sql
     * @return int
     */
    public function update($sql)
    {
        if (is_array($sql)) {
            foreach ($sql as $k => $v) {
                $stmt = $this->pdo->prepare($v);
                $stmt->execute();
            }
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        return $stmt->rowCount();
    }

    /**
     * 查询一条
     * @param $sql
     * @param int $dataType PDO::FETCH_ASSOC || PDO::FETCH_OBJ
     * @return mixed
     * @throws \Exception
     */
    public function getOne($sql, $dataType = PDO::FETCH_OBJ)
    {
        return $this->query($sql, null, $dataType)[0];

    }

    /**
     * 查询所有
     * @param $sql
     * @param int $dataType
     * @return mixed
     * @throws \Exception
     */
    public function getAll($sql, $dataType = PDO::FETCH_OBJ)
    {
        return $this->query($sql, null, $dataType);
    }

    /**
     *
     * @param $table
     * @param array|null $conditions
     * @param string $fields
     * @return mixed
     * @throws \Exception
     */
    public function get_record($table, array $conditions = null, $fields = '*')
    {
        list($select, $params) = $this->where_clause($conditions);
        return $this->get_record_select($table, $select, $params, $fields);
    }

    /**
     * 获取多条
     * @param $table
     * @param array|null $conditions
     * @param string $fields
     * @param string $groupby
     * @param string $sort
     * @param string $limit
     * @param int $offset
     * @return mixed
     * @throws \Exception
     */
    public function get_records($table, array $conditions = null, $fields = '*', $groupby = '', $sort = '', $limit = '', $offset = 0)
    {
        list($select, $params) = $this->where_clause($conditions);
        return $this->get_records_select($table, $select, $params, $fields, $groupby, $sort, $limit, $offset);
    }

    /**
     * @param $table
     * @param $select
     * @param array|null $params
     * @param string $fields
     * @return mixed
     * @throws \Exception
     */
    public function get_record_select($table, $select, array $params = null, $fields = '*')
    {
        if ($select) {
            $select = "WHERE $select";
        }
        return $this->get_record_sql("SELECT $fields FROM  $table $select", $params);
    }

    /**
     * @param $table
     * @param $select
     * @param array|null $params
     * @param string $fields
     * @param string $groupby
     * @param string $sort
     * @param string $limit
     * @param int $offset
     * @return mixed
     * @throws \Exception
     */
    public function get_records_select($table, $select, array $params = null, $fields = '*', $groupby = '', $sort = '', $limit = '', $offset = 0)
    {
        if ($select) {
            $select = "WHERE $select";
        }
        if ($sort) {
            $sort = "ORDER BY $sort";
        }
        if ($groupby) {
            $groupby = "GROUP BY $groupby";
        }
        if ($limit) {
            if ($offset) {
                $limit = "limit $offset,$limit";
            } else {
                $limit = "limit $limit";
            }
        }

        return $this->get_records_sql("SELECT $fields FROM $table $select $groupby $sort $limit", $params);
    }

    /**
     * @param $sql
     * @param array|null $params
     * @param int $dataType
     * @return mixed
     * @throws \Exception
     */
    public function get_record_sql($sql, array $params = null, $dataType = PDO::FETCH_OBJ)
    {
        $records = $this->get_records_sql($sql, $params, $dataType);
        return reset($records);
    }

    /**
     * @param $sql
     * @param array|null $params
     * @param int $dataType
     * @return mixed
     * @throws \Exception
     */
    public function get_records_sql($sql, array $params = null, $dataType = PDO::FETCH_OBJ)
    {
        return $this->query($sql, $params, $dataType);
    }

    /**
     * 插入多条
     * @param $table
     * @param $dataobjects
     * @throws \Exception
     */
    public function insert_records($table, $dataobjects)
    {
        if (!is_array($dataobjects) and !($dataobjects instanceof Traversable)) {
            throw new \Exception('insert_records（）传递了不可遍历的对象');
        }

        $fields = null;
        try {
            $this->beginTransaction();
            foreach ($dataobjects as $dataobject) {
                if (!is_array($dataobject) and !is_object($dataobject)) {
                    throw new \Exception('insert_records（）传递了不可遍历的对象');
                }
                $dataobject = (array)$dataobject;
                if ($fields === null) {
                    $fields = array_keys($dataobject);
                } else if ($fields !== array_keys($dataobject)) {
                    throw new \Exception('insert_records（）中的所有数据对象必须具有相同的结构！');
                }
                $this->insert_record($table, $dataobject);
            }
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw new \Exception($exception->getMessage());
        }

    }

    /**
     * 插入一条
     * @param $table
     * @param $dataobject
     * @return int|string
     * @throws \Exception
     */
    public function insert_record($table, $dataobject)
    {
        list($sql, $params) = $this->deal_insert_sql($table, $dataobject);
        return $this->execute($sql, $params);
    }

    /**
     * 处理插入的sql
     * @param $table
     * @param $dataobject
     * @return array
     * @throws \Exception
     */
    public function deal_insert_sql($table, $dataobject)
    {
        if (!is_array($dataobject) and !is_object($dataobject)) {
            throw new \Exception('insert_record（）传递了不可遍历的对象');
        }
        $dataobject = (array)$dataobject;
        $dataobject = $this->filterField($table, $dataobject);
        $fields = array_keys($dataobject);
        $field = '`' . join('`,`', $fields) . '`';

        $values = array_values($dataobject);
        $count = count($values);
        $val = [];
        $val = array_pad($val, $count, '?');
        $value = join(',', $val);

        $sql = "insert into $table($field) values($value)";
        return array($sql, $values);
    }

    /**
     * 更新
     * @param $table
     * @param $dataobject
     * @param array|null $where
     * @return mixed
     * @throws \Exception
     */
    public function update_record($table, $dataobject, array $where = null)
    {
        if (!is_array($dataobject) and !is_object($dataobject)) {
            throw new \Exception('insert_record（）传递了不可遍历的对象');
        }
        $dataobject = (array)$dataobject;
        $dataobject = $this->filterField($table, $dataobject);
        $fields = array_keys($dataobject);
        $field = [];
        foreach ($fields as $k => $v) {
            $field[] = "`$v` = :$v";
        }
        $field_str = join(',', $field);
        //没有where条件则以对象id为条件
        if (empty($where)) {
            $where = "where id = {$dataobject['id']}";
        } else {
            $conditions = [];
            foreach ($where as $k => $v) {
                $conditions[] = "`$k` = '$v'";
            }
            $where = 'where ' . join(' AND ', $conditions);
        }
        $sql = "UPDATE $table SET $field_str $where";

        return $this->execute($sql, $dataobject);
    }

    /**
     * 删除
     * @param $table
     * @param array $conditions
     * @return int
     * @throws \Exception
     */
    public function delete_records($table, array $conditions)
    {
        list($where, $params) = $this->where_clause($conditions);
        $sql = "DELETE FROM `$table` where $where ";
        return $this->execute($sql, $params);
    }

    /**
     * 过滤掉无用字段
     * @param $table
     * @param array $fields
     * @return array
     */
    protected function filterField($table, array $fields)
    {
        $fields_arr = $this->getFields($table);
        //遍历传递过来的数组 我们才能拿到数组中的键和值
        foreach ($fields as $key => $val) {
            //判断 你的值是否在缓存字段数组中 allFields
            if (!in_array($key, $fields_arr)) {
                unset($fields[$key]);
            }

        }
        return $fields;
    }

    /**
     * 查询字段
     * @param $table
     * @return array
     */
    protected function getFields($table)
    {
        $field = $table . '_fields';
        if (empty($this->$field)) {
            //查看表信息数据库
            $sql = "DESC {$table}";
            //发送数据语句
            $stmt = $this->pdo->query($sql);
            //新建一个数组 用来存储数据库字段
            $result = $this->fetchAll($stmt);
            $fields = array();
            foreach ($result as $val) {
                $fields[] = $val['Field'];
            }
            //缓存字段
            $this->$field = $fields;
        }
        return $this->$field;
    }


    /**
     * @param array|null $conditions
     * @return array
     */
    protected function where_clause(array $conditions = null)
    {
        $conditions = is_null($conditions) ? array() : $conditions;

        if (empty($conditions)) {
            return array('', array());
        }

        $where = array();
        $params = array();
        foreach ($conditions as $k => $v) {
            $where[] = "`$k` = :$k";
            $params[':' . $k] = $v;
        }
        $where = implode(" AND ", $where);
        return array($where, $params);
    }

    /**
     * 执行添加 修改 删除
     * @param $sql
     * @param array|null $params
     * @return int|string
     * @throws \Exception
     */
    public function execute($sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            if (count($params) == count($params, 1)) {
                $stmt->execute($params);
            } else {
                foreach ($params as $param) {
                    $stmt->execute($param);
                }
            }
        } catch (\Exception $exception) {
            var_dump($exception);
            // info($exception->getMessage(),[$exception->getTraceAsString()]);
            if (ini_get("display_errors")) {
                throw $exception;
            } else {
                throw new \Exception("数据库操作异常");
            }
        }
        //如果是添加操作就将添加成功的id返回回去 如果不是添加操作 就返回受影响行
        return $this->pdo->lastInsertId() ? $this->pdo->lastInsertId() : $stmt->rowCount();
    }

    /**
     * 执行查询
     * @param $sql
     * @param array|null $params
     * @param int $dataType
     * @return mixed
     * @throws \Exception
     */
    public function query($sql, array $params = null, $dataType = PDO::FETCH_OBJ)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\Exception $exception) {
            info($exception->getMessage(), [$exception->getTraceAsString()]);
            if (ini_get("display_errors")) {
                throw $exception;
            } else {
                throw new \Exception("数据库读取异常");
            }
        }
//        $stmt->debugDumpParams();
        return $this->fetchAll($stmt, $dataType);
    }

    /**
     * 处理查询结果集
     * @param $stmt
     * @param int $type
     * @return mixed
     */
    protected function fetchAll($stmt, $type = PDO::FETCH_ASSOC)
    {
        return $stmt->fetchAll($type);
    }

    /**
     * 释放pdo连接
     */
    public function __destruct()
    {
        $this->pdo = null;
    }

}
function info($str,$arr){
    $dir = __DIR__.'/log/';
    if(!is_dir($dir)){
        mkdir($dir);
    }
    file_put_contents($dir.date("Y-m-d")."_error.log",$str.json_encode($arr).PHP_EOL,FILE_APPEND);
}
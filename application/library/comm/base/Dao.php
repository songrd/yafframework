<?php
/**
 * Dao.php
 * @desc:
 * @author: songruidong
 * @time: 2017/9/11 16:22
 */

class Comm_Base_Dao
{
    private static $_db = false;
    protected $_fieldArr = array();

    /**
     * 构造函数
     * @param string $db_name
     * @throws Utils_Error
     */
    public function __construct($clusterName='sat_customer')
    {
        if (self::$_db === false) {
            self::$_db = Comm_Db_DbConn::getConn($clusterName);
            if (self::$_db === false) {
                throw new Comm_Exception_Error(Comm_ErrorCodes::DB_CONNECTION_ERROR);
            }
        }
    }

    /**
     * 查询数据
     * @param            $tbl
     * @param            $fields
     * @param null       $conds
     * @param null       $options
     * @param null       $appends
     * @param int        $fetchType
     * @param bool|false $bolUseResult
     * @return mixed      成功：查询结果 失败： 返回false
     * @throws Utils_Error
     */
    public function select($table, $fields = array('*'), $filter = null, $halfSql = '')
    {
        $ret = self::$_db->getList($table, $fields, $filter, $halfSql);
        if ($ret === false) {
            throw new Comm_Exception_Error(Comm_ErrorCodes::DB_QUERY_ERROR);
        }

        return $ret;
    }

    /**
     * 数据库操作方法 参考 Comm_Db 方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array(self::$_db, $name), $arguments);
    }

    /**
     * 魔术方法
     * @param $name
     * @return
     */
    public function __get($name)
    {
        switch($name)
        {
            case 'error':
                return self::$_db->error;
            case 'errno':
                return self::$_db->errno;
            case 'insertId':
                return self::$_db->insert_id;
            case 'affectedRows':
                return self::$_db->affected_rows;
            case 'lastSql':
                return self::$_db->lastSql;
            case 'lastCost':
                return self::$_db->lastCost;
            case 'totalCost':
                return self::$_db->totalCost;
            case 'isConnected':
                return self::$_db->isConnected;
            case 'db':
                return self::$_db->mysql;
            default:
                return NULL;
        }
    }
}
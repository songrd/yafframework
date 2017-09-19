<?php
/**
 * Mysqli.php
 * @desc: mysqli
 * @author: songruidong
 * @time: 2017/9/12 11:44
 */

class Comm_Db_Mysqli extends Comm_Db implements Comm_Db_Interface
{
    /**
     * Comm_Db_Mysqli constructor.
     */
    public function __construct()
    {
        $this->mysql = mysqli_init();
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 设置mysql连接选项
     * @param $optName 选项名字
     * @param $value   选项值
     * @return
     */
    public function setOption($optName, $value)
    {
        return $this->mysql->options($optName, $value);
    }

    /**
     * @brief 设置连接超时
     *
     * @param $seconds 超时时间
     *
     * @return
     */
    public function setConnectTimeOut($seconds)
    {
        if($seconds <= 0) {
            return false;
        }
        if (defined('MYSQLI_OPT_CONNECT_TIMEOUT_US')) {
            return $this->setOption(MYSQLI_OPT_CONNECT_TIMEOUT_US, ceil($seconds * 1000000));
        } else {
            return $this->setOption(MYSQLI_OPT_CONNECT_TIMEOUT, ceil($seconds));
        }
    }

    /**
     * @brief 设置读超时
     * @param $seconds 超时时间
     * @return
     */
    public function setReadTimeOut($seconds)
    {
        if($seconds <= 0) {
            return false;
        }
        if (defined('MYSQLI_OPT_READ_TIMEOUT_US')) {
            return $this->setOption(MYSQLI_OPT_READ_TIMEOUT_US, ceil($seconds * 1000000));
        } else {
            return $this->setOption(MYSQLI_OPT_READ_TIMEOUT, ceil($seconds));
        }
    }

    /**
     * @brief 设置写超时
     * @param $seconds 超时时间
     * @return
     */
    public function setWriteTimeOut($seconds)
    {
        if($seconds <= 0) {
            return false;
        }
        if (defined('MYSQLI_OPT_WRITE_TIMEOUT_US')) {
            return $this->setOption(MYSQLI_OPT_WRITE_TIMEOUT_US, ceil($seconds * 1000000));
        } else {
            return $this->setOption(MYSQLI_OPT_WRITE_TIMEOUT, ceil($seconds));
        }
    }

    /**
     * @brief 连接方法
     *
     * @param $host 主机
     * @param $uname 用户名
     * @param $passwd 密码
     * @param $dbname 数据库名
     * @param $port 端口
     * @param $flags 连接选项
     *
     * @return true：成功；false：失败
     */
    public function connect($host, $uname = null, $passwd = null, $dbname = null, $port = null, $flags = 0)
    {
        $port = intval($port);
        if(!$port) {
            $port = 3306;
        }

        $this->dbConf = array(
            'host'      => $host,
            'port'      => $port,
            'uname'     => $uname,
            'passwd'    => $passwd,
            'flags'     => $flags,
            'dbname'    => $dbname,
        );

        $this->isConnected = $this->mysql->real_connect(
            $host, $uname, $passwd, $dbname, $port, NULL, $flags
        );

        if(!$this->isConnected) {
            // 记录日志
            Comm_Log::warning($this->mysql->error, $this->mysql->errno, $this->dbConf);
        }

        return $this->isConnected;
    }

    /**
     * @brief 关闭连接
     * @return
     */
    public function close()
    {
        if(!$this->isConnected) {
            return;
        }
        $this->isConnected = false;
        $this->mysql->close();
    }

    /**
     * @brief 是否连接，注意，此时mysqli.reconnect需要被关闭
     * @param $bolCheck
     * @return
     */
    public function isConnected($bolCheck = false)
    {
        if($this->isConnected && $bolCheck && !$this->mysql->ping()) {
            $this->isConnected = false;
        }
        return $this->isConnected;
    }

    /**
     * @brief 设置和查询当前连接的字符集
     *
     * @param $name NULL表示查询，字符串表示设置
     *
     * @return
     */
    public function charset($name = NULL)
    {
        if($name === NULL)
        {
            return $this->mysql->character_set_name();
        }
        return $this->mysql->set_charset($name);
    }

    /**
     * @brief 获取连接参数
     *
     * @return
     */
    public function getConnConf()
    {
        if($this->dbConf == NULL)
        {
            return NULL;
        }

        return array(
            'host'   => $this->dbConf['host'],
            'port'   => $this->dbConf['port'],
            'uname'  => $this->dbConf['uname'],
            'dbname' => $this->dbConf['dbname']
        );
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
                return $this->mysql->error;
            case 'errno':
                return $this->mysql->errno;
            case 'insertId':
                return $this->mysql->insert_id;
            case 'affectedRows':
                return $this->mysql->affected_rows;
            case 'lastSql':
                return $this->lastSql;
            case 'lastCost':
                return $this->lastCost;
            case 'totalCost':
                return $this->totalCost;
            case 'isConnected':
                return $this->isConnected;
            case 'db':
                return $this->mysql;
            default:
                return NULL;
        }
    }

    /**
     * 执行sql语句，直接返回sql语句影响的记录数
     * @return int
     * @see Comm_Db_Interface::exec()
     */
    public function exec()
    {
        $stmt = $this->query(func_get_args());
        return $stmt->affected_rows;
    }

    /**
     * 获取第一个字段的值
     * @return string 第1个字段的值
     * @see Comm_Db_Interface::getOne()
     */
    public function getOne()
    {
        $stmt = $this->query(func_get_args(), 1);

        $result = '';
        $stmt->store_result();
        $stmt->bind_result($result);
        $stmt->fetch();
        $stmt->free_result();
        return $result;
    }

    /**
     * 获取一列记录
     * @return array 1维数组，指定字段组成
     * @see Comm_Db_Interface::getCol()
     */
    public function getCol()
    {
        $stmt = $this->query(func_get_args());

        $result = array();
        $stmt->store_result();
        $stmt->bind_result($result);
        $out = array();
        while ($stmt->fetch()) {
            $out[] = $result;
        }
        $stmt->free_result();
        return $out;
    }

    /**
     * 获取所有记录
     * @return array 2维数组
     * @see Comm_Db_Interface::getAll()
     */
    public function getAll()
    {
        $stmt = $this->query(func_get_args());

        $result = $rowData = array();

        $bindResult = $this->_getResultFields($stmt, $rowData);

        $stmt->store_result();
        call_user_func_array(array($stmt, 'bind_result'), $bindResult);

        while ($stmt->fetch()) {
            // rowData is references
            $rowValue = array();
            foreach ($rowData as $k=>$v) {
                $rowValue[$k] = $v;
            }
            $result[] = $rowValue;
        }
        $stmt->free_result();

        return $result;
    }

    /**
     * 获取一行记录
     * @return array 1维数组
     * @see Comm_Db_Interface::getRow()
     */
    public function getRow()
    {
        $stmt = $this->query(func_get_args(), 1);

        $rowData = array();

        $bindResult = $this->_getResultFields($stmt, $rowData);

        $stmt->store_result();
        call_user_func_array(array($stmt, 'bind_result'), $bindResult);

        if ($stmt->fetch()) {
            $stmt->free_result();
            return $rowData;
        }

        return array();
    }

    /**
     * 返回上次插入的id
     * @return int
     * @see Comm_Db_Interface::lastInsertId()
     */
    public function lastInsertId()
    {
        return $this->mysql->insert_id;
    }

    /**
     * 获取查询结果的Fields信息
     * @param object $stmt Mysqli STMT Object
     * @param array $rowData 引用的每行数据信息
     * @return array 返回供$stmt::bind_result绑定结果集的参数数组
     */
    private function _getResultFields($stmt, &$rowData)
    {
        $args = func_get_args();
        $result = $stmt->result_metadata();

        // 错误处理
        if (!$result) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, array_merge($args, ["Mysqli::_getResultFields() Error!; mysqli_errno:{$stmt->errno}; mysqli_error:{$stmt->error}"]), 'warning');
        }

        $fields = $result->fetch_fields();
        $bindResult = array();
        foreach ($fields as $v) {
            $bindResult[] = &$rowData[$v->name];
        }
        return $bindResult;
    }

    /**
     * 事务开始
     */
    public function beginTransaction()
    {
        $this->mysql->autocommit(false);
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        $this->mysql->commit();
        $this->mysql->autocommit(true);
    }

    /**
     * 事务回滚
     */
    public function rollBack()
    {
        $this->mysql->rollback();
        $this->mysql->autocommit(true);
    }

    /**
     * 执行sql查询
     * @param mixed $params
     * @param int $fixlimit 是否要在sql后面自动补充limit 1
     * @return resource
     * @see Comm_Db_Interface::queryExe()
     */
    public function queryExe($params, $fixlimit = 0)
    {
        $args = func_get_args();

        if (is_array($params)) {
            $sql = array_shift($params);
            if (isset($params[0]) && is_array($params[0])) {
                $params = $params[0];
            }
        } else {
            $sql = $params;
            unset($params);
        }

        if ($fixlimit && stripos($sql, 'limit') === false) {
            $sql .= ' LIMIT 1';
        }

        $params_arr = array();
        if (isset($params) && is_array($params) && strpos($sql, '?')) {
            $params_arr = $params;
            $s = str_repeat('s', count($params_arr));
            array_unshift($params_arr, $s);
        }

        // 最后操作的sql
        $this->lastSql = $params ? $this->bindParam($sql, $params) : $sql;

        // add DEBUG
        if (defined('D_BUG') && D_BUG) {
            global $g;
            if (!$g['sql']) $g['sql'] = array();
            $explain = array();
            $info = '';

            $sql_info = $params ? "\n <br />" . var_export($params, true) : NULL;
            $sql_real = $params ? $this->bindParam($sql, $params) : $sql;

            if (strncasecmp($sql, 'SELECT ', 7) == 0 && strpos($sql, '?')) {
                $stmt = $this->mysql->prepare("EXPLAIN $sql");
                if ($params_arr && strpos($sql, '?')) {
                    call_user_func_array(array($stmt, 'bind_param'), $params_arr);
                }
                $stmt->execute();

                $bindResult = $this->_getResultFields($stmt, $explain);

                $stmt->store_result();
                call_user_func_array(array($stmt, 'bind_result'), $bindResult);
                $stmt->fetch();
                $stmt->free_result();
            }

            $sql_debug_info = array(
                'sql'      => $sql,
                'sql_info' => $sql_info,
                'sql_real' => $sql_real,
                'time'     => '',
                'info'     => $info,
                'explain'  => $explain,
                'db'       => "{$this->db_driver} : {$this->db_host} > {$this->db_name}",
            );
            $mtime = explode(' ', microtime());
            $sqlstarttime = $mtime[1] + $mtime[0];
        }

        $beg = intval(microtime(true)*1000000);

        // 预处理
        $stmt = $this->mysql->prepare($sql);
        if (!$stmt) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_QUERY_ERROR, null, array_merge($args, ["Mysqli::queryExe() Error!sql:$sql"]), 'warning');
        }

        //$stmt->bind_param("s", $params_arr[1]);
        if ($params_arr && strpos($sql, '?')) {
            //call_user_func_array(array($stmt, 'bind_param'), $params_arr);
            call_user_func_array(array($stmt, "bind_param"), $this->refValues($params_arr));
        }

        $stmt->execute();

        // record cost
        $this->lastCost = intval(microtime(true)*1000000) - $beg;
        $this->totalCost += $this->lastCost;

        $logStr = __METHOD__ . ", db[{$this->dbConf['dbname']},{$this->dbConf['host']}:{$this->dbConf['port']}], sql[{$this->lastSql}], lastCost[".round($this->lastCost / 1000)."]";
        Comm_Log::notice($logStr, 0);

        if ($stmt->errno) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_QUERY_ERROR, null, array_merge($args, ["mysqliWrapper::query_exe() Error!; mysqli_errno:{$stmt->errno}; mysqli_error:{$stmt->error}; Error sql:$sql"]), 'warning');
        }

        if (defined('D_BUG') && D_BUG) {
            $mtime = explode(' ', microtime());
            $sqltime = number_format(($mtime[1] + $mtime[0] - $sqlstarttime), 6)*1000;
            $sql_debug_info['time'] = $sqltime;
            $g['sql'][] = $sql_debug_info;
        }

        return $stmt;
    }

    /**
     * refValues
     * @param $arr
     * @return array
     */
    public function refValues($arr)
    {
        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }
}
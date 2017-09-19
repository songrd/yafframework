<?php
/**
 * DbComm.php
 * @desc: 数据库连接类
 * @author: songruidong
 * @time: 2017/9/11 22:46
 */

define('LOAD_CONF_ERROR',10001);
define('MYSQL_FLAGS_ERROR',10002);
define('LOAD_CLASS_ERROR',10003);
define('SET_HOSTS_ERROR',10004);
define('SET_HOOK_ERROR',10005);
define('ALL_CONNECT_ERROR',10006);
define('CONNECT_ERROR',10007);
define('CLUSTERNAME_ERROR',10010);

define('INVALID_SQL',10008);
define('QUERY_ERROR',10009);

if (!defined('MYSQLI_OPT_READ_TIMEOUT')) {
    define('MYSQLI_OPT_READ_TIMEOUT',11);
    define('MYSQLI_OPT_WRITE_TIMEOUT',12);
}

class Comm_Db_DbConn
{
    private static $_clusterObj = array();
    private static $_conf       = null;
    private static $_hosts      = null;
    private static $_hostIndex  = 0;
    private static $_mysql_err  = null;
    private static $_error      = array();

    /**
     * 获取db对象
     * @param $clusterName 集群名称
     * @param $key 负载均衡key
     * @param $getNew 是否重新连接
     * @return
     */
    public static function getConn($clusterName, $key=NULL, $getNew=false)
    {
        if (!empty(self::$_clusterObj[$clusterName]) && $getNew !== false) {
            return self::$_clusterObj[$clusterName];
        }

        if (self::_init($clusterName) === false) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_CONNECTION_ERROR, null, self::$_error, 'warning');
        }

        $class = 'Comm_Db_' . self::$_conf['connect_file'];

        if (!class_exists($class)) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_CONNECTION_ERROR, null, ["mysql {$clusterName} connect file {$class} empty"], 'warning');
        }

        $dbObj = new $class();
        $pid = posix_getpid();

        for($i = 1; $i <= self::$_conf['retry_times']; $i++) {
            $timeout   = self::$_conf['connect_timeout_ms'];
            $r_timeout = self::$_conf['read_timeout_ms'];
            $w_timeout = self::$_conf['write_timeout_ms'];
            $dbObj->setReadTimeOut($r_timeout / 1000.0);
            $dbObj->setConnectTimeOut($timeout / 1000.0);
            $dbObj->setWriteTimeOut($w_timeout / 1000.0);

            $start  = microtime(true) * 1000;
            //connect
            $ret    = $dbObj->connect(
                self::$_hosts['valid_hosts'][self::$_hostIndex]['ip'],
                self::$_conf['username'],
                self::$_conf['password'],
                self::$_conf['default_db'],
                self::$_hosts['valid_hosts'][self::$_hostIndex]['port'],
                self::$_conf['connect_flag']
            );
            $end     = microtime(true)*1000;
            $cost_ms = $end - $start;
            if ($ret) {
                if (empty($conf['charset']) || $dbObj->charset($conf['charset'])) {
                    $logPara['time_ms'] = $cost_ms;
                    $logPara['pid']     = $pid;
                    Comm_Log::trace('Connect to Mysql successfully', 0, $logPara);
                    $lastDb[$clusterName] = $dbObj;
                    return $lastDb[$clusterName];
                } else {
                    throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_SET_OPTION_ERR, null, ["mysql {$clusterName} [pid=$pid] Set charset failed"], 'warning');
                }
            }
        }

        throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_CONNECTION_ERROR, null, ["mysql {$clusterName} connect err"], 'warning');
        return false;
    }

    /**
     * 初始化
     * @static
     * @param $clusterName
     * @return bool
     */
    private static function _init($clusterName)
    {
        return self::_initConf($clusterName);
    }

    /**
     * 初始化配置文件
     * @static
     * @param $clusterName
     * @return bool
     */
    private static function _initConf($clusterName)
    {
        $confList    = Comm_Conf::get("db/cluster");
        self::$_conf = $confList[$clusterName];
        if(self::$_conf  === false || $clusterName == '') {
            self::$_error['errno'] = CLUSTERNAME_ERROR;
            self::$_error['error'] = 'Cannot find matched cluster:'.$clusterName.' in configure file, please check';
            return false;
        }

        //initialize args which use default value
        $default_conf = array(
            'retry_interval_s'   => '',   //当某台机器被标为故障后，将其封禁的时间，单位是秒
            'balance_strategy'   => '',   // 负载均衡选择器类名，留空表示使用默认的随机选择器
            'connect_timeout_ms' => Intval(self::$_conf['connect_timeout_ms']),
            'read_timeout_ms'    => Intval(self::$_conf['read_timeout_ms']),
            'write_timeout_ms'   => Intval(self::$_conf['write_timeout_ms']),
            'retry_times'        => 1,
            'charset'            => 'utf-8',
        );
        self::$_conf = array_merge($default_conf, self::$_conf);

        // 判断host
        if(!array_key_exists('host',self::$_conf)) {
            self::$_error['errno'] = SET_HOSTS_ERROR;
            self::$_error['error'] = 'No host was setted for cluster:'.$clusterName.' in configure file';
            return false;
        }
        self::$_hosts['valid_hosts'] = self::$_conf['host'];
        unset(self::$_hosts['failed_hosts']);

        self::_initHostIndex(self::$_hosts['valid_hosts']);

        return true;
    }

    /**
     * 初始化使用哪个host
     * @static
     * @param $hosts
     * @return bool
     */
    private static function _initHostIndex($hosts)
    {
        if (!is_array($hosts)) {
            self::$_hostIndex = 0;
            return true;
        }

        $hostsTotal = count($hosts);

        self::$_hostIndex = mt_rand(0, $hostsTotal-1);
        return true;
    }
}
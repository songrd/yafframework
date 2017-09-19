<?php
/**
 * @desc Redis 封装类
 *
 */
class Comm_Redis {
    private static $_objList = array();
    private static $_config = array();
    private static $_retry = array();
    //当前设置集群名称
    private $_cluster;

    /**
     * 构造方法
     *
     * @param string $cluster
     */
    public function __construct($cluster) {
        $this->setCluster($cluster);
    }

    /**
     * 获取一个redis的实例
     *
     * @param string $cluster
     * @return object
     */
    private static function getInstance($cluster) {
        if (isset(self::$_objList[$cluster]) && is_object(self::$_objList[$cluster])) {
            return self::$_objList[$cluster];
        }
        self::_loadConfig($cluster);
        if (!self::$_retry[$cluster]) {
            self::$_retry[$cluster] = 1;
        }
        for ($i = 0; $i <= self::$_retry[$cluster]; $i++) {

            try {
                $config = self::$_config[$cluster];
                self::$_objList[$cluster] = self::_init($config);

                return self::$_objList[$cluster];
            } catch (Exception $e) {
                if ($i < self::$_retry[$cluster]) {
                    continue;
                }
                self::warning($e->getCode(),'['.__METHOD__.']'.$e->getMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * 设置配置集群名称
     *
     * @param string $cluster
     */
    public function setCluster($cluster) {
        $this->_cluster = $cluster;
    }

    /**
     * @desc 写缓存
     *
     * @param string $key     key name
     * @param string $value   缓存值
     * @param int    $expire  过期时间, 0:表示无过期时间
     *
     * @return boolean
     */
    public function set($key, $value, $expire = 0) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            if (0 === $expire) {
                $ret = $instance->set($key, $value);
            } else {
                $ret = $instance->setex($key, $expire, $value);
            }
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.'][val:'.$value.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * @desc 设置key过期时间
     *
     * @param string $key     key name
     * @param string $value   缓存值
     * @param int    $expire  过期时间, 0:表示无过期时间
     *
     * @return boolean
     */
    public function expire($key, $expire, $ms = false) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            if ($ms) {
                $ret = $instance->pexpire($key, $expire); //设置毫秒
            } else {
                $ret = $instance->expire($key, $expire);
            }
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * @desc 条件形式设置缓存,如果 key不存时就设置, 存在时设置失败
     *
     * @param string $key     key name
     * @param string $value   缓存值
     *
     * @return boolean
     */
    public function setnx($key, $value, $timeout=0) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            $ret = $instance->setnx($key, $value);
            if ($ret && $timeout > 0) {
                $instance->expire($key,$timeout);
            }
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.'][val:'.$value.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * @desc  删除缓存
     *
     * @param string || array $key key name, 支持单个健:"key1" 或多个健:array('key1','key2')
     *
     * @return int 删除的健的数量
     */
    public function remove($key) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            $ret = $instance->delete($key);
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * @desc 值加加操作,类似 ++$i ,如果 key不存在时自动设置为 0后进行加加操作
     *
     * @param string $key     key name
     * @param int    $step    step by incr
     *
     * @return int　操作后的值
     */
    public function incr($key, $step = 1, $timeout=0) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            if (1 == $step) {
                $ret = $instance->incr($key);
            } else {
                $ret = $instance->incrBy($key, $step);
            }
            if ($timeout > 0) {
                $instance->expire($key,$timeout);
            }
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * @desc 值减减操作,类似 --$i ,如果 key不存在时自动设置为 0后进行减减操作
     *
     * @param string $key     key name
     * @param int    $step    step by decr
     *
     * @return int　操作后的值
     */
    public function decr($key, $step = 1) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            if (1 == $step) {
                $ret = $instance->decr($key);
            } else {
                $ret = $instance->decrBy($key, $step);
            }
            $this->_errCheck($instance);

            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * @desc 读缓存
     *
     * @param string || array $key key name ,支持一次取多个 $key = array('key1','key2')
     *
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function get($key) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            $func = is_array($key) ? 'mGet' : 'get';
            $ret  = $instance->{$func}($key);
            $this->_errCheck($instance);

            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * 加载配置
     *
     * @param string $cluster
     * @return array
     */
    private static function _loadConfig($cluster) {
        if (!self::$_config[$cluster]) {
            $config = Comm_Conf::get("redis/cluster");
            $config = $config[$cluster];
            if (empty($config) || !isset($config['host'])) {
                self::warning(Comm_SysErrorCodes::REDIS_CONFIG_ERR,'redis API配置错误');
                return false;
            }
            // config 示例
            /*
            array (
                'retry_times' => '2',
                'persistent' => '0',
                'timeout' => '1',
                'strategy' => 'rand',
                'host' =>
                    array(
                        'ip' => '127.0.0.1',
                        'port' => '6379',
                    ),
                    array(
                        'ip' => '127.0.0.1',
                        'port' => '6379',
                    ),
            )
            */

            self::$_retry[$cluster] = $config['retry_times'];

            $index = 0;
            if (isset($config['strategy']) && $config['strategy'] == 'rand') {
                $index = array_rand($config['host']);
            }
            self::$_config[$cluster] = array(
                'host' => $config['host'][$index]['ip'],
                'port' => $config['host'][$index]['port'],
                'persistent' => $config['persistent'],
                'timeout'    => $config['timeout'],
                'password'   => $config['password'],
                'database'   => $config['database'] ? $config['database'] : 0,
            );
        }
    }


    /**
     * @desc init redis
     *
     * @param array $config redis config
     *
     * @return boolean or throw exception
     */
    private static function _init($config) {
        if (!class_exists('Redis')) {
            throw new Exception('redis API链接错误',Comm_SysErrorCodes::REDIS_CONNECT_ERR);
            return false;
        }
        $client = new Redis();
        if ($config['persistent']) {
            $connRes = $client->pconnect($config['host'], $config['port'],
                $config['timeout']);
        } else {
            $connRes = $client->connect($config['host'], $config['port'],
                $config['timeout']);
        }
        if (!$connRes) {
            throw new Exception('redis API链接错误',Comm_SysErrorCodes::REDIS_CONNECT_ERR);
        }
        if ($config['password']) {
            $client->auth($config['password']);
        }
        if (isset($config['database']) && is_numeric($config['database'])) {
            $client->select($config['database']);
        }

        return $client;
    }

    /**
     * 错误检测
     *
     * @param string $instance
     * @return bool
     */
    private function _errCheck($instance) {
        $err = $instance->getLastError();
        if (null != $err) {
            self::warning(Comm_SysErrorCodes::REDIS_CHECK_ERR,'redis API访问错误');
            return false;
        }
    }

    /**
     * @desc 读hash缓存
     *
     * @param string $key     获取hash数据的key
     *
     * @return array 返回hash下所有field和value值
     */
    public function hGetAll($key) {
        try {
            $instance = self::getInstance($this->_cluster);
            if (!$instance) {
                return false;
            }
            $ret = $instance->hGetAll($key);
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.__METHOD__.'][key:'.$key.']'.$e->getMessage());
            return false;
        }
    }

    /**
     * pipeline 方法
     *
     */
    public function pipeline() {
        $instance = self::getInstance($this->_cluster);
        $instance->pipeline();
    }

    /**
     * exec 方法
     *
     * @return array
     */
    public function exec() {
        $instance = self::getInstance($this->_cluster);
        $arrRet = $instance->exec();
        return $arrRet;
    }

    /**
     *  @调用方法不存在时，直接调用phpRedis扩展方法
     *  @param $name string 方法名
     *  @param $arguments array 数组
     *  @return array/false
     */
    public function __call($name, $arguments) {
        try {
            $instance = self::getInstance($this->_cluster);
            $ret = call_user_func_array(array($instance,$name), $arguments);
            $this->_errCheck($instance);
            return $ret;
        } catch (Exception $e) {
            self::warning($e->getCode(),'['.$name.'][arg:'.implode(' '.$arguments).']'.$e->getMessage());
            return false;
        }
    }

    /**
     * 记录日志
     *
     * @param int $code
     * @param string $msg
     */
    private static function warning($code,$msg) {
        $code || $code = Comm_SysErrorCodes::REDIS_CALL_EXCEPTION;
        Comm_Log::warning($msg, $code);
    }
}

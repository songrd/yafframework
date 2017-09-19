<?php
/**
 * Request.php
 * @desc: 构造request请求
 * @author: songruidong
 * @time: 2017/9/14 18:10
 */

class Comm_ServiceClient_Request
{
    private $_defaultProtocol = 'http';
    private $_defaultMethod   = 'GET';

    private $config = array();
    private $header = array();
    private $url = null;
    private $method = 'GET';
    private $opt = array(
        'cookie' => '',
        'user_agent' => '',
        'referer'    => '',
    );

    /**
     * 创建一个request请求
     * @static
     * @return Comm_ServiceClient_Request
     */
    public static function createRequest()
    {
        return new self();
    }

    /**
     * 设置request请求api
     * @param $service
     * @param $apiName
     * @param array $opt
     * @param array $header
     */
    public function setApi($service, $apiName, $opt=array(), $header=array())
    {
        $this->_initServiceHost($service);
        $this->url = $this->_getApi($service, $apiName);
        $this->method = $this->_getMethod($service, $opt);
    }

    /**
     * 设置请求参数
     * @param $params
     */
    public function setParam($params)
    {
        $this->params = (array)$params;
    }

    /**
     * 设置header头
     * @param $header
     */
    public function setHeader($header)
    {
        if ($this->config['header']) {
            $this->header = array_unique(array_merge((array)$this->config['header'], (array)$header));
        } else {
            $this->header = (array)$header;
        }
    }

    /**
     * 设置opetion
     * @param $opt
     * @return bool
     */
    public function setOption($opt)
    {
        if (!is_array($opt)) {
            return false;
        }
        foreach ($opt as $key => $value) {
            $this->_setopt($key, $value);
        }
    }

    /**
     * _setopt
     * @param $type
     * @param $value
     */
    private function _setopt($type, $value)
    {
        switch ($type) {
            case 'timeout':
                $this->opt['timeout'] = (int)$value;
                break;
            case 'connect_timeout':
                $this->opt['connect_timeout'] = (int)$value;
                break;
            case 'timeout_ms':
                $this->opt['timeout_ms'] = (int)$value;
                break;
            case 'connect_timeout_ms':
                $this->opt['connect_timeout_ms'] = (int)$value;
                break;
        }
    }

    /**
     * 获取api信息
     * @param $service
     * @param $apiName
     * @return string
     */
    private function _getApi($service, $apiName)
    {
        if (!$this->config) {
            $this->_initServiceHost($service);
        }

        $url = (isset($this->config['protocol']) ? $this->config['protocol'] : $this->_defaultProtocol) . "://";
        $url .= $this->config['host'];
        if (stripos($apiName, "/") !== 0) {
            $url .= "/";
        }
        $url .= $apiName;

        return $url;
    }

    /**
     * 获取请求方式
     * @param $service
     * @param $opt
     * @return string
     */
    private function _getMethod($service, $opt)
    {
        if (isset($opt['method'])) {
            return $opt['method'];
        }
        return $this->_defaultMethod;
    }

    /**
     * 初始化host的配置文件
     * @param $service
     */
    private function _initServiceHost($service)
    {
        $config = Comm_Conf::get("service/$service");
        if (!$config) {
            Comm_Log::fatal('Comm_ServiceClient_Request::_initServiceHost() serivce:'. $service . ' config empty', Comm_SysErrorCodes::SERVICE_CONFIG_EMPTY);
        }

        $this->config = $config;
    }

    /**
     * 魔术方法
     * @param $type
     * @return array
     */
    public function __get($type)
    {
        if (isset($this->$type)) {
            return $this->$type;
        }
        else {
            return array();
        }
    }
}
<?php
/**
 * Controller的上下文。
 * 用来存储一些在请求中共用的数据，以及提供GET/POST参数的简便封装。
 * Request.php
 * @desc: request 类库
 * @author: songruidong
 * @time: 2017/9/8 21:40
 */
class Comm_Request
{
    protected static $_contextData = array();

    /**
     * 转换过滤字符串
     *
     * @param string $string
     * @return string
     */
    protected static  function filterString($string) {
        if($string === null) {
            return false;
        }
        return htmlspecialchars($string, ENT_QUOTES);
    }

    /**
     * 从$_GET中获取指定参数的值
     *
     * @param string $name
     * @param mixed  $default 默认值
     * @param bool	 $filter
     * @return string
     */
    public static function param($name, $default = null, $filter = false) {
        if($filter) {
            return isset($_GET[$name]) ?(self::filterString($_GET[$name])) :$default;
        }
        return isset($_GET[$name]) ?$_GET[$name] :$default;
    }

    /**
     * 从$_POST中获取指定参数的值
     *
     * @param string $name
     * @param mixed $default
     * @return string
     */
    public static function form($name, $default = null, $filter = false){
        if($filter) {
            return isset($_POST[$name]) ?(self::filterString($_POST[$name])) :$default;
        }
        return isset($_POST[$name]) ?$_POST[$name] :$default;
    }


    /**
     * 从requestParams中获取指定参数的值
     *
     * @param string $name
     * @param mixed  $default
     * @param boolean $filter
     * @return string
     */
    public static function request($name, $default = null, $filter = false) {
        $requestParams = self::getRequestParams();
        if($filter) {
            return isset($requestParams[$name]) ?(self::filterString($requestParams[$name])) :$default;
        }
        return isset($requestParams[$name]) ?$requestParams[$name] :$default;
    }

    /**
     * 获取请求的参数数组
     *
     * @param
     * @return array
     */
    public static function getRequestParams() {
        static $requestParams = null;
        if($requestParams === null) {
            $params = Yaf_Dispatcher::getInstance()->getRequest()->getParams();
            $requestGet = $_GET;
            $requestPost = $_POST;
            if(is_array($params) && $params) {
                $requestGet = array_merge($requestGet, $params);
            }
            $requestParams = array_merge($requestGet, $requestPost);
        }
        return $requestParams;
    }

    /**
     * 得到当前请求的环境变量
     *
     * @param string $name
     * @param string $default
     * @return string|null 当$name指定的环境变量不存在时，返回null
     */
    public static function getServer($name, $default = null){
        return isset($_SERVER[$name]) ?$_SERVER[$name] :$default;
    }

    /**
     * 获取请求URI
     *
     * @param
     * @return string
     */
    public static function getRequestUri() {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        return $request->getRequestUri();
    }

    /**
     * 获取当前域名
     *
     * @param
     * @return string
     */
    public static function getDomain(){
        return self::getServer('SERVER_NAME');
    }

    /**
     * 获取http请求方法。
     *
     * @param
     * @return string GET/POST/PUT/DELETE/HEAD等
     */
    public static function getHttpMethod(){
        return self::getServer('REQUEST_METHOD');
    }

    /**
     * 判断当前请求是否是XMLHttpRequest(AJAX)发起
     *
     * @param
     * @return boolean
     */
    public static function isXmlHttpRequest() {
        return (self::getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') ? true : false;
    }

    /**
     * 判断当前请求是否Cli请求
     *
     * @param
     * @return boolean
     */
    public static function isCli() {
        return PHP_SAPI == 'cli';
    }

    /**
     * 获取客户端ip
     *
     * @param boolean $to_long
     * @return string|int
     */
    public static function getClientIp($toLong = false)
    {
        $forwarded = self::getServer('HTTP_X_FORWARDED_FOR');
        if($forwarded){
            $ipChains = explode(',', $forwarded);
            $proxiedClientIp = $ipChains ? trim(array_pop($ipChains)) : '';
        }

        if(self::isPrivateIp(self::getServer('REMOTE_ADDR')) && isset($proxiedClientIp)){
            $realIp = $proxiedClientIp;
        }else{
            $realIp = self::getServer('REMOTE_ADDR');
        }
        return $toLong ? ip2long($realIp) :$realIp;
    }

    /**
     * getUserIp
     * @static
     * @return int|string
     */
    public static function getUserIp()
    {
        $uip = '';
        if(isset($_SERVER['HTTP_X_BD_USERIP']) && $_SERVER['HTTP_X_BD_USERIP'] && strcasecmp($_SERVER['HTTP_X_BD_USERIP'], 'unknown')) {
            $uip = $_SERVER['HTTP_X_BD_USERIP'];
        } else {
            $uip = self::getClientIp();
        }
        return $uip;
    }

    /**
     * getFrontendIp
     * @static
     * @return string
     */
    public static function getFrontendIp()
    {
        if (isset($_SERVER['REMOTE_ADDR']))
            return $_SERVER['REMOTE_ADDR'];
        return '';
    }

    /**
     * getLocalIp
     * @static
     * @return string
     */
    public static function getLocalIp()
    {
        if (isset($_SERVER['SERVER_ADDR']))
            return $_SERVER['SERVER_ADDR'];
        return '';
    }

    /**
     * 判断是否是私有ip
     *
     * @param string $ip
     * @return boolean
     */
    public static function isPrivateIp($ip)
    {
        $ip_value = ip2long($ip);
        return ($ip_value & 0xFF000000) === 0x0A000000 //10.0.0.0-10.255.255.255
            || ($ip_value & 0xFFF00000) === 0xAC100000 //172.16.0.0-172.31.255.255
            || ($ip_value & 0xFFFF0000) === 0xC0A80000 //192.168.0.0-192.168.255.255
            ;
    }
}
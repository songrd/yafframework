<?php
/**
 * Curl.php
 * @desc: curl请求
 * @author: songruidong
 * @time: 2017/9/14 18:10
 */
class Comm_ServiceClient_Curl extends Comm_ServiceClient_CurlLog
{
    const DefaultTimeOut = 2;  //默认接口超时时间
    const DefaultTimOutConn = 1; //默认连接时间

    private static $instance = NULL;
    private $curlHandle = NULL;

    /**
     * 单例模式
     * @static
     * @return Comm_ServiceClient_Curl|null
     */
    public static function instance()
    {
        is_null(self::$instance) && self::$instance = new self();
        return self::$instance;
    }

    /**
     * 打开curl
     */
    public function open()
    {
        $this->curlHandle = curl_init();
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->curlHandle, CURLOPT_HEADER, FALSE);
        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curlHandle, CURLOPT_NOSIGNAL, 1);
    }

    /**
     * 发送请求
     * @param $request
     */
    public function send($request)
    {
        $params = http_build_query($request->params);
        $url = $request->url;
        $method = $request->method;
        $method = empty($method) ? 'GET' : $method;
        $method = strtolower($method);
        switch ($method) {
            case 'post':
                curl_setopt($this->curlHandle, CURLOPT_POST, TRUE);
                curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $params);
                break;
            case 'get':
                curl_setopt($this->curlHandle, CURLOPT_HTTPGET, TRUE);
                $url .= '?' . $params;
                break;
        }
        curl_setopt($this->curlHandle, CURLOPT_URL, $url);

        // 设置参数
        $this->setopt($request);

        // 设置header
        $this->setHeader($request);
    }

    /**
     * 执行请求
     * @return array
     */
    public function exec()
    {
        $response = curl_exec($this->curlHandle);
        parent::wlog($this->curlHandle);
        $res = array();
        $res['http_code']   = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        $res['curl_info']   = curl_getinfo($this->curlHandle);
        $res['content']     = json_decode($response);
        return $res;
    }

    /**
     * 关闭
     */
    public function close()
    {
        curl_close($this->curlHandle);
        $this->curlHandle = NULL;
        //return $requestInfo;
    }

    /**
     * 设置ua
     * @param $value
     */
    private function setUserAgent($value)
    {
        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, $value);
    }

    /**
     * 设置cookie
     * @param $value
     */
    private function setCookie($value)
    {
        curl_setopt($this->curlHandle, CURLOPT_COOKIE, $value);
    }

    /**
     * 设置referer
     * @param $value
     */
    private function setReferer($value)
    {
        if ($value) {
            curl_setopt($this->curlHandle, CURLOPT_REFERER, $value);
        } else {
            // 设置referer
            if(isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI'])) {
                $referer = $_SERVER['SERVER_NAME'] . str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
                curl_setopt($this->curlHandle, CURLOPT_REFERER, $referer);
            }
        }
    }

    /**
     * 设置header
     * @param $request
     */
    private function setHeader($request)
    {
        $headerArr = (array)$request->header;
        $headerArr[] = 'LogId:' . Comm_Log::genLogID();
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headerArr);
    }

    /**
     * 设置参数
     * @param $request
     */
    private function setopt($request)
    {
        $options = $request->opt;

        if (empty($options['timeout'])) {
            $options['timeout'] = self::DefaultTimeOut;
        }
        if (!empty($options['timeout_ms'])) {
            unset($options['timeout']);
        }

        if (empty($options['connect_timeout'])) {
            $options['connect_timeout'] = self::DefaultTimOutConn;
        }
        if (!empty($options['connect_timeout_ms'])) {
            unset($options['connect_timeout']);
        }
        foreach ($options as $type => $value) {
            switch ($type) {
                case 'timeout':
                    curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $value);
                    break;
                case 'connect_timeout':
                    curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT, $value);
                    break;
                case 'timeout_ms':
                    curl_setopt($this->curlHandle, CURLOPT_TIMEOUT_MS, $value);
                    break;
                case 'connect_timeout_ms':
                    curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT_MS, $value);
                    break;
                case 'user_agent':
                    $this->setUserAgent($value);
                    break;
                case 'referer':
                    $this->setReferer($value);
                    break;
                case 'cookie':
                    $this->setCookie($value);
                    break;
            }
        }
    }
}
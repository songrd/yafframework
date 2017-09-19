<?php
/**
 * MultiCurl.php
 * @desc: 并发请求
 * @author: songruidong
 * @time: 2017/9/14 18:10
 */

class Comm_Service_MultiCurl extends Comm_Service_CurlLog
{

    const DefaultTimeOut = 2;  //默认接口超时时间
    const DefaultTimOutConn = 1; //默认连接时间

    private static $instance = null;
    private $requestMap = array();
    private $curlMultiHandle = null;

    private $file = "mutilcurl";

    /**
     * 单例模式
     * @static
     * @return Comm_Service_Curl|null
     */
    public static function instance()
    {
        is_null(self::$instance) && self::$instance = new self();
        return self::$instance;
    }

    /**
     * open
     */
    public function open()
    {
        $this->curlMultiHandle = curl_multi_init();
    }

    /**
     * 发送请求
     * @param $requests
     * @return null
     */
    public function send($requests)
    {
        if (!is_array($requests) || empty($requests)) {
            return null;
        }

        foreach ($requests as $key => $request) {
            $curlHandle = $this->initSingleCutl();
            $curlHandle = $this->setOpt($curlHandle, $request);
            $curlHandle = $this->setUrl($curlHandle, $request);
            $curlHandle = $this->setHeader($curlHandle, $request);
            curl_multi_add_handle($this->curlMultiHandle, $curlHandle);
            $this->requestMap[(string) $curlHandle] = $key;
        }
    }

    /**
     * 初始化curl
     * @return resource
     */
    private function initSingleCutl()
    {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlHandle, CURLOPT_HEADER, FALSE);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandle, CURLOPT_NOSIGNAL, 1);

        return $curlHandle;
    }

    /**
     * 设置header
     * @param $curlHandle
     * @param $request
     * @return mixed
     */
    private function setHeader($curlHandle, $request)
    {
        $headerArr = (array)$request->header;
        $headerArr[] = 'LogId:' . Comm_Log::genLogID();
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headerArr);

        return $curlHandle;
    }


    /**
     * 设置参数
     * @param $request
     */
    private function setopt($curlHandle, $request)
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
                    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $value);
                    break;
                case 'connect_timeout':
                    curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, $value);
                    break;
                case 'timeout_ms':
                    curl_setopt($curlHandle, CURLOPT_TIMEOUT_MS, $value);
                    break;
                case 'connect_timeout_ms':
                    curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT_MS, $value);
                    break;
                case 'user_agent':
                    curl_setopt($curlHandle, CURLOPT_USERAGENT, $value);
                    break;
                case 'cookie':
                    curl_setopt($curlHandle, CURLOPT_COOKIE, $value);
                    break;
                case 'referer':
                    if ($value) {
                        curl_setopt($curlHandle, CURLOPT_REFERER, $value);
                    } else {
                        if(isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI'])) {
                            $referer = $_SERVER['SERVER_NAME'] . str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
                            curl_setopt($curlHandle, CURLOPT_REFERER, $referer);
                        }
                    }
                    break;
            }
        }

        return $curlHandle;
    }

    /**
     * 设置url
     * @param $curlHandle
     * @param $request
     * @return mixed
     */
    private function setUrl($curlHandle, $request)
    {
        $params = http_build_query($request->params);
        $url = $request->url;
        $method = $request->method;
        $method = empty($method) ? 'GET' : $method;
        $method = strtoupper($method);
        switch ($method) {
            case 'POST':
                curl_setopt($curlHandle, CURLOPT_POST, TRUE);
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $params);
                break;
            case 'GET':
                curl_setopt($curlHandle, CURLOPT_HTTPGET, TRUE);
                $url .= '?' . $params;
                break;
        }
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        return $curlHandle;
    }

    /**
     * 执行
     * @return mixed
     */
    public function exec()
    {
        $response = array();
        $active = null;
        do {
            do {
                $status = curl_multi_exec($this->curlMultiHandle, $active);
            } while ($status == CURLM_CALL_MULTI_PERFORM);
            if ($status != CURLM_OK) {
                break;
            }
            while ($respond = curl_multi_info_read($this->curlMultiHandle)) {
                $responses[$this->requestMap[(string) $respond['handle']]]['content'] = curl_multi_getcontent($respond['handle']);
                $responses[$this->requestMap[(string) $respond['handle']]]['curl_info'] = curl_getinfo($respond['handle']);
                $responses[$this->requestMap[(string) $respond['handle']]]['http_code'] = curl_getinfo($respond['handle'], CURLINFO_HTTP_CODE);
                parent::wlog($respond['handle'], $this->file);

                curl_multi_remove_handle($this->curlMultiHandle, $respond['handle']);
                curl_close($respond['handle']);
            }
            if ($active > 0) {
                curl_multi_select($this->curlMultiHandle, 0.05);
            }
        } while ($active);

        return $responses;
    }

    /**
     * close
     */
    public function close()
    {
        curl_multi_close($this->curlMultiHandle);
        $this->curlMultiHandle = NULL;
        $this->requestMap = array();
    }
}
<?php
/**
 * Client.php
 * @desc: 网络请求客户端
 * @author: songruidong
 * @time: 2017/9/14 18:09
 */

class Comm_Service_Client
{
    private $requestList = array();
    private $responseList = array();
    private $options = array();
    private $headers = array();

    /**
     * call
     * @param $service 服务标识，配置文件 conf/serveice/$service
     * @param $apiName 接口名
     * @param $params 接口需要的参数
     * @param $callback 返回数据的标识
     * @param array $opt array('method' => 'GET','timeout' => 1) 可选配置， 比如method、timeout等
     * @param array $header 头信息 array("LogId:4132822614", "xxx:xxx");
     */
    public function call($service, $apiName, $params, $callback, $opt=array(), $header=array())
    {
        $request = Comm_Service_Request::createRequest();
        $request->setApi($service, $apiName, $opt);
        $request->setParam($params);
        $request->setOption($opt);
        $request->setHeader($header);
        $this->requestList[$callback] = $request;
    }

    /**
     * 设置当前请求的通用opt
     * @param $opt
     */
    public function setopt($opt)
    {
        $this->options = (array) $opt;
    }

    /**
     * 请求数据
     * @return array
     */
    public function callData()
    {
        $this->responseList = Comm_Service_Transport::exec($this->requestList, $this->options);
        $this->requestList = array();
        return $this->responseList;
    }

    /**
     * 魔术方法
     * @param $callback
     * @return mixed|string
     */
    public function __get($callback)
    {
        if (isset($this->responseList[$callback])) {
            return $this->responseList[$callback];
        }
        else {
            return '';
        }
    }
}
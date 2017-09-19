<?php
/**
 * Client.php
 * @desc: 网络请求客户端
 * @author: songruidong
 * @time: 2017/9/14 18:09
 */

class Comm_ServiceClient_Client
{
    private $requestList = array();
    private $responseList = array();
    private $options = array();
    private $headers = array();

    /*
     * service 服务标识，比如virus、doota
     * apiName 接口名
     * params 接口需要的参数
     * callback 返回数据的标识
     * opt, array('method' => 'GET','timeout' => 1) 可选配置， 比如method、timeout等
     */
    public function call($service, $apiName, $params, $callback, $opt=array(), $header=array())
    {
        $request = Comm_ServiceClient_Request::createRequest();
        $request->setApi($service, $apiName, $opt);
        $request->setParam($params);
        $request->setOption($opt);
        $request->setHeader($header);
        $this->requestList [$callback] = $request;
    }

    public function callData()
    {
        $this->responseList = Comm_ServiceClient_Transport::exec($this->requestList, $this->options);
        $this->requestList = array();
        return $this->responseList;
    }
}
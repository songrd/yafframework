<?php
/**
 * Log.php
 * @desc: 日志插件
 * @author: songruidong
 * @time: 2017/9/8 22:07
 */

class Plugin_Log extends Yaf_Plugin_Abstract
{
    /**
     * 分发开始hook
     *
     * @param object $request
     * @param object $response
     * @return
     */
    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        Comm_Omp::startup();
        if(!defined('CLIENT_IP')) {
            define('CLIENT_IP', Comm_Request::getClientIp());
        }
        if(!defined('LOG_ID')) {
            Comm_Log::genLogID();
        }
        if(!Comm_Request::isCli()) {
            $logArr = array(
                'ua' => Comm_Request::getServer('HTTP_USER_AGENT', ''),
                'host' => Comm_Request::getServer('HTTP_HOST', ''),
                'clientIp' => CLIENT_IP,
                'optime' => Comm_Request::getServer('REQUEST_TIME'),
            );
        } else {
            $logArr = array(
                'optime' => Comm_Request::getServer('REQUEST_TIME'),
            );
        }
        foreach($logArr as $key=>$log) {
            Comm_Log::addNotice($key, $log);
        }
    }

    /**
     * 分发结束hook
     *
     * @param object $request
     * @param object $response
     * @return
     */
    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        Tool_Omp::shutdown();
        Comm_Log::notice('log success');
    }
}
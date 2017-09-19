<?php
/**
 * Smarty.php
 * @desc:smarty插件
 * @author: songruidong
 * @time: 2017/9/11 16:20
 */

class Plugin_Smarty extends Yaf_Plugin_Abstract
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
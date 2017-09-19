<?php
/**
 * Action.php
 * @desc: action 基类
 * @author: songruidong
 * @time: 2017/9/11 16:21
 */
abstract class Comm_Base_Action extends Yaf_Action_Abstract
{
    const AUTH_LEVEL_EVERYONE   = 10;   // 任何人都可以访问
    const AUTH_LEVEL_LOGIN      = 20;   // 必须登录
    const AUTH_LEVEL_ADMIN      = 30;   // 必须管理员

    const OUTPUT_TYPE_JSON      = 'json';
    const OUTPUT_TYPE_JSONP     = 'jsonp';
    const OUTPUT_TYPE_HTML      = 'html';

    protected $_userInfo            = array();
    protected $_loginAuthorization  = self::AUTH_LEVEL_EVERYONE;
    protected $_outputType          = self::OUTPUT_TYPE_JSON;    // json, jsonp, html

    /**
     * 业务类具体的逻辑
     */
    abstract protected function __execute();

    protected function execute()
    {
        try {
            // 初始化用户
            $this->_initUser();
            // 校验权限
            $this->_checkAuthorization();
            // 执行业务逻辑
            $this->__execute();
        } catch (Exception $e) {
            Comm_Log::fatal($e->getMessage(), $e->getCode(), ['trace'=>$e->getTraceAsString()]);
            $response = Comm_Response::buildResponse($e->getCode(), $e->getMessage(), array(), $this->_userInfo);
            $this->_output($response);
        }
    }

    /**
     * 初始化用户
     */
    protected function _initUser()
    {
        // 判断用户是否登录等并初始化用户到_userInfo中
    }

    /**
     * 初始化权限，校验权限
     */
    protected function _checkAuthorization()
    {
        // 判断用户权限
    }

    /**
     * 输出结果
     * @param array $response
     * @param null $tplName
     * @param null $callback
     */
    protected function _output(array $response, $tplName=null, $callback=null)
    {
        switch ($this->_outputType) {
            case self::OUTPUT_TYPE_JSON:
                $this->_renderJson($response);
                break;
            case self::OUTPUT_TYPE_JSONP:
                $this->_renderJsonp($response, $callback);
                break;
            case self::OUTPUT_TYPE_HTML:
                $this->_renderSmarty($response, $tplName);
                break;
            default:
                $this->_renderJson($response);
        }
    }

    /**
     * 输出jsonp格式
     * @param array $response
     * @param null $callback
     */
    protected function _renderJsonp(array $response, $callback=null)
    {
        Comm_log::debug('response', 0, ['response' => $response]);
        Comm_Response::outputJsonp($response, $callback);
    }

    /**
     * 输出json格式
     * @param array $response
     */
    protected function _renderJson(array $response)
    {
        Comm_log::debug('response', 0, ['response' => $response]);
        Comm_Response::outputJson($response);
    }

    /**
     * 模板渲染
     * @param array $response
     * @param null $tplName
     */
    protected function _renderSmarty(array $response, $tplName=null)
    {

    }
}
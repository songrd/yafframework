<?php
/**
 * 构造响应工具类 
 *
 * @desc: response 类库
 * @author: songruidong
 * @time: 2017/9/8 21:40
 */

class Comm_Response
{
    /**
     * 构造输出响应
     *
     * @param int $code
     * @param string $msg
     * @param array  $data
     * @return array
     */
    public static function buildResponse($code, $msg = null, $data = array(), $userInfo=array())
    {
        if($msg === null) {
            $msg = self::getErrMsg($code);
        }
        return array(
            'errno'     => $code,
            'errmsg'    => $msg,
            'userInfo'  => $userInfo,
            'data'      => $data,
            'timestamp' => Comm_Request::getServer('REQUEST_TIME'),
            'logId'     => Comm_Log::genLogID(),
        );
    }

    /**
     * 根据code返回相应的错误信息
     *
     * @param int $code
     * @return string
     */
    public static function getErrMsg($code)
    {
        return Comm_ErrorCodes::getErrMsg($code);
    }

    /**
     * 接口按json格式输出响应
     *
     * @param string $code
     * @param string $msg
     * @param mixed $data
     */
    public static function outputJson(array $data)
    {
        if(!headers_sent()){
            header('Content-type: application/json; charset=utf-8', true);
        }
        echo json_encode($data);
    }

    /**
     * 接口按jsonp格式输出响应
     *
     * @param string $code
     * @param string $msg
     * @param mixed $data
     */
    public static function outputJsonp(array $data)
    {
        if(!headers_sent()){
            header('Content-type: application/json; charset=utf-8', true);
        }
        echo json_encode($data);
    }
}

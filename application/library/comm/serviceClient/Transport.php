<?php
/**
 * Transport.php
 * @desc: 执行curl
 * @author: songruidong
 * @time: 2017/9/14 18:11
 */

class Comm_ServiceClient_Transport
{
    public static $multiRequestType = 'multicurl';

    /**
     * 执行方法
     * @static
     * @param array $requestList 请求信息 new Comm_ServiceClient_Request()
     * @param array $opt  请求参数
     * @return array
     */
    public static function exec($requestList = array(), $opt= array())
    {
        if (empty($requestList)) {
            return array();
        }

        // 合并请求请求参数
        foreach ($requestList as $request) {
            $newOpt = self::combinOptions($request->opt, $opt);
            $request->setOption($newOpt);
        }

        // 判断如果是多个用 mulitcul，如果是一个用curl
        if (count($requestList) > 1) {
            $responseList = self::multiRequest($requestList, self::$multiRequestType);
        }
        else {
            $responseList = self::curlExec($requestList);
        }
        return $responseList;
    }

    /**
     * 设置请求参数
     * @static
     * @param $opt
     * @param $additionOpt
     * @return mixed
     */
    private static function combinOptions($opt, $additionOpt)
    {
        if (!is_array($additionOpt)) {
            return $opt;
        }
        foreach ($additionOpt as $type => $value) {
            if (isset($opt[$type])) {
                $opt[$type] = $value;
            } else {
                $opt[$type] = $value;
            }
        }
        return $opt;
    }

    /**
     * multi curl
     * @static
     * @param $requestList
     * @param $type
     * @return array
     */
    private static function multiRequest($requestList, $type)
    {
        if (empty($requestList)) {
            return array();
        }
        $responseList = self::MultiCurlExec($requestList);
        return $responseList;
    }

    /**
     * multi curl
     * @static
     * @param $requestList
     * @return mixed
     */
    private static function MultiCurlExec($requestList)
    {
        MultiCurl::instance()->open();
        MultiCurl::instance()->send($requestList);
        $responseList = MultiCurl::instance()->exec();
        MultiCurl::instance()->close();
        return $responseList;
    }

    /**
     * curl 请求
     * @static
     * @param $requestList
     * @return array
     */
    private static function curlExec($requestList)
    {
        $responseList = array();
        foreach ($requestList as $key => $request) {
            Comm_ServiceClient_Curl::instance()->open();
            Comm_ServiceClient_Curl::instance()->send($request);
            $responseList[$key] = Comm_ServiceClient_Curl::instance()->exec();
            Comm_ServiceClient_Curl::instance()->close();
        }
        return $responseList;
    }
}
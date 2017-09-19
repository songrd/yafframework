<?php
/**
 * CurlLog.php
 * @desc: curl log
 * @author: songruidong
 * @time: 2017/9/19 16:24
 * 统计超时日志数量的shell例子: for i in `ls -d mutilcurl_* | grep -v "result"`;do grep "2013-12-16" $i/$i\_current | wc -l;echo $i\_current;done
 */

class Comm_Service_CurlLog
{
    const LOGFILE_NAME = 'service';

    /**
     * 记录错误日志
     *
     * @return boolean
     * @access private
     * @param $ch curl的句柄, $type是该请求所采用的curl类型
     */
    public function wlog($ch, $type)
    {
        if (isset($GLOBALS['FROM_SERVICE']) && $GLOBALS['FROM_SERVICE'] == 'cart') {
            //购物车zmon日志
            $this->rpclog($ch);
            return true;
        }

        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $info = curl_getinfo($ch);
        $url = $info['url'];
        $code = $info['http_code'];

        $time = $_SERVER['REQUEST_TIME'];
        $fromIp = $_SERVER['SERVER_ADDR'];
        $logid = Comm_Log::genLogID();

        $uri = 'http://' . $_SERVER['HTTP_HOST'] . (empty($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI']);
        $encodedErrMsg = urlencode($curlError);
        $str = "[time:{$time}][logid:{$logid}][ip:{$fromIp}][req:{$url}][url:{$uri}][errno:{$curlErrno}][errmsg:{$encodedErrMsg}][code:{$code}]";
        $extra = http_build_query($info);
        $ctimeStr = sprintf('%.2f', $info['connect_time'] * 1000);
        $wtimeStr = sprintf('%.2f', ($info['pretransfer_time'] - $info['connect_time']) * 1000);
        $rtimeStr = sprintf('%.2f', ($info['total_time'] - $info['pretransfer_time']) * 1000);
        $ttimeStr = sprintf('%.2f', $info['total_time'] * 1000);
        $str .= "[ctime:{$ctimeStr}][wtime:{$wtimeStr}][rtime:{$rtimeStr}][ttime:{$ttimeStr}][extra:{$extra}]";

        Comm_Log::serviceclientLog($str);

        return true;
    }

    /**
     * 记录rpclog
     * @param $ch
     */
    private function rpclog($ch)
    {
        $curlErrno = curl_errno ( $ch );
        $curlError = curl_error ( $ch );
        $info = curl_getinfo ( $ch );

        $logInfo = array ();
        $logInfo ['curl_errno'] = $curlErrno;
        $logInfo ['curl_error'] = $curlError;
        $logInfo ['url'] = $info ['url'];
        $logInfo ['http_code'] = $info ['http_code'];
        $logInfo ['total_time'] = number_format ( $info ['total_time'] * 1000, 0 );
        $logInfo ['time_detail'] = number_format ( $info ['namelookup_time'] * 1000, 0 ) . "," . number_format ( $info ['connect_time'] * 1000, 0 ) . "," .   number_format ( $info ['pretransfer_time'] * 1000, 0 ) . "," . number_format ( $info ['starttransfer_time'] * 1000, 0 );

        Comm_Log::serviceclientLog('rpc', 0, $logInfo);
    }
}
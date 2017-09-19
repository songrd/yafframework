<?php 
/*
Used for app index
Original format string: %L: %t [%f:%N] errno[%E] logId[%l] uri[%U] refer[%{referer}i] cookie[%{cookie}i] %S errmsg[%M]
*/
function _comm_log_f39d3e755019813c15338de203f1d8d3() {

return '' . Comm_Log::$currInstance->currLogLevel . ': ' . strftime('%y-%m-%d %H:%M:%S') . ' [' . Comm_Log::$currInstance->currFile . ':' . Comm_Log::$currInstance->currLine . '] errno[' . Comm_Log::$currInstance->currErrno . '] logId[' . Comm_Log::genLogID() . '] uri[' . Comm_Request::getRequestUri() . '] refer[' . (isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : '') . '] cookie[' . (isset($_SERVER['HTTP_COOKIE'])? $_SERVER['HTTP_COOKIE'] : '') . '] ' . Comm_Log::$currInstance->getStrArgs() . ' errmsg[' . Comm_Log::$currInstance->currErrmsg . ']' . "\n";
}
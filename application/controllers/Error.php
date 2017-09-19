<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author internal\songruidong
 */
class Controller_Error extends Yaf_Controller_Abstract {

    //从2.1开始, errorAction支持直接通过参数获取异常
    public function errorAction($exception) {

        Comm_Log::warning($exception->getMessage(), $exception->getCode(), ['trace'=>$exception->getTraceAsString()]);

        echo "<pre>";
        echo "file: " . $exception->getFile() . "\r\n";
        echo "line: " . $exception->getLine() . "\r\n";
        echo "code: " . $exception->getCode() . "\r\n";
        echo "message: " . $exception->getMessage() . "\r\n";
        echo "trace:\r\n";
        echo $exception->getTraceAsString();

        //var_dump($exception->getMessage());
        //var_dump($exception);
        //1. assign to view engine
        //$this->getView()->assign("exception", $exception);
        //5. render by Yaf
    }
}

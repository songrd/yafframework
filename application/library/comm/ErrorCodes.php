<?php
/**
 * ErrorCodes.php
 * @desc: 错误码
 * @author: songruidong
 * @time: 2017/9/11 17:35
 */

class Comm_ErrorCodes
{
    // 错误码大于10000，小于10000在 Comm_SysErrorCodes 中，属于系统错误
    const TEST_ERR = 100001;

    protected static $codeMap = array(
        self::TEST_ERR => '错误示例',
    );

    /**
     * 根据code返回相应的错误信息
     *
     * @param int $code
     * @return string
     */
    public static function getErrMsg($code)
    {
        $errMsg = isset(self::$codeMap[$code]) ? self::$codeMap[$code] : Comm_SysErrorCodes::$codeMap[$code];
        return $errMsg;
    }
}
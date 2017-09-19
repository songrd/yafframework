<?php
/**
 * PHP性能分析工具类
 *
 * @author: songruidong
 * @time: 2017/9/8 18:15
 */
class Comm_Omp
{

    protected static $_startTime = null;
    protected static $_endTime = null;

    /**
     * 启动函数
     *
     * @param
     * @return
     */
    public static function startup() {
        self::$_startTime = microtime(true) * 1000;
        //xhprof
    }

    /**
     * 结束函数
     *
     * @param
     * @return
     */
    public static function shutdown() {
        self::$_endTime = microtime(true) * 1000;
        $cost = intval(self::$_endTime - self::$_startTime);
        Tool_Log::addNotice('cost', $cost);
        //xhprof
    }
}

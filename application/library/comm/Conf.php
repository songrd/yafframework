<?php
/**
 * 配置文件存取器(封装Yaf_Config_Ini)
 *
 * @desc: 配置文件存取器
 * @author: songruidong
 * @time: 2017/9/8 21:40
 */
class Comm_Conf {
    
    private static $inst = array();
    
    /**
     * 构造函数
     *
     * @param
     * @return
     */
    private function __construct()
    {

    }

    /**
     * 获取配置项
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key){
        if (strpos($key, '.') !== false) {
            list($file, $path) = explode('.', $key, 2);
        }else{
            $file = $key;
        }

        if (!isset(self::$inst[$file])) {
            self::$inst[$file] = new Yaf_Config_Ini(ROOT_PATH . '/conf/' . $file . '.ini');
        }

        if (isset($path)) {
            $ret = self::$inst[$file]->get($path);
            if (is_a($ret,'Yaf_Config_Ini')) {
                return $ret->toArray();
            }else{
                return $ret;
            }
        }else{
            return self::$inst[$file]->toArray();
        }
    }
}

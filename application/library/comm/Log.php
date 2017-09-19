<?php
/**
 * Log.php
 * @desc: log 基类
 * @author: songruidong
 * @time: 2017/9/8 18:15
 */

class Comm_Log
{
    // 日志级别
    const LOG_LEVEL_FATAL   = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE  = 0x04;
    const LOG_LEVEL_TRACE   = 0x08;
    const LOG_LEVEL_DEBUG   = 0x10;

    // 日志类型
    public static $logLevelMap = array(
        self::LOG_LEVEL_FATAL       => 'FATAL',
        self::LOG_LEVEL_WARNING     => 'WARNING',
        self::LOG_LEVEL_NOTICE      => 'NOTICE',
        self::LOG_LEVEL_TRACE       => 'TRACE',
        self::LOG_LEVEL_DEBUG       => 'DEBUG',
    );

    //日志相关的配置
    // log日志级别
    protected $_intLevel;
    // 是否自动切分
    protected $_bolAutoRotate;
    // 切分格式 YmdHis
    protected $_autoRotateTime;
    // 参考常量 DEFAULT_FORMAT..
    protected $_strFormat;
    protected $_strFormatWF;
    protected $_strFormatCli;
    // 日志文件
    protected $_strLogFile;
    protected $_currArgs;


    public $currLogLevel;
    public $currErrno;
    public $currErrmsg;
    public $currFile;
    public $currLine;
    public $currFunc;
    public $currClass;
    public $currFuncParam;

    protected $_addNotice = array();

    public static $currInstance;
    protected static $_arrInstance = array();

    const DEFAULT_FORMAT = '%L: %t [%f:%N] errno[%E] logId[%l] uri[%U] user[%u] refer[%{referer}i] cookie[%{cookie}i] %S %M';
    const DEFAULT_FORMAT_STD = '%L: %{%m-%d %H:%M:%S}t %{app}x * [logid=%l filename=%f lineno=%N errno=%{err_no}x errmsg=%{u_err_msg}x]';
    const DEFAULT_FORMAT_STD_DETAIL = '%L: %{%m-%d %H:%M:%S}t %{app}x * [logid=%l filename=%f lineno=%N errno=%{err_no}x errmsg=%{u_err_msg}x cookie=%{u_cookie}x]';


    /**
     * 构造函数
     *
     * @param array $arrLogConfig
     * @return
     */
    protected function __construct($arrLogConfig)
    {
        $this->_intLevel         = $arrLogConfig['level'];
        $this->_bolAutoRotate    = $arrLogConfig['auto_rotate'];
        $this->_autoRotateTime	 = $arrLogConfig['auto_rotate_time'];
        $this->_strFormat        = $arrLogConfig['format'];
        $this->_strFormatWF      = $arrLogConfig['format_wf'];
        $this->_strFormatCli     = $arrLogConfig['format_cli'];
        $this->_strLogFile       = $arrLogConfig['log_file'];
    }

    /**
     * 获取日志前缀
     *
     * @param
     * @return string
     */
    public static function getLogPrefix()
    {
        $request = Yaf_Dispatcher::getInstance()->getRequest();
        $moduleName = $request->getModuleName();
        return strtolower($moduleName);
    }

    /**
     * 获取日志的路径
     *
     * @param
     * @return string
     */
    protected static function _getLogPath()
    {
        return LOG_PATH;
    }

    /**
     * 获取数据目录
     *
     * @param
     * @return string
     */
    protected static function _getDataPath()
    {
        return DATA_PATH;
    }

    /**
     * 根据日志级别获取日志格式
     *
     * @param int $level
     * @return string
     */
    protected function _getLogFormat($level)
    {
        if (PHP_SAPI == 'cli') {
            $fmtstr = $this->_strFormatCli;
        } elseif ($level == self::LOG_LEVEL_FATAL || $level == self::LOG_LEVEL_WARNING) {
            $fmtstr = $this->_strFormatWF;
        } else {
            $fmtstr = $this->_strFormat;
        }
        return $fmtstr;
    }

    /**
     * 获取日志字符串
     *
     * @param string $format
     * @return string
     */
    protected function _getLogString($format)
    {
        $md5val = md5($format);
        $func = "_comm_log_{$md5val}";
        if (function_exists($func)) {
            return $func();
        }
        $dataPath = self::_getDataPath();
        $filename = $dataPath. '/logfuncs/' . $md5val . '.php';
        if (!file_exists($filename)) {
            $tmpFileName = $filename . '.' . mt_rand();
            if (!is_dir($dataPath. '/logfuncs')) {
                mkdir($dataPath. '/logfuncs', 0744, true);
            }
            file_put_contents($tmpFileName, $this->_parseFormat($format));
            rename($tmpFileName, $filename);
        }
        Yaf_Loader::getInstance()->import($filename);
        $str = $func();
        return $str;
    }

    /**
     * 获取日志实例
     *
     * @param string $app 日志前缀
     * @param string $logType 日志类型
     * @return $this
     */
    public static function getInstance($app = null, $logType=null)
    {
        if(!$app) {
            $app = self::getLogPrefix();
        }
        if(!isset(self::$_arrInstance[$app])) {
            $appLogConf = Comm_Conf::get('log');
            $logPath    = self::_getLogPath();
            if(isset($appLogConf['use_sub_dir']) && $appLogConf['use_sub_dir']) {
                if(!is_dir("{$logPath}/{$app}")) {
                    mkdir("{$logPath}/{$app}", 0744, true);
                }
                $logFile = "{$logPath}/{$app}/{$app}.log";
            } else {
                $logFile = "{$logPath}/{$app}.log";
            }

            if (isset($appLogConf['format'])) {
                $format = $appLogConf['format'];
            } else {
                $format = self::DEFAULT_FORMAT;
            }

            if (isset($appLogConf['format_wf'])) {
                $formatWf = $appLogConf['format_wf'];
            } else {
                $formatWf = $format;
            }

            if(isset($appLogConf['format_cli'])) {
                $formatCli = $appLogConf['format_cli'];
            } else {
                $formatCli = self::DEFAULT_FORMAT;
            }

            $logConf = array(
                'level'         => intval($appLogConf['level']),
                'auto_rotate'   => ($appLogConf['auto_rotate'] == 1),
                'log_file'      => $logFile,
                'format'        => $format,
                'format_wf'     => $formatWf,
                'format_cli'    => $formatCli,
            );

            if(isset($appLogConf['auto_rotate_time'])) {
                $logConf['auto_rotate_time'] = $appLogConf['auto_rotate_time'];
            } else {
                $logConf['auto_rotate_time'] = 'YmdH';
            }
            self::$_arrInstance[$app] = new self($logConf);
        }
        return self::$_arrInstance[$app];
    }

    /**
     * 调试日志
     *
     * @param string $str
     * @param int $errno
     * @param array $arrArgs
     * @param int $depth
     * @return
     */
    public static function debug($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        $ret = self::getInstance()->_writeLog(self::LOG_LEVEL_DEBUG, $str, $errno, $arrArgs, $depth + 1);
        return $ret;
    }

    /**
     * 跟踪日志
     *
     * @param string $str
     * @param int $errno
     * @param array $arrArgs
     * @param int $depth
     * @return
     */
    public static function trace($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        $ret = self::getInstance()->_writeLog(self::LOG_LEVEL_TRACE, $str, $errno, $arrArgs, $depth + 1);
        return $ret;
    }

    /**
     * 业务日志
     *
     * @param string $str
     * @param int $errno
     * @param array $arrArgs
     * @param int $depth
     * @return
     */
    public static function notice($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        $ret = self::getInstance()->_writeLog(self::LOG_LEVEL_NOTICE, $str, $errno, $arrArgs, $depth + 1);
        return $ret;
    }

    /**
     * 警告日志
     *
     * @param string $str
     * @param int $errno
     * @param array $arrArgs
     * @param int $depth
     * @return
     */
    public static function warning($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        $ret = self::getInstance()->_writeLog(self::LOG_LEVEL_WARNING, $str, $errno, $arrArgs, $depth + 1);
        return $ret;
    }

    /**
     * 错误日志
     *
     * @param string $str
     * @param int $errno
     * @param array $arrArgs
     * @param int $depth
     * @return
     */
    public static function fatal($str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        $ret = self::getInstance()->_writeLog(self::LOG_LEVEL_FATAL, $str, $errno, $arrArgs, $depth + 1);
        return $ret;
    }

    /**
     * serviceclient日志
     * @static
     * @param $str
     * @param int $errno
     * @param null $arrArgs
     * @param int $depth
     * @param string $level
     * @return int
     */
    public static function serviceclientLog($str, $errno = 0, $arrArgs = null, $depth = 0, $level=0x02)
    {
        $ret = self::getInstance('serviceclient')->_writeLog($level, $str, $errno, $arrArgs, $depth + 1);
        return $ret;
    }

    /**
     * 添加业务日志
     *
     * @param string $key
     * @param mixed	$value
     * @return
     */
    public static function addNotice($key, $value)
    {
        $log = self::getInstance();
        if(!isset($value)) {
            $value = $key;
            $key = '@';
        }
        $info = is_array($value) ?json_encode($value) :$value;
        if(PHP_SAPI != 'cli') {
            $log->_addNotice[$key] = $info;
        } else {
            $arrArgs = array(
                $key => $info,
            );
            self::notice('', 0, $arrArgs, 1);
        }
    }

    /**
     * 生成logId
     *
     * @param
     * @return int
     */
    public static function genLogID()
    {
        if(defined('LOG_ID')){
            return LOG_ID;
        }
        if(isset($_REQUEST['logid']) && intval($_REQUEST['logid']) !== 0){
            define('LOG_ID', intval($_REQUEST['logid']));
        }else{
            $arr = gettimeofday();
            $logId = ((($arr['sec']*100000 + $arr['usec']/10) & 0x7FFFFFFF) | 0x80000000);
            define('LOG_ID', $logId);
        }
        return LOG_ID;
    }

    /**
     * 写日志函数
     *
     * @param int $intLevel 日志级别
     * @param string $str
     * @param int $errno
     * @param array $arrArgs
     * @param int $depth
     * @param
     */
    protected function _writeLog($intLevel, $str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        if( $intLevel > $this->_intLevel || !isset(self::$logLevelMap[$intLevel]) ) {
            return;
        }

        $strLogFile = $this->_strLogFile;
        if(($intLevel & self::LOG_LEVEL_WARNING) || ($intLevel & self::LOG_LEVEL_FATAL)) {
            $strLogFile .= '.wf';
        }

        $this->currLogLevel = self::$logLevelMap[$intLevel];
        $this->_currArgs    = $this->_addNotice;

        if (is_array($arrArgs) && $arrArgs) {
            $this->_currArgs = array_merge($this->_currArgs, $arrArgs);
        }
        $this->currErrno  = $errno;
        $this->currErrmsg = $str;
        $trace = debug_backtrace();

        $depth2 = $depth + 1;
        if( $depth >= count($trace) ) {
            $depth = count($trace) - 1;
            $depth2 = $depth;
        }
        $this->currFile = isset($trace[$depth]['file']) ?$trace[$depth]['file'] :'';
        $this->currLine = isset($trace[$depth]['line']) ?$trace[$depth]['line'] :'';
        $this->currFunc = isset($trace[$depth2]['function']) ?$trace[$depth2]['function'] :'';
        $this->currClass = isset($trace[$depth2]['class']) ?$trace[$depth2]['class'] :'';
        $this->currFuncParam = isset($trace[$depth2]['args']) ?$trace[$depth2]['args'] :'';
        self::$currInstance = $this;

        $format = $this->_getLogFormat($intLevel);
        $str    = $this->_getLogString($format);

        if($this->_bolAutoRotate) {
            $strLogFile .= '.'.date($this->_autoRotateTime);
        }

        return file_put_contents($strLogFile, $str, FILE_APPEND);
    }

    /**
     * 根据format输出字符串
     *
     * @param string $format
     * @return string
     */
    protected function _parseFormat($format)
    {
        $matches = array();
        $regex = '/%(?:{([^}]*)})?(.)/';
        preg_match_all($regex, $format, $matches);
        $prelim = array();
        $action = array();
        $prelim_done = array();

        $len = count($matches[0]);
        for($i = 0; $i < $len; $i++) {
            $code = $matches[2][$i];
            $param = $matches[1][$i];
            switch($code) {
                case 'h':
                    $action[] = "(defined('CLIENT_IP')? CLIENT_IP: Comm_Request::getClientIp())";
                    break;
                case 't':
                    $action[] = ($param == '') ?"strftime('%y-%m-%d %H:%M:%S')" :"strftime(" . var_export($param, true) . ")";
                    break;
                case 'i':
                    $key = 'HTTP_' . str_replace('-', '_', strtoupper($param));
                    $key = var_export($key, true);
                    $action[] = "(isset(\$_SERVER[$key])? \$_SERVER[$key] : '')";
                    break;
                case 'a':
                    $action[] = "(defined('CLIENT_IP') ?CLIENT_IP :Comm_Request::getClientIp())";
                    break;
                case 'A':
                    $action[] = "(isset(\$_SERVER['SERVER_ADDR'])? \$_SERVER['SERVER_ADDR'] : '')";
                    break;
                case 'C':
                    if ($param == '') {
                        $action[] = "(isset(\$_SERVER['HTTP_COOKIE'])? \$_SERVER['HTTP_COOKIE'] : '')";
                    } else {
                        $param = var_export($param, true);
                        $action[] = "(isset(\$_COOKIE[$param])? \$_COOKIE[$param] : '')";
                    }
                    break;
                case 'D':
                    $action[] = "(defined('REQUEST_TIME_US') ?(microtime(true) * 1000 - REQUEST_TIME_US/1000) :'')";
                    break;
                case 'e':
                    $param = var_export($param, true);
                    $action[] = "((getenv($param) !== false) ?getenv($param) :'')";
                    break;
                case 'f':
                    $action[] = 'Comm_Log::$currInstance->currFile';
                    break;
                case 'H':
                    $action[] = "(isset(\$_SERVER['SERVER_PROTOCOL'])? \$_SERVER['SERVER_PROTOCOL'] : '')";
                    break;
                case 'm':
                    $action[] = "(isset(\$_SERVER['REQUEST_METHOD'])? \$_SERVER['REQUEST_METHOD'] : '')";
                    break;
                case 'p':
                    $action[] = "(isset(\$_SERVER['SERVER_PORT'])? \$_SERVER['SERVER_PORT'] : '')";
                    break;
                case 'q':
                    $action[] = "(isset(\$_SERVER['QUERY_STRING'])? \$_SERVER['QUERY_STRING'] : '')";
                    break;
                case 'T':
                    switch($param) {
                        case 'ms':
                            $action[] = "(defined('REQUEST_TIME_US')? (microtime(true) * 1000 - REQUEST_TIME_US/1000) : '')";
                            break;
                        case 'us':
                            $action[] = "(defined('REQUEST_TIME_US')? (microtime(true) * 1000000 - REQUEST_TIME_US) : '')";
                            break;
                        default:
                            $action[] = "(defined('REQUEST_TIME_US')? (microtime(true) - REQUEST_TIME_US/1000000) : '')";
                            break;
                    }
                    break;
                case 'U':
                    $action[] = "Comm_Request::getRequestUri()";
                    break;
                case 'v':
                    $action[] = "(isset(\$_SERVER['HOSTNAME'])? \$_SERVER['HOSTNAME'] : '')";
                    break;
                case 'V':
                    $action[] = "(isset(\$_SERVER['HTTP_HOST'])? \$_SERVER['HTTP_HOST'] : '')";
                    break;

                case 'L':
                    $action[] = 'Comm_Log::$currInstance->currLogLevel';
                    break;
                case 'N':
                    $action[] = 'Comm_Log::$currInstance->currLine';
                    break;
                case 'E':
                    $action[] = 'Comm_Log::$currInstance->currErrno';
                    break;
                case 'l':
                    $action[] = 'Comm_Log::genLogID()';
                    break;
                case 'S':
                    $action[] = 'Comm_Log::$currInstance->getStrArgs()';
                    break;
                case 'M':
                    $action[] = 'Comm_Log::$currInstance->currErrmsg';
                    break;
                case 'x':
                    $needUrlencode = false;
                    if (substr($param, 0, 2) == 'u_') {
                        $needUrlencode = true;
                        $param = substr($param, 2);
                    }
                    switch($param) {
                        case 'log_level':
                            $action[] = 'Comm_Log::$currInstance->currLogLevel';
                            break;
                        case 'line':
                            $action[] = 'Comm_Log::$currInstance->currLine';
                            break;
                        case 'class':
                            $action[] = 'Comm_Log::$currInstance->currClass';
                            break;
                        case 'function':
                            $action[] = 'Comm_Log::$currInstance->currFunc';
                            break;
                        case 'err_no':
                            $action[] = 'Comm_Log::$currInstance->currErrno';
                            break;
                        case 'err_msg':
                            $action[] = 'Comm_Log::$currInstance->currErrmsg';
                            break;
                        case 'log_id':
                            $action[] = 'Comm_Log::genLogID()';
                            break;
                        case 'app':
                            $action[] = 'Comm_Log::getLogPrefix()';
                            break;
                        case 'function_param':
                            $action[] = 'Comm_Log::flattenArgs(Comm_Log::$currInstance->currFuncParam)';
                            break;
                        case 'argv':
                            $action[] = '(isset($GLOBALS["argv"])? Comm_Log::flattenArgs($GLOBALS["argv"]) : \'\')';
                            break;
                        case 'cookie':
                            $action[] = "(isset(\$_SERVER['HTTP_COOKIE'])? \$_SERVER['HTTP_COOKIE'] : '')";
                            break;
                        default:
                            $action[] = "''";
                    }
                    if ($needUrlencode) {
                        $actionLen = count($action);
                        $action[$actionLen - 1] = 'rawurlencode(' . $action[$actionLen - 1] . ')';
                    }
                    break;
                case '%':
                    $action[] =  "'%'";
                    break;
                default:
                    $action[] = "''";
            }
        }

        $strformat = preg_split($regex, $format);
        $code = var_export($strformat[0], true);
        for($i = 1; $i < count($strformat); $i++) {
            $code = $code . ' . ' . $action[$i-1] . ' . ' . var_export($strformat[$i], true);
        }
        $code .=  ' . "\n"';
        $pre = implode("\n", $prelim);

        $cmt = 'Used for app ' . self::getLogPrefix() . "\n";
        $cmt .= 'Original format string: ' . str_replace('*/', '* /', $format);

        $md5val = md5($format);
        $func = "_comm_log_$md5val";
        $str = "<?php \n/*\n$cmt\n*/\nfunction $func() {\n$pre\nreturn $code;\n}";
        return $str;
    }

    /**
     * 格式化空白字符
     *
     * @param array $args
     * @return string
     */
    public static function flattenArgs($args)
    {
        if (!is_array($args)) {
            return '';
        }
        $formatArgs = array();
        foreach($args as $arg) {
            $formatArgs[] = preg_replace('/[ \n\t]+/', ' ', $arg);
        }
        return implode(', ', $formatArgs);
    }

    /**
     * 格式化日志字符串
     *
     * @param
     * @return string
     */
    public function getStrArgs()
    {
        $strArgs = '';
        foreach($this->_currArgs as $k=>$v){
            if(is_object($v)) {
                $v = serialize($v);
            } elseif(is_array($v)) {
                $v = json_encode($v);
            }
            $strArgs .= ' '.$k.'['.$v.']';
        }
        return $strArgs;
    }
}
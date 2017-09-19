<?php
/**
 * SysErrorCodes.php
 * @desc: 系统错误
 * @author: songruidong
 * @time: 2017/9/12 17:00
 */

class Comm_SysErrorCodes
{
    // 通用
    const SUCCESS           = 0; //成功
    const ERROR = 1;   //失败
    const NOPOWER = 2; //没有权限
    const REFRESHJS_ERROR   = 3;  //刷新JS失败
    const INTERFACE_ERROR   = -1;     //接口调用失败
    const INTERFACE_TIMEOUT = -2;  //接口调用接口超时

    // 登录、权限
    const NOT_LOGIN      = 21;
    const NOT_AUTH_URI   = 22;
    const NOT_AUTH_DATA  = 23;
    const TOKEN_ERR      = 25;

    // REDIS
    const REDIS_CONNECT_ERR     = 31;
    const REDIS_CHECK_ERR       = 32;
    const REDIS_CONFIG_ERR      = 33;
    const REDIS_CALL_EXCEPTION  = 34;

    // 数据库
    const DB_CONNECTION_ERROR           = 51;
    const DB_QUERY_ERROR                = 52;
    const DB_SELECT_ERROR               = 53;
    const DB_SELECTCOUNT_ERROR          = 54;
    const DB_UPDATE_ERROR               = 55;
    const DB_DELETE_ERROR               = 56;
    const DB_INSERT_ERROR               = 57;
    const DB_START_TRANSACTION_ERROR    = 58;
    const DB_COMMIT_TRANSACTION_ERROR   = 59;
    const DB_ROLLBACK_TRANSACTION_ERROR = 60;
    const DB_TO_SQL_ERR                 = 61;
    const DB_SET_OPTION_ERR             = 62;

    // 参数
    const PARAM_MISS_ERROR = 71; //参数缺失
    const PARAM_ILLE_ERROR = 72; //参数非法
    const PARAM_TYPE_ERROR = 73; //参数类型错误

    // service
    const SERVICE_CONFIG_EMPTY = 81;

    public static $codeMap = array(
        self::SUCCESS           => 'SUCCESS',
        self::ERROR             => 'ERROR',
        self::NOPOWER           => 'NOPOWER',
        self::REFRESHJS_ERROR   => 'REFRESHJS_ERROR',
        self::INTERFACE_ERROR   => 'INTERFACE_ERROR',
        self::INTERFACE_TIMEOUT => 'INTERFACE_TIMEOUT',

        // 登录、权限
        self::NOT_LOGIN     => 'NOT LOGIN',
        self::NOT_AUTH_URI  => 'NOT AUTH URI',
        self::NOT_AUTH_DATA => 'NOT AUTH DATA',
        self::TOKEN_ERR     => 'TOKEN ERR',

        // redis链接错误
        self::REDIS_CONNECT_ERR => 'REDIS_CONNECT_ERR',
        self::REDIS_CHECK_ERR   => 'REDIS_CHECK_ERR',
        self::REDIS_CONFIG_ERR  => 'REDIS_CONFIG_ERR',
        self::REDIS_CALL_EXCEPTION => 'REDIS_CALL_EXCEPTION',

        //DB错误=========================
        self::DB_CONNECTION_ERROR => 'db connection error',
        self::DB_QUERY_ERROR => 'db query error',
        self::DB_QUERY_ERROR => 'DB_QUERY_ERROR',
        self::DB_SELECT_ERROR => 'DB_SELECT_ERROR',
        self::DB_SELECTCOUNT_ERROR => 'DB_SELECTCOUNT_ERROR',
        self::DB_UPDATE_ERROR => 'DB_UPDATE_ERROR',
        self::DB_DELETE_ERROR => 'DB_DELETE_ERROR',
        self::DB_INSERT_ERROR => 'DB_INSERT_ERROR',
        self::DB_START_TRANSACTION_ERROR => 'DB_START_TRANSACTION_ERROR',
        self::DB_COMMIT_TRANSACTION_ERROR => 'DB_COMMIT_TRANSACTION_ERROR',
        self::DB_ROLLBACK_TRANSACTION_ERROR => 'DB_ROLLBACK_TRANSACTION_ERROR',
        self::DB_TO_SQL_ERR                 => 'DB_TO_SQL_ERR',
        self::DB_SET_OPTION_ERR             => 'DB_SET_OPTION_ERR',

        self::PARAM_MISS_ERROR => '参数缺失',
        self::PARAM_ILLE_ERROR => '参数不合法',
        self::PARAM_TYPE_ERROR => '参数类型错误',
    );
}
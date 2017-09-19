<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/

class Comm_Exception_Error extends Exception {
    const WARNING = 'warning';
    const DEBUG = 'debug';
    const TRACE = 'trace';
    const FATAL = 'fatal';
    const NOTICE = 'notice';

    private $err_no;
    private $err_str;

    public function __construct($err_no, $errstr=null, $arg=null, $level=self::WARNING) {
        $this->err_no = $err_no;
        if ($errstr == null) {
            $errstr = Comm_ErrorCodes::getErrMsg($err_no);
        }
        if ($errstr == null) {
            $errstr = '---Err_no not found . no:' . $err_no;
        }
        $this->err_str = $errstr;

        $stack_trace = $this->getTrace();
        $class       = @$stack_trace[0]['class'];
        $type        = @$stack_trace[0]['type'];
        $function    = $stack_trace[0]['function'];
        $file        = $this->file;
        $line        = $this->line;
        if ($class != null) {
            $function = "$class$type$function";
        }

        Comm_Log::$level("$errstr at [$function at $file:$line]", $err_no, $arg);

        parent::__construct($errstr, $err_no);
    }

    public function getErrNo() {
        return $this->err_no;
    }
    public function getErrStr() {
        return $this->err_str;
    }
}

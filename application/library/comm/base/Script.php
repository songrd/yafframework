<?php
/**
 * Script.php
 * @desc: 脚本基类
 * @author: songruidong
 * @time: 2017/9/11 16:22
 */

abstract class Comm_Base_Script {
    protected $_arrOption = null;
    protected $_tsSt      = null;    //  执行开始时间
    protected $_tsEd      = null;    //  执行结束时间

    /**
     *  run
     *
     *  @param  mixed $argv
     *  @access public
     *  @return void
     */
    public function run($argv)
    {
        //  执行开始时间
        $this->_tsSt = time();
        //  引入命令行参数
        $this->resolveArgs($argv);

        try {
            $intReturnCode = $this->execute($argv);
        } catch (Exception $e) {
            Bd_Log::warning($e->getMessage(), $e->getCode());
            $intReturnCode = -1;
        }

        //  执行结束时间
        $this->_tsEd = time();

        $intCostTime = $this->_tsEd - $this->_tsSt;
        Bd_Log::trace("[costTime: {$intCostTime}]", 0, $argv);

        return $intReturnCode;
    }

    /**
     *  execute
     *
     *  @param  mixed $argv
     *  @abstract
     *  @access public
     *  @return void
     */
    abstract public function execute($argv);

    /**
     *  resolveArgs
     *
     *  @param  mixed $argv
     *  @access protected
     *  @return void
     */
    protected function resolveArgs($argv)
    {
        foreach ($argv as $arg) {
            if (preg_match('/^--(\w+)(=(.*))?$/',$arg,$matches)) {
                list(, $option, , $val) = $matches;
                if (isset($this->_arrOption[$option])) {    //  已设值
                    if (!is_array($this->_arrOption[$option])) {
                        //  未数组==>数组化
                        $this->_arrOption[$option] = array($this->_arrOption[$option]);
                    }
                    array_push($this->_arrOption[$option], $val);
                } else {
                    $this->_arrOption[$option] = $val;
                }
            }
        }
        return true;
    }

    /**
     *  getTsPassedBy
     *
     *  @access protected
     *  @return void
     */
    protected function getTsPassedBy()
    {
        return intval(time() - $this->_tsSt);
    }
}
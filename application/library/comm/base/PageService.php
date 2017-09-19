<?php
/**
 * PageService.php
 * @desc: pageservice 基类
 * @author: songruidong
 * @time: 2017/9/11 16:22
 */
abstract class Comm_Base_PageService
{
    protected $_arrInput;
    protected $_arrFormat     = array();
    protected $_userInfo      = array();
    protected $_adminUserInfo = array();

    /**
     * 构造函数
     *
     * @param
     * @return
     */
    public function __construct()
    {

    }

    /**
     * 业务类具体的逻辑
     */
    abstract protected function __execute();

    /**
     * 初始化参数
     * @param
     * @return
     */
    protected function _initParams()
    {
        $this->_arrInput = Comm_Request::getRequestParams();
        Bd_Log::addNotice('request_params', http_build_query($this->_arrInput));
    }

    /**
     * 检查参数
     * @param array $arrFormat
     * @param array $arrInput
     * @return boolean
     */
    protected function _checkParams($arrFormat, $arrInput) {
        if(!$arrFormat) {
            return true;
        }

        foreach ($arrFormat as $key=>$val) {
            if ($val['required'] && !isset($arrInput[$key])) {
                throw new InvalidArgumentException("PARAM '$key' REQUIRED", Comm_ErrorCodes::PARAM_MISS_ERROR);
            }
            if ($val['required'] === false) {
                continue;
            }
            if(!isset($arrInput[$key])) {
                if(isset($val['default'])) {
                    $this->_arrInput[$key] = $val['default'];
                }
                continue;
            }
            $currParam = $arrInput[$key];
            $code = Comm_ErrorCodes::SUCCESS;
            switch ($val['type']) {
                case 'int':
                case 'float':
                    if (!is_numeric($currParam)) {
                        $code = Comm_ErrorCodes::PARAM_TYPE_ERROR;
                        break;
                    }
                    if(isset($val['min']) && $currParam < $val['min']) {
                        throw new InvalidArgumentException("PARAM '$key' MUST GREATER THAN " . $val['min'], Comm_ErrorCodes::PARAM_ILLE_ERROR);
                    }
                    if(isset($val['max']) && $currParam > $val['max']) {
                        throw new InvalidArgumentException("PARAM '$key' MUST LESS THAN " . $val['max'], Comm_ErrorCodes::PARAM_ILLE_ERROR);
                    }
                    break;
                case 'boolean':
                    if (!is_bool($currParam)) {
                        $code = Comm_ErrorCodes::PARAM_TYPE_ERROR;
                        break;
                    }
                    break;
                case 'string':
                    if (!is_string($currParam)) {
                        $code = Comm_ErrorCodes::PARAM_TYPE_ERROR;
                        break;
                    }
                    $strLen = mb_strwidth($currParam, 'UTF-8');
                    if(isset($val['min']) && $strLen < $val['min']) {
                        throw new InvalidArgumentException("PARAM '$key' LEN MUST GREATER THAN " . $val['min'], Comm_ErrorCodes::PARAM_ILLE_ERROR);
                    }
                    if(isset($val['max']) && $strLen > $val['max']) {
                        throw new InvalidArgumentException("PARAM '$key' LEN MUST LESS THAN " . $val['max'], Comm_ErrorCodes::PARAM_ILLE_ERROR);
                    }
                    break;
                case 'date':
                    if (!is_string($currParam) || $currParam != date('Y-m-d H:i:s', strtotime($currParam))) {
                        $code = Comm_ErrorCodes::PARAM_TYPE_ERROR;
                    }
                    break;
                case 'enum':
                    if (!in_array($currParam, $val['value'])) {
                        throw new InvalidArgumentException("PARAM '$key' ONLY SUPPORT (" . implode(',', $val['value']) . ')', Comm_ErrorCodes::PARAM_ILLE_ERROR);
                    }
                    break;
                case 'reg':
                    if (!preg_match($val['value'], $currParam)) {
                        throw new InvalidArgumentException("PARAM '$key' MATCHING ERR: " . $val['value'], Comm_ErrorCodes::PARAM_ILLE_ERROR);
                    }
                default:
                    break;
            }
            if ($code == Comm_ErrorCodes::PARAM_TYPE_ERROR) {
                throw new InvalidArgumentException("PARAM '$key' TYPE ERROR", $code);
            }

        }
        return true;
    }

    /**
     * 初始化当前用户信息
     * @param
     * @return
     */
    protected function _initUser()
    {
        $this->_userInfo      = Yaf_Registry::get('user');
        $this->_adminUserInfo = Yaf_Registry::get('adminUser');
    }
}
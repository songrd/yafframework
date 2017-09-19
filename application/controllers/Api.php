<?php
/**
 * Api.php
 * @desc: api 控制器
 * @author: songruidong
 * @time: 2017/9/11 12:57
 */
class Controller_Api extends Yaf_Controller_Abstract
{
    public $actions = array(
        'test' => 'actions/api/Test.php',
    );
}
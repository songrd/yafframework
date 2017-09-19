<?php
/**
 * Test.php
 * @desc: test
 * @author: songruidong
 * @time: 2017/9/11 12:59
 */
class Action_Test extends Yaf_Action_Abstract
{
    /**
     * 执行函数
     *
     * @param
     * @return
     */
    public function execute()
    {
        // curl请求
        $address = "﻿北京市丰台区晓月四里";
        $getParams = array(
            'address' => $address,
            'output'  => 'json',
            'ak'      => 'xxx',
        );
        $opt = array(
            'cookie'  => 'zzzxxx',
            'referer' => 'http://blog.songrd.com',
            'method'  => 'GET',
        );
        $header = array(
            'token:xxxaaa',
        );
        $client = new Comm_Service_Client();
        $client->setopt(array('timeout'=>1200));

        $client->call('baidumap', 'geocoder/v2/', $getParams, 'info', $opt, $header);

        $getParams['address'] = '北京市海地区';
        $client->call('baidumap', 'geocoder/v2/', $getParams, 'info2', $opt, $header);

        $client->callData();

        var_dump($client, $client->info['content'], $client->info2['content']);

        Comm_Log::warning($client->info2['content']);

        exit();

        // redis操作
        $redisObj     = new Comm_Redis('platform');
        $redisKey     = $redisObj->get('aaa');
        var_dump($redisKey);

        // 数据库操作
        $db = new Comm_Base_Dao('my_cluster');
        $r = $db->getList('goods', 'id, title' /*array('id', 'title')*/, array('id' => array(10,11,23,13,15)), "ORDER BY id asc limit 3");
        $r1 = $db->read('goods', array('id', 'title'), array(1=>1), "order by id desc limit 5");
        //$r2 = $db->getTotal('goods');
        //$r3 = $db->update('goods', array('id' => 10), array('title' => 'hello3'));
        //$r4 = $db->insert('goods', array('title' => '测试商品3', 'category_id'=>51, 'market_price'=>23, 'real_price'=>31));
        var_dump($db->lastSql,  $db->lastCost, $db->totalCost, $r, $r1, $r2, $r3, $r4);

        // 配置文件获取
        $conf = Comm_Conf::get('/db/cluster');
        var_dump($conf);

        // 获取ip地址
        $ip = Comm_Request::getClientIp();

        //记录日志
        Comm_Log::warning($ip);
        var_dump($ip);
    }
}
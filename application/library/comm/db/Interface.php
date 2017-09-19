<?php
/**
 * Interface.php
 * @desc: 数据库接口
 * @author: songruidong
 * @time: 2017/9/12 11:46
 */

interface Comm_Db_Interface
{
    /**
     * 获取一行记录
     * @return array 1维数组
     */
    public function getRow();

    /**
     * 获取一列记录
     * @return array 1维数组，指定字段组成
     */
    public function getCol();

    /**
     * 获取第一个字段的值
     * @return string 第1个字段的值
     */
    public function getOne();

    /**
     * 获取所有记录
     * @return array 2维数组
     */
    public function getAll();

    /**
     * 返回上次插入的id
     * @return int
     */
    public function lastInsertId();

    /**
     * 执行sql语句，并返回结果的资源
     * @param string $sql
     * @return resource
     */
    public function queryExe($sql);

    /**
     * 执行sql语句，直接返回sql语句影响的记录数
     * @return int
     */
    public function exec();
}
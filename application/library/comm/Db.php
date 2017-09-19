<?php
/**
 * Db.php
 * @desc: 数据库常用类
 * @author: songruidong
 * @time: 2017/9/11 22:46
 */

abstract class Comm_Db
{
    private $_defaultPk = 'id';

    // mysql 对象
    protected $mysql;
    // 最后操作的sql
    protected $lastSql;
    // 耗时
    protected $lastCost;
    // 总耗时
    protected $totalCost;
    // 是否连接
    protected $isConnected;
    // db配置文件
    protected $dbConf;

    /**
     * 执行SQL
     * 根据SQL进行主从分离
     * @param mixed $params
     * @param int $fixlimit 是否要在sql后面自动补充limit 1
     */
    public function query($params, $fixlimit = 0)
    {
        $args = func_get_args();

        if (!$params) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, array_merge($args, ['DBAbstract::query() Error!$params is empty']), 'warning');
        }

        return $this->queryExe($params, $fixlimit);
    }

    /**
     * 获取列表
     * @param array|string  array('id','name') or '*'
     * @param array $filter 条件数组或字符串，可为空
     * @param string $halfSql SQL中的limit语句，可添加order by
     * @return array 二维数组
     */
    public function getList($table, $fields=array(), $filter = array(), $halfSql = '')
    {
        $args = func_get_args();

        if (!$filter && (!$halfSql || stripos($halfSql, 'LIMIT') === false)) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, array_merge($args, ['Comm_Db::getList() Error!$filter is empty.']), 'warning');
        }
        // @TODO 支持扩展属性条件
        // $sql = "SELECT * FROM `{$this->table}` {$where} {$halfSql}";
        $sql = 'SELECT' . $this->treatField($fields) . " FROM `{$table}`" . $this->arrayToWhere($filter) . $this->treatHalfSql($halfSql);

        return $this->getAll($sql, $filter, $halfSql);
    }

    /**
     * 读取1条记录，支持各种形式的条件
     * @param string $table 表名
     * @param array|string  array('id','name') or '*'
     * @param array $filter 1、数字 id；2、 1维条件数组（array(1,2,3)或array('id'=>2000)）；3、 SQL语句WHERE…ORDER…LIMIT…\OR…\ 等；4、逗号分隔的id "1,2,3,4"
     * @param string $sql 补充的sql，在where以后，可以是  ORDER 也可以是 LIMIT，甚至 是 WHERE 的补充
     * @return array 1维数组
     * @example read('article', 2)
     * @example read('article', array('id'=>2000))
     * @example read('article', array(1,2,3,4))
     * @example read('article', "1,2,3,4")
     * @example read('article', "ORDER BY `id`"); // 读最新1条记录
     * @example read('article', "ORDER BY `id` LIMIT 1"); // 读最新1条记录（会自动补充 LIMIT 1）
     * @example read('article', array('user_id'=>2010, 'res_type'=>1)) // 条件数组
     * @example read('article', array('user_id'=>2010, 'res_type'=>1), 'ORDER BY `add_time` DESC') // 条件数组
     * @return array
     */
    public function read($table, $fields=array(), $filter, $halfSql = '')
    {
        $sql = 'SELECT' . $this->treatField($fields) . " FROM `{$table}`" . $this->arrayToWhere($filter) . $this->treatHalfSql($halfSql);

        return $this->getRow($sql, $filter);
    }

    /**
     * 删除表记录，支持各种形式的条件
     * @param string $table 表名
     * @param array $filter 参数形式参考 read()
     * @return int 执行的结果
     * @example delete('article', 1001);
     * @example delete('article', array('id'=>2000));
     * @example delete('article', array('id'=>array(110000,1000,1001)));
     * @example delete('article', array(110000,1000,1001));
     */
    public function delete($table, $filter)
    {
        $sql = "DELETE FROM `{$table}`" . $this->arrayToWhere($filter);
        return $this->exec($sql, $filter);
    }

    /**
     * 更新表记录，支持各种形式的条件
     * @param string $table 表名
     * @param array $filter 参数形式参考 read()，支持各种形式的条件
     * @param array $info 更新的内容
     * @return int 更新的记录数
     * @example update('ad', 1001, "SET num=num+1");  // +1 操作
     * @example update('ad', 1001, array('name'=>"互动阳光");
     * @example update('ad', array('id'=>2000), array('name'=>"互动阳光");
     */
    public function update($table, $filter, $info)
    {
        $params = (is_array($info)) ? array_values($info) : array();

        if ($filter && is_array($filter)) {
            foreach ($filter as $v) {
                $params[] = $v;
            }
        }

        $sql = "UPDATE `{$table}`" . $this->arrayToUpdate($info) . $this->arrayToWhere($filter);
        return $this->exec($sql, $params);
    }

    /**
     * 写入1条记录
     * @param string $table 表名
     * @param array $info 插入的数组
     * @param string|null $halfSql 附加的SQL语句  REPLACE ，或 ON DUPLICATE UPDATE……，或简写的UPDATE
     * @return int 新插入的id
     * @example create('ad', array('title'=>'alltosun'));
     * @example create('ad', array('id'=>'1000', 'title'=>'test', 'hits'=>100), 'ON DUPLICATE KEY UPDATE hits=hits+1');
     * @example create('ad', array('id'=>'1000', 'title'=>'test', 'hits'=>100), 'UPDATE hits=hits+1');
     * @example create('ad', array('id'=>'1000', 'title'=>'test', 'hits'=>100), 'REPLACE');
     */
    public function insert($table, $info, $halfSql = '')
    {
        if ($halfSql && (false !== stripos($halfSql, 'REPLACE'))) {
            $halfSql = '';
            $sql = "REPLACE INTO `{$table}`";
        } else {
            $sql = "INSERT INTO `{$table}`";
            if ($halfSql && (false !== stripos($halfSql, 'UPDATE ')) && (false === stripos($halfSql, 'ON '))) {
                $halfSql = 'ON DUPLICATE KEY ' . $halfSql;
            }
        }

        $sql .= '('.$this->treatField(array_keys($info)).') VALUES ('.implode(',', array_fill(0, count($info), '?')) . ') '. $halfSql;

        $result = $this->exec($sql, array_values($info));
        $id = $this->lastInsertId();

        if ($id) return $id;
        return $result;
    }

    /**
     * 返回指定条件的字段集，1维数组
     * @param string $table 表名
     * @param array $field 取的字段名，只支持1个字段名
     * @param array $filter 参数形式参考 read()和arrayToWhere()，支持各种形式的条件
     * @param string $halfSql SQL中的limit语句，或其它任何SQL语句
     * @param string $table 指定表
     * @return array 1维数组
     * @example getFields('ad', 'id'); // 取所有记录
     * @example getFields('ad', 'id', 'WHERE id<100 ORDER BY `id` LIMIT 10'); // 取最新10条记录
     * @example getFields('ad', 'id', 'ORDER BY `id` LIMIT 10'); // 取最新10条记录
     * @example getFields('ad', 'id', 'LIMIT 10'); // 取10条记录
     * @example getFields('ad', 'id', array('res_type' => 1), 'ORDER BY `id` LIMIT 10'); // 取 res_type=1 的最新10条记录
     * @example 更多参考 getList()
     */
    public function getFields($table, $field = 'id', $filter = array(), $halfSql = '')
    {
        $args = func_get_args();
        // $filter 可为空
        if (!$filter && (!$halfSql || stripos($halfSql, 'LIMIT') === false)) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, array_merge($args, ['Comm_Db::getFields() Error!$filter is empty.']), 'warning');
        }

        $sql = 'SELECT' . $this->treatField($field) . " FROM `{$table}`" . $this->arrayToWhere($filter, null, 0) . $this->treatHalfSql($halfSql);

        return $this->getCol($sql, $filter);
    }

    /**
     * 统计相关条件记录数
     * @param string $table 表名
     * @param array $filter 参数形式参考 read()和arrayToWhere()，支持各种形式的条件。
     *        注意：如果为空统计全表记录数。
     * @param string $halfSql SQL中的limit语句，或其它任何SQL语句
     * @return int
     * @example getTotal(); // 统计全表记录数
     * @example getTotal('ad', array('type' => 1)); // 统计 type 为1的记录数
     * @example getTotal('ad', array('type' => 1, 'name like' =>'%福%'));  // 统计 type 为1的记录数且name中包含“福”的记录数
     * @example 更多参考 getList()
     */
    public function getTotal($table, $filter = array(), $halfSql = '')
    {
        // $filter 可为空
        $sql = "SELECT COUNT(*) FROM `{$table}`" . $this->arrayToWhere($filter, NULL, 0) . $this->treatHalfSql($halfSql);

        return $this->getOne($sql, $filter);
    }

    /**
     * 构造WHERE条件SQL语句
     * 对 LIKE 的支持通过在key中添加LIKE的方式进行，array('title LIKE'=>'%allto%')
     * @param mixed $filter 1维条件数组、2维数组、id组成的字符串、简单的sql语句（不能有'"）
     * @param string $pk    PK字段的名，默认为id
     * 以下为$filter参数的具体说明
     * @return string 1、WHERE `res_id`=? AND `res_type`=?，2、WHERE `res_id` IN(?,?,?)，3、WHERE `name` LIKE ?
     * @example 0、条件为空，返回空字符串；如'',0,false 返回： ''
     * @example 1、条件为字符串
     *          1.1 将做为未来SQL的一部分，注意会过滤所以不支持'"，只支持简化的SQL。如'ORDER BY `id` LIMIT 10'、'LIMIT 10' 直接返回至未来的SQL中
     *          1.2 条件为含有逗号分隔的字符串，会转化成IN操作。如'1,2,3'，返回： WHERE `id` IN(?,?,?)
     * @example 3、条件为数字，如1，直接返回： WHERE `id`='1'
     * @example 4、条件为1维数组，key为自然数字，转换成IN操作。如array(1,2,3,4)，返回：  WHERE `id` IN(?,?,?)
     *          4.1 条件为1维数组，key为字段名，转换成条件 array('add_time'=>'2010-10-1')，返回：WHERE `add_time` = ?
     * @example 5、条件是2维数组，将依次转化成多个条件。如：array('id'=>array(1,2,3,4), 'group_id'=>array(1,2))，返回： WHERE `id` IN(?,?,?) AND `group_id` IN (?,?)
     * @example 6、对LIKE,AND,OR,>,<,=,!的支持是通过条件数组key的特殊写法来支持的
     *          6.1 对LIKE的支持 array('name LIKE'=>'%福%') 返回 WHERE `name` LIKE ?
     *          6.1 对LIKE的支持 array('title LIKE'=>array('allto%', '%tosun%')) 返回 WHERE (`title` LIKE? OR `title` LIKE?)
     *          6.2 对AND支持(key中带AND) array('add_time'=>'2010-10-1', 'AND `id`'=>'100')，返回：WHERE `add_time` =? AND `id` =?
     *          6.3 对AND支持(key中不带AND,即默认支持) array('add_time'=>'2010-10-1', 'id'=>'100')，返回：WHERE `add_time` =? AND `id` =?
     *          6.3 对OR支持(key中带OR) array('add_time'=>'2010-10-1', 'OR `add_time`'=>'2010-10-2')，返回：WHERE `add_time` =? OR `add_time` =?
     *          6.4 对>,<,=,! 支持(key中带相应符号) array('add_time >'=>'2010-10-1', 'OR `add_time` >='=>'2010-10-2')，返回：WHERE `add_time` >? OR `add_time` >=?
     */
    public function arrayToWhere(&$filter, $pk = NULL, $check = 1)
    {
        if (!$filter) {
            // 0、条件为空
            if (!$check) {
                // 不检查条件为空，即允许条件为空
                return '';
            }
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, ['Comm_Db::arrayToWhere() Error!$filter is empty.'], 'warning');
        }

        if (NULL === $pk) $pk = $this->_defaultPk;

        if (is_numeric($filter)) {
            // 3、如果只是1个数字id
            $filter = (array) $filter;
            return " WHERE `{$pk}`=?";
        } elseif (is_string($filter)) {
            if (is_numeric($filter[0]) || (strpos($filter, ',') && !strpos($filter, ' '))) {
                // 2、'1,2,3,4,5'
                // 注意：不支持 'a,b,c,d'字符串组成的keys
                // 注意：不能有空格，因空格是sql的组成部分
                $filter = $this->removeFilterBadChar(explode(',', $filter));
                return " WHERE `{$pk}` IN(" . implode(',', array_fill(0, count($filter), '?')) . ")";
            } else {
                if (' ' === $filter[0]) {
                    throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, ['Comm_Db::arrayToWhere() Error!$filter is invalid,the first character can not be space.'], 'warning');
                }
                // 1、WHERE,LIMIT,ORDER 简单的SQL语句
                // 注意：不能包含 英文单引、双引号 '"
                // 复杂的写到sql参数中
                //@todo
                return ' ' . addslashes($filter);
            }
        } elseif (isset($filter[0])) {
            // 4、如果是1维数组，array(1,2,3,4);
            return " WHERE `{$pk}` IN(" . implode(',', array_fill(0, count($filter), '?')) . ')';
        }

        $where = '';
        $sql = '';
        $parm = array();
        foreach ($filter as $k => $v) {
            // 过滤 key
            $k = $this->removeFilterBadChar($k);

            if (is_array($v)) {
                if (!$v) continue;

                if (strpos($k, '>') || strpos($k, '<') || strpos($k, '!')  || strpos($k, '=') || stripos($k, 'LIKE')) {
                    // 6.1、如果是LIKE条件的2维数组，array('title LIKE'=>array('allto%', '%tosun'))
                    $connection = '';
                    $field = $k;
                    // array('title LIKE'=>array('allto%', '%tosun'), 'OR content LIKE'=>array('all%', '%to'))
                    if (false !== stripos($k, 'OR ')) {
                        $connection = substr($k, 0, stripos($k, 'OR ') + 3);
                        $field = substr($k, stripos($k, 'OR ') + 3);
                    } elseif (false !== stripos($k, 'AND ')) {
                        $connection = substr($k, 0, stripos($k, 'AND ') + 4);
                        $field = substr($k, stripos($k, 'AND ') + 4);
                    }
                    // array('(title LIKE'=>array('allto%'), 'OR content LIKE'=>array('all%'), ') AND intro LIKE'=>array('%all'))
                    if (false !== strpos($field, '(')) {
                        $connection .= '(';
                        $field = substr($field, strpos($field, '(') + 1);
                    } elseif (false !== strpos($field, ')')) {
                        $connection = ')' . $connection;
                        $field = substr($field, strpos($field, ')') + 1);
                    }
                    $where_arr = array();
                    foreach ($v as $v1) {
                        $where_arr[] = $field . '?';
                        $parm[] = $v1;
                    }
                    $where = $connection . '(' . implode(' OR ', $where_arr).')';
                } else {
                    // 5、如果是2维数组，array('id'=>array(1,2,3,4))
                    $where = $k . ' IN(' . implode(',', array_fill(0, count($v), '?')) . ')';
                    foreach ($v as $v1) {
                        $parm[] = $v1;
                    }
                }
            } elseif (strpos($k, '>') || strpos($k, '<') || strpos($k, '!')  || strpos($k, '=') || stripos($k, 'LIKE')) {
                // 6、array('add_time >'=>'2010-10-1')，条件key中带 > < 符号
                // 6、array('add_time ='=>'2010-10-1')，条件key中带 > < 符号
                // 6、array('add_time !='=>'2010-10-1')，条件key中带 > < 符号
                // 6、array('title LIKE'=>'%福')，LIKE 的支持
                $where = $k . '?';
                $parm[] = $v;
            } else {
                // 8、array('res_type'=>1)
                $where = $k . '=?';
                $parm[] = $v;
            }

            if (!$sql) $sql = " WHERE {$where}";
            // 如 key 中有 AND OR，不处理，否则添加 AND
            else $sql = $sql . ' ' . ((false !== stripos($k, 'OR ') || false !== stripos($k, 'AND ')) ? '': 'AND ') . $where;
        }
        $filter = $parm;

        return $sql;
    }

    /**
     * 构造更新的SQL
     * @param mixed $info 0、为空，返回空；
     *                    1、如果是字串，直接返回，如：id=id+1；
     *                    2、一维数组，array('name'=>'alltosun.com')，返回：SET `name`=?
     * @return string
     */
    public function arrayToUpdate($info)
    {
        if (!$info) {
            throw new Comm_Exception_Error(Comm_SysErrorCodes::DB_TO_SQL_ERR, null, ['Comm_Db::::arrayToUpdate() Error!$info is empty.'], 'warning');
        }
        if (is_string($info)) {
            // 1、如果是SQL语句
            return $info;
        }

        // 2、1维数组，类似于array('name'=>'alltosun.com')
        $s = '';
        foreach ($info as $k => $v) {
            // 过滤 key
            if ($s) { $s .= ','; }
            $s .= '`'.$this->removeBadChar($k).'`=?';
        }

        return ' SET ' . $s;
    }

    /**
     * 处理函数后补充的sql
     * @param string $half_sql，位于where以后的补充Sql
     * @return string
     */
    public function treatHalfSql($half_sql)
    {
        if ($half_sql) {
            return ' ' . $half_sql;
        } else {
            return '';
        }
    }

    /**
     * 绑定变量，替换问号为变量值
     * @param string $sql sql语句
     * @param array $params 变量值数组
     * @return string 绑定变量后的sql语句
     */
    public function bindParam($sql, $params)
    {
        if (!$params || !is_array($params) || !strpos($sql, '?')) return $sql;

        // 进行 ? 的替换，变量替换
        $offset = 1;
        $i = 0;
        while ($offset = strpos($sql, '?', $offset)) {
            $p = $params[$i++];
            if ('`' === $sql[$offset-1] || "'" === $sql[$offset-1]) {
                $sql = substr_replace($sql, $p, $offset, 1);
            } else {
                $sql = substr_replace($sql, "'".$p."'", $offset, 1);
            }
            $offset = 1 + $offset + strlen($p);
        }

        return $sql;
    }

    /**
     * 过滤表名、字段名中的非法字符
     * @param string $name
     * @return string
     */
    public function removeBadChar($name)
    {
        return str_replace(array(' ', '`', "'", "'", ',', ';', '*', '#', '/', '\\', '%'), '', $name);
    }

    /**
     * 过滤 Filter 中的非法字符
     * @param mixed $array
     * @return mixed
     */
    public function removeFilterBadChar($array)
    {
        if (is_numeric($array)) {
            return $array;
        } elseif (!is_array($array)) {
            return str_replace(array('"', "'", ',', ';', '*', '#', '/', '\\', '%'), '', $array);
        }

        foreach ($array as $k => $v) {
            $array[$k] = $this->removeFilterBadChar($v);
        }

        return $array;
    }

    /**
     * 处理SQL中的field部分
     * @param string $field 字段名，支持逗号分隔的多个字段名
     * @return string
     */
    public function treatField($field = NULL)
    {
        if ((!$field) || ('*' === $field)) {
            return ' *';
        }

        if ($field) {
            if (is_array($field)) ;
            elseif (strpos($field, ',')) $field = explode(',', $field);
            else return ' `' . $this->removeBadChar($field) . '`';
        }

        $sql = '';
        foreach ($field as $v) {
            if ($sql) { $sql .= ','; }
            $sql .= '`' . ($v == '*') ? $v : $this->removeBadChar($v) . '`';
        }

        return ' ' . $sql;
    }

    /**
     * 处理SQL中的order部分
     * @return string
     */
    public function treatOrder($option)
    {
        if (empty($option['order'])) {
            return '';
        }

        if (strpos($option['order'][0], ',')) {
            $option['order'] = explode(',', $option['order'][0]);
        }

        $sql = '';
        foreach ($option['order'] as $v) {
            // $sql .= $this->removeBadChar($v);
            if ($sql) { $sql .= ','; }
            $sql .= $this->removeFilterBadChar($v);
        }

        return ' ORDER BY ' . $sql;
    }

    /**
     * 处理SQL中的limit部分
     * @return string
     */
    public function treatLimit($option)
    {
        if (empty($option['limit'])) {
            return '';
        }

        $sql = ' LIMIT ' . intval($option['limit'][0]);
        if (!empty($option['limit'][1])) $sql .= ',' . intval($option['limit'][1]);

        return $sql;
    }
}
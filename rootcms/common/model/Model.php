<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Model
 *
 * @author wb
 */
namespace app\common\model;

class Model extends \think\Model {
    
    //是否新增
    private $isNewRecord = false;

    /**
     * 新增数据
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @param boolean $replace 是否replace
     * @return mixed
     */
    public function add($data = '', $options = array(), $replace = false) {
        $this->isNewRecord = true;
        return parent::add($data, $options, $replace);
    }

    public function addAll($dataList, $options = array(), $replace = false) {
        $this->isNewRecord = true;
        return parent::addAll($dataList, $options, $replace);
    }

    /**
     * 对保存到数据库的数据进行处理
     * 注意：由于建表不规范，可能一些字段设置不允许NULL，然后又没有设置默认值
     *      所以对_facade进行了复写，支持这类操作
     * @access protected
     * @param mixed $data 要操作的数据
     * @return boolean
     */
    protected function _facade($data) {
        // 检查数据字段合法性
        if (!empty($this->fields)) {
            if (!empty($this->options['field'])) {
                $fields = $this->options['field'];
                unset($this->options['field']);
                if (is_string($fields)) {
                    $fields = explode(',', $fields);
                }
            } else {
                $fields = $this->fields;
            }
            //补充
            if ($this->isNewRecord === true) {
                $fileList = $fields;
                unset($fileList['_type'], $fileList['_pk']);
                foreach ($fileList as $k => $f) {
                    if (!isset($data[$f]) && $f != $fields['_pk']) {
                        $data[$f] = '';
                    }
                }
                $this->isNewRecord = false;
            }
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields, true)) {
                    if (!empty($this->options['strict'])) {
                        E(L('_DATA_TYPE_INVALID_') . ':[' . $key . '=>' . $val . ']');
                    }
                    unset($data[$key]);
                } elseif (is_scalar($val)) {
                    // 字段类型检查 和 强制转换
                    $this->_parseType($data, $key);
                }
            }
        }
        // 安全过滤
        if (!empty($this->options['filter'])) {
            $data = array_map($this->options['filter'], $data);
            unset($this->options['filter']);
        }
        $this->_before_write($data);
        return $data;
    }

    /**
     * 删除表
     * @param string $tablename 不带表前缀的表名
     * @return type
     */
    public function drop_table($tablename) {
        $tablename = config("database.prefix") . $tablename;
        return $this->query("DROP TABLE $tablename");
    }

    /**
     *  读取全部表名
     * @return type
     */
    public function list_tables() {
        $tables = array();
        $data = $this->query("SHOW TABLES");
        foreach ($data as $k => $v) {
            $tables[] = $v['tables_in_' . C("DB_NAME")];
        }
        return $tables;
    }

    /**
     * 检查表是否存在
     * $table 不带表前缀
     */
    public function table_exists($table) {
        $tables = $this->list_tables();
        return in_array(config("database.prefix") . $table, $tables) ? true : false;
    }

    /**
     * 获取表字段
     * $table 不带表前缀
     */
    public function get_fields($table) {
        $fields = array();
        $table = config("database.prefix") . $table;
        $data = $this->query("SHOW COLUMNS FROM $table");
        foreach ($data as $v) {
            $fields[$v['field']] = $v['type'];
        }
        return $fields;
    }

    /**
     * 检查字段是否存在
     * $table 不带表前缀
     */
    public function field_exists($table, $field) {
        $fields = $this->get_fields($table);
        return array_key_exists($field, $fields);
    }
        
    // 自动表单令牌验证
    // TODO  ajax无刷新多次提交暂不能满足
    public function autoCheckToken($data) {
        // 支持使用token(false) 关闭令牌验证
        //if(isset($this->options['token']) && !$this->options['token']) return true;
        if(config('TOKEN_ON')){
            $name   = config('TOKEN_NAME', null, '__hash__');
            if(!isset($data[$name]) || !isset($_SESSION[$name])) { // 令牌数据无效
                return false;
            }

            // 令牌验证
            list($key,$value)  =  explode('_',$data[$name]);
            if($value && $_SESSION[$name][$key] === $value) { // 防止重复提交
                unset($_SESSION[$name][$key]); // 验证完成销毁session
                return true;
            }
            // 开启TOKEN重置
            if(config('TOKEN_RESET')) unset($_SESSION[$name][$key]);
            return false;
        }
        return true;
    }
}

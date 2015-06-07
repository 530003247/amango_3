<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Common\Api;
class ModelApi {
    public static function del_model($name){
        $model    = D('Model');
        $model_id = $model->where(array('name'=>$name))->getField('id');
        if(!empty($model_id)){
            $model->del($model_id);
            return true;
        } else {
            return false;
        }
    }
    /**
     * 获取文档模型信息
     * @param  integer $id    模型ID
     * @param  string  $field 模型字段
     * @return array
     */
    public static function get_document_model($id = null, $field = null){
        static $list;

        /* 非法分类ID */
        if(!(is_numeric($id) || is_null($id))){
            return '';
        }

        /* 读取缓存数据 */
        if(empty($list)){
            $list = S('DOCUMENT_MODEL_LIST');
        }

        /* 获取模型名称 */
        if(empty($list)){
            $map   = array('status' => 1, 'extend' => 1);
            $model = M('Model')->where($map)->field(true)->select();
            foreach ($model as $value) {
                $list[$value['id']] = $value;
            }
            S('DOCUMENT_MODEL_LIST', $list); //更新缓存
        }

        /* 根据条件返回数据 */
        if(is_null($id)){
            return $list;
        } elseif(is_null($field)){
            return $list[$id];
        } else {
            return $list[$id][$field];
        }
    }

    // 根据模型id 获取模型title
    public static function get_model_by_id($id){
        return $model = M('Model')->getFieldById($id,'title');
    }

    // 根据模型id 获取模型name
    public static function get_model_name($id){
        static $model_name;
        if(!isset($model_name[$id])){
            $model_name[$id] = M('Model')->where(array('id'=>$id))->getField('name');
        }
        return $model_name[$id];
    }
    // 根据模型name 获取模型id
    public static function get_model_id($name){
        static $model_id;
        if(!isset($model_id[$name])){
            $model_id[$name] = M('Model')->where(array('name'=>$name))->getField('id');
        }
        return $model_id[$name];
    }
    // 根据模型name 判断实际数据表存在 $name 带前缀的完整表名
    public static function get_table_by_name($name){
        $name = C('DB_PREFIX').$name;
$sql = <<<sql
                SHOW TABLES LIKE '{$name}';
sql;
        $res = M()->query($sql);
        if(count($res)==0){
            return false;
        } else {
            return true;
        }
    }
    // 根据模型name 判断实际数据表存在 $name 带前缀的完整表名
    public static function set_table_by_model($name,$data){
        $status = D('Model')->where(array('name'=>$name))->save($data);
        return $status;
    }
    // 获取模型信息
    public static function get_model($id){
        static $model_info;
        if(!isset($model_info[$id])){
            if(is_numeric($id)){
                $model_info[$id] = M('Model')->where(array('id'=>$id))->find();
            } else {
                $model_info[$id] = M('Model')->where(array('name'=>$id))->find();
            }
        }
        return $model_info[$id];
    }
    /**
     * 获取属性信息并缓存
     * @param  integer $id    属性ID
     * @param  string  $field 要获取的字段名
     * @return string         属性信息
     */
    public static function get_model_attribute($model_id, $group = true){
        static $list;

        /* 非法ID */
        if(empty($model_id) || !is_numeric($model_id)){
            return '';
        }

        /* 读取缓存数据 */
        if(empty($list)){
            $list = S('attribute_list');
        }

        /* 获取属性 */
        if(!isset($list[$model_id])){
            $map = array('model_id'=>$model_id);
            $extend = M('Model')->getFieldById($model_id,'extend');

            if($extend){
                $map = array('model_id'=> array("in", array($model_id, $extend)));
            }
            $info = M('Attribute')->where($map)->select();
            $list[$model_id] = $info;
            //S('attribute_list', $list); //更新缓存
        }

        $attr = array();
        foreach ($list[$model_id] as $value) {
            $attr[$value['id']] = $value;
        }

        if($group){
            $sort  = M('Model')->getFieldById($model_id,'field_sort');

            if(empty($sort)){	//未排序
                $group = array(1=>array_merge($attr));
            }else{
                $group = json_decode($sort, true);

                $keys  = array_keys($group);
                foreach ($group as &$value) {
                    foreach ($value as $key => $val) {
                        $value[$key] = $attr[$val];
                        unset($attr[$val]);
                    }
                }

                if(!empty($attr)){
                    $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
                }
            }
            $attr = $group;
        }
        return $attr;
    }

    // 分析属性的枚举类型字段值 格式 a:名称1,b:名称2 或者 :fun(var1,var)
    public static function parse_field_attr($string) {
        if(0 === strpos($string,':')){
            // 采用函数定义
            return   eval(substr($string,1).';');
        }
        $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
        if(strpos($string,':')){
            $value  =   array();
            foreach ($array as $val) {
                list($k, $v) = explode(':', $val);
                $value[$k]   = $v;
            }
        }else{
            $value  =   $array;
        }
        return $value;
    }
}
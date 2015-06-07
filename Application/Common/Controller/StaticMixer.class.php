<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
/**
 * 伪静态—混合模型
 * @uses [replyName] [D方法参数指定映射模型D('参数')]
 * @param 目标类 [禁止转化法方法] deny_static = array(); 
 */
namespace Common\Controller;
abstract class StaticMixer{

    /**
     * 内部参数
     * @var  Weixin\Model\AmangoModel[类型.Model:默认为微信]
     */
    protected  static $responseParam;

    /**
     * 目标模型
     * @var  Weixin\Model\AmangoModel[类型.Model:默认为微信]
     */
    protected  static $responseModel;

    /**
     * 禁止方法
     * @var 数组
     */
    protected  static $responseAction;

    /**
     * 获取回复模型
     * @param  string  $name
     * @return mixed
     */
    protected static function getResponseModel(){
        static::$responseParam = static::replyName();
        return static::setResponseModel(static::$responseParam);
    }

    /**
     * 获取禁止方法
     * @param  string  $name
     * @return mixed
     */
    public static function getResponseAction(){
        if (isset(static::$responseAction)){
            return static::$responseAction;
        } else {
            return static::$responseAction = static::getResponseModel()->deny_static;
        }
    }

    /**
     * 设置回复模型
     * @param  string  $name
     * @return mixed
     */
    public static function setResponseModel($name){
        if (is_object($name)) return $name;
        if (isset(static::$responseModel[$name[1]])){
            return static::$responseModel[$name[1]];
        } else {
            if($name[0]=='Model'){
                return static::$responseModel[$name[1]] = D($name[1]);
            } elseif ($name[0]=='Controller') {
                return static::$responseModel[$name[1]] = A($name[1]);
            } else {
                $newname = $name[1];
                return static::$responseModel[$name[1]] = new $newname();
            }
            
        }
    }

    /**
     * 处理静态方法
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public static function __callStatic($method, $args){
        $instance    = static::getResponseModel();
        $deny_static = static::getResponseAction();
        //判断是否为可用静态方法 * 代表全部可用
        if(in_array($method, $deny_static)){
            return false;
        }
        switch (count($args)){
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($args[0]);
            case 2:
                return $instance->$method($args[0], $args[1]);
            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);
            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);
            default:
                return call_user_func_array(array($instance, $method), $args);
        }
    }
}

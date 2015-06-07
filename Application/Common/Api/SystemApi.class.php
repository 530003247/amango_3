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
class SystemApi {
    /**
     * 获取主题信息
     * @return array 主题信息数组
     */
    public static function getThemeinfo($themename,$config=''){
	    $themename = empty($themename) ? C('default_theme') : $themename;
    	$themepath = AMANGO_FILE_ROOT . '/Application/Home/'.C('default_v_layer').'/'.$themename;
    	//获取主题参数
    	$themepath = $themepath.'/Config.php';
    	if(file_exists($themepath)){
    		$themeconfig = include_once($themepath);
    	}
    	    $themeconfig['INFO']['preview'] = __ROOT__.'/Application/Home/'.C('default_v_layer').'/'.$themename.'/preview.jpg';
    	    $themeconfig['INFO']['name']    = $themename;
    	if(in_array($config,array('INFO','CONFIG'))){
            return $themeconfig[$config];
    	} else {
            return $themeconfig;
    	}
    }

    /**
     * 获取个性化模板信息
     * @return array 主题信息数组
     */
    public static function getCategoryThemelist(){
        $WEB_SITE_THEME = M('Config')->where(array('name'=>'WEB_SITE_THEME'))->getField('value');
        $themesdir      = AMANGO_FILE_ROOT . '/Application/Home/'.C('default_v_layer').'/'.$WEB_SITE_THEME.'/Article/';
        $dirs           = array_map('basename',glob($themesdir.'*', GLOB_ONLYDIR));

        $themeconfig    = array();
        $i = 0;
        foreach ($dirs as $key => $value) {
            $configPath = $themesdir.'/'.$value.'/Config.php';
            if(file_exists($configPath)){
                $i++;
                //获取每个的个性模板的配置文件
                $themeconfig[$i] = include_once($configPath);
                $themeconfig[$i]['INFO']['preview'] = __ROOT__.'/Application/Home/'.C('default_v_layer').'/'.$WEB_SITE_THEME.'/Article/'.$value.'/preview.jpg';
                $themeconfig[$i]['INFO']['name']    = $value;
            }
        }
            return $themeconfig;
    }

}
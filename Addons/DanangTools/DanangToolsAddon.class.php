<?php

namespace Addons\DanangTools;
use Common\Controller\Addon;

/**
 * 蚬港定制插件插件
 * @author 530003247
 */

    class DanangToolsAddon extends Addon{

        public $info = array(
            //插件标识
            'name'=>'DanangTools',
            //插件名称
            'title'=>'岘港定制插件',
            //插件是否含有微信Bundle 1/0
            'weixin'=>'1',
            'weixinkeyword'=>array(
                    'option'   =>array(
                        'title:天气'=>'天气',
                        'title:地图'=>'地图',
                        'title:会员'=>'会员',
                    ),
                    'post'     =>array(
                                0=>array(
                                    'keyword_post' =>'text',
                                    'keyword_rules'=>'/^(地图|天气|会员|积分)/',
                                ),
                    ),
                    'response' =>array(
                                0=>array(
                                    'response_name' =>'岘港工具盒子',
                                    'param'         =>'title:天气',
                                ),
                    ),
                    'group'    =>array(
                         '0'=>'0' 
                    ),
            ),
            //插件前台含有个人中心 1/0
            'has_profile'=>'0',
            //插件描述
            'description'=>'个性化定制关键词：“天气”“地图”“会员”',
            //插件状态
            'status'=>'1',
            //插件作者
            'author'=>'530003247',
            //插件版本
            'version'=>'0.1',
            //插件LOGO
            'logo'=>'logo.jpg',
        );
        public $custom_adminlist = 'list.html';
        public $custom_config    = 'config.html';
        public $admin_list = array(
            'model'      =>'Addonsdntickets',
            'fields'     =>'*',            
            'map'        =>'',
            'order'      =>'id desc',
            'search_key' =>'fromusername',
            'listKey'  => array(
                'fromusername' =>'微信用户',
                'name'         =>'姓名',
                'tel'          =>'电话',
                'type'         =>'主题',
                'sex'          =>'性别',
                'passport'     =>'护照号',
                'nums'         =>'客人数',
                'timeinfo'     =>'日期',
                'creattime'    =>'下单日期',
                'desc'         =>'备注',
            ),
        );
        public function install(){
            $install_sql = './Addons/DanangTools/install.sql';
            if (file_exists($install_sql)) {
                execute_sql_file($install_sql);
            }
            return true;
        }

        public function uninstall(){
                $uninstall = './Addons/DanangTools/uninstall.sql';
                if (file_exists($uninstall)) {
                  execute_sql_file($uninstall);
                }
            return true;
        }

        //实现的amango钩子方法
        public function amango($param){

        }

    }
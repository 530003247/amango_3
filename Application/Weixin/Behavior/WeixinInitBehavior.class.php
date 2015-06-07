<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
namespace Weixin\Behavior;
use Think\Behavior;
use Weixin\Controller\Credits;
use Common\Api\WxuserApi;

defined('THINK_PATH') or exit();
class WeixinInitBehavior extends Behavior {
    static private $weixin_post = array();
    static private $admin_group = array('admin');

    public function run(&$wechatinfo){
        global $_W;
        self::$weixin_post = $_W;
        //初始化用户信息
        self::init_user_info();
        //初始化系统关键词
        self::init_auto_keyword();
        return true;
    }
    
    static private function init_user_info(){
            $userinit = WxuserApi::get_infos_openid(self::$weixin_post['fromusername']);
            if(empty($userinit['id'])){
                //注册用户
                if(empty(self::$weixin_post['fromusername'])){
                    wx_error('Sorry!用户标识为空');
                } else {
                    $regStatus = WxuserApi::register_weixin(true,self::$weixin_post['fromusername']);
                    if(false===$regStatus[0]){
                        wx_error($regStatus[1]);
                    } else {
                        $userinit  = $regStatus[1];
                    }
                    //新关注用户
                    Credits::ag_weixin_newregister($userinit['id']);
                }
            }

            //判断用户公众号归属
            if(empty($userinit['tousername'])){
                WxuserApi::update_info_openid($userinit['fromusername'],array('account_union'=>self::$weixin_post['tousername']));
            }

                //用户调试状态
                if(in_array($userinit['followercate_title'], self::$admin_group)){
                    defined('WEIXIN_TRACE',TRUE);
                }

                //用户关注状态
                if(empty($userinit['follow'])){
                    WxuserApi::update_follow($userinit['fromusername']);
                }
                
                //注册用户昵称
                if(preg_match("/^我叫/",self::$weixin_post['content'])){
                    $nickname  = str_replace('我叫', '', str_replace(" ", '', self::$weixin_post['content']));
                    //set_nickname(self::$weixin_post['fromusername'],$nickname );
                    $setStatus = WxuserApi::set_nickname(array(self::$weixin_post['fromusername']=>$nickname));
                    if($setStatus){
                        wx_success($nickname.'小主,更新昵称成功！');
                    } else {
                        wx_error('更新昵称失败！');
                    }
                }

                //强制绑定昵称
                if(empty($userinit['nickname'])){
                    wx_success('发送“我叫”+您的昵称,交朋友更方便哦~');
                }

                if($userinit['status']==0){
                    wx_error('Sorry!您的账号已被冻结,请联系管理员......');
                }
                if($userinit['followercate_status']==0){
                    wx_error('Sorry!您所在的用户组【'.$userinit['followercate_title'].'】已被冻结,请联系管理员......');
                }
                /*用户资料初始化*/
                global $_P;$_P = $userinit;
                //用户自动升级
                Credits::init($userinit['id']);
    }
    
    //自动响应系统级关键词
    static protected function init_auto_keyword() {
        //判断是否为 微笑  发送 UC 自动登录链接
        if(preg_match('/^\/微笑$/', self::$weixin_post['content'])){
            global $_P;
            $loginparam = array(
                   'nickname'   => $_P['nickname'],
                   'ucusername' => $_P['ucusername'],
                   'ucpassword' => $_P['ucpassword'],
                );
            $autolink = U('/User/login',$loginparam,'',true);
            $other    = "【内置关键词】\n发送“我叫***”即可改昵称";
            wx_success("亲爱的{$_P['nickname']}\nUC账号:{$_P['ucusername']}\nUC密码:{$_P['ucpassword']}\r\n<a href='{$autolink}'>自动登录网页版</a>\n".$other);
        }
    }
}
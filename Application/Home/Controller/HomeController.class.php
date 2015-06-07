<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Think\Controller;
use User\Api\UserApi;
/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class HomeController extends Controller {

	/* 空操作，用于输出404页面 */
	public function _empty(){
		$this->redirect('Index/index');
	}
	//先获取用户openid  进行比对 发现不存在 则进行二次获取 写入数据库
	//获取到用户信息    进行自动 注册入库 自动登陆
    protected function _initialize(){
    	//初始化积分
    	$loginstatus = is_login();
    	if($loginstatus){
            //用户自动升级
            $userid  = session('P.id');
            Credits::init($userid);
            //更新用户资料
            $newinfo = api('Wxuser/get_info',array('id'=>$userid));
            if(!empty($newinfo)){
                session('P',null);
                session('P',$newinfo);
            }
    	}
            //初始化主题 参数
            A('Amangotheme')->init_config();
            //初始化公众号网站配置
            A('Amangotheme')->get_account();
            global $_K;
            $locationurl = __SELF__;
            if($_K['DEFAULT']['account_getuserinfo']!='0'){
            	A('Amangotheme')->weixin_get_info($locationurl);
            }

	            if(!empty($_GET['ucusername'])&&!empty($_GET['ucpassword'])&&$loginstatus==0){
	                $this->auto_login($_GET['ucusername'],$_GET['ucpassword']);
		         	//判断是否存在
		         	$replace_list = array();
		         	$urldepr      = C('URL_PATHINFO_DEPR');
		         	if(!empty($_GET['ucusername'])){
	                    $replace_list[$urldepr.'ucusername'.$urldepr.$_GET['ucusername']] = '';
		         	}
		         	if(!empty($_GET['ucpassword'])){
	                    $replace_list[$urldepr.'ucpassword'.$urldepr.$_GET['ucpassword']] = '';
		         	}
		    		//提取后缀前
		         	$redirect_url   = strtr($locationurl,$replace_list);
					redirect($redirect_url);
	            }
	            if(!empty($param)){
		    		$suffix         = C('TMPL_TEMPLATE_SUFFIX');
		    		$parse_url      = explode($suffix, $locationurl);
					redirect($parse_url[0].$suffix);
	            }
    }

    //自动登陆
    protected function auto_login($username, $password){
        $user = new UserApi;
			$uid = $user->login($username, $password);
			if(0 < $uid){ //UC登录成功
				$Member = M('Weixinmember')->where(array('ucmember'=>$uid))->find();
				if(!empty($Member)){ //登录用户
			        $auth = array(
			            'uid'             => $Member['id'],
			            'username'        => $Member['nickname'],
			            'last_login_time' => time(),
			            'uidtype'         => 'user',
			        );
			        session('P', $Member);
			        session('user_auth', $auth);
			        session('user_auth_sign', data_auth_sign($auth));
			        return true;
				}
			}
			    return false;
    }
	/* 用户登录检测 */
	protected function login(){
		/* 用户登录检测 */
		is_login() || $this->error('您还没有登录，请先登录！', U('User/login'));
	}
	protected function setShare($imgurl,$url,$title,$content){
		if(strpos($url, 'http://') === false){
			$url    = Amango_U('Article/lists',array('category'=>'wsqxxgl'));
		}
		if(empty($imgurl)){
			$imgurl = 'http://'.$_SERVER['HTTP_HOST'].'/Public/logo.jpg';
		} else {
			$imgurl = get_cover_pic($imgurl);
		}
		if(empty($title)){
			$title  = C('WEB_SITE_TITLE');
		}
		$content   = $title.$content;
        $Shareinfo = array(
					'ImgUrl'     =>$imgurl,
					'TimeLink'   =>$url,
					'FriendLink' =>$url,
					'WeiboLink'  =>$url,
					'tTitle'     =>$title,
					'tContent'   =>$content,
					'fTitle'     =>$title,
					'fContent'   =>$content,
					'wContent'   =>$content
        );
        $this->assign('Share',$Shareinfo);
	}
	protected function logic($name){
		$name  = ucfirst(empty($name) ? $this->logicmodel : $name);
        $class = is_file(MODULE_PATH . 'Logic/' . $name . 'Logic' . EXT) ? $name : 'Base';
        $class = MODULE_NAME . '\\Logic\\' . $class . 'Logic';
        return new $class($name); 
	}
}

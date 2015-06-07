<?php
namespace Home\Controller;
use Think\Controller;
use User\Api\UserApi;
class AmangothemeController extends Controller {
    public function init_theme($themename,$modulename){
        if(MODULE_NAME=='Home'){
            $themename = empty($themename) ? C('default_theme') : $themename;
            $themepath = AMANGO_FILE_ROOT . '/Application/Home/'.C('default_v_layer').'/'.$themename;
            //获取主题参数
            $themeconfig = $themepath.'/Config.php';
            if(file_exists($themeconfig)){
                $thememparam = include_once($themeconfig);
            }
            //主题运行环境
            $browserstatus = $this->check_browser($thememparam['CONFIG']['browser_limit']);
            if(true!==$browserstatus){
                echo '<script language="javascript">alert("'.$browserstatus.'");</script>'.$browserstatus;die;
            }
            //TODO 主题预定义参数
            // if(!empty($thememparam['CONFIG'])){
            // }
            //主题资源路径 默认为ASSET文件夹
            $assetpath   = empty($thememparam['CONFIG']['assetpath']) ? 'ASSET' : $thememparam['CONFIG']['assetpath'];
            $assetpath   = __ROOT__.'/Application/Home/'.C('default_v_layer').'/'.$themename.'/'.$assetpath;
            $reset_tmpl_parse = array(
                'TMPL_PARSE_STRING' => array(
                    '__PUBLIC__' => $assetpath,
                    '__STATIC__' => $assetpath . '/static',
                    '__CSS__'    => $assetpath . '/css',
                    '__JS__'     => $assetpath . '/js',
                ),
            );
            if($modulename!='addons'){
                defined('THEME_NAME') or define('THEME_NAME', $themename);
                defined('THEME_PATH') or define('THEME_PATH', $themepath.'/');
                $reset_tmpl_parse['DEFAULT_THEME'] = $themename;
            }
            C($reset_tmpl_parse);
            return true;
        }
    }
    protected function check_browser($allowtype='Auto'){
    	//判断 PC 移动浏览器
	    $detect   = import('Home.ORG.Mobile_Detect','','.php');
	    $detect   = new \Mobile_Detect;
	    $isMobile = $detect->isMobile();
	    $isTablet = $detect->isTablet();
	    if($isMobile||$isTablet){
	    	session('browser_type','wap');
	    } else {
	    	session('browser_type','pc');
	    }
    	switch ($allowtype) {
    		case 'Weixin':
				if (strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')==false) {
					    $errormsg = '请在微信浏览器中打开';
				}	
    			break;
    		case 'Pc':
    		    if(true===$isMobile||true===$isTablet){
                    $errormsg = '请在PC浏览器中打开';
    		    } 
    			break;
    		case 'Mobile':
    		    if(false===$isMobile&&false===$isTablet){
                    $errormsg = '请在手机或平板浏览器中打开';
    		    } 
    			break;
    		case 'Tablet':
    		    if(false===$isTablet){
                    $errormsg = '请在平板浏览器中打开';
    		    } 
    			break;
    	}
    	        return empty($errormsg) ? true : $errormsg;
    }
    public function init_config($modulename){
        /* 读取数据库中的配置 */
        $config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =   api('Config/lists');
            S('DB_CONFIG_DATA',$config);
        }
        //添加配置
        C($config);

            $this->init_theme(C('WEB_SITE_THEME'),$modulename);
            if(!C('WEB_SITE_CLOSE')){
                $this->error('站点已经关闭，请稍后访问~');
            }
            //浏览器参数
            $this->assign('browser_type',session('browser_type'));
            //申明全局变量   默认网站信息
            global $_W;
                   $_W    = $config;
            return true;
    }
    public function get_account(){
        global $_K;
        if(empty($_K)){
            $accountmodel = M('Account');
            $map = array();
            $defaultlist  = $accountmodel->where(array('account_default'=>'default'))->find();
                $map['account_default']  = array('neq','default');
            $otherlist    = $accountmodel->where($map)->select();
            //申明全局变量   默认微信公众号信息
                   $_K['DEFAULT'] = $defaultlist;
                   $_K['OTHER']   = $otherlist;
        } else {
            $defaultlist = $_K['DEFAULT'];
        }
            return $defaultlist;
    }

    protected function parse_oauth_url($type=1,$url){
        if($type==1){
            $newparam = array();
            //不存在
            if((strpos($url, '&code=') === false)&&(strpos($url, '&state=') === false)){
                return '';
            }
            //同时存在
            if((strpos($url, '&code=') !== false)&&(strpos($url, '&state=') !== false)){
                $apiparam  = explode('&code=', $url);
                $api_param = explode('&state=', $apiparam[1]);
                $newparam['code']  = $api_param[0];
                $newparam['state'] = $api_param[1];
            } else {
                //只存在state
                if(strpos($url, '&state=') !== false){
                    $api_param  = explode('&state=', $url);
                    $newparam['state'] = $api_param[1];
                } else {
                    $api_param  = explode('&code=', $url);
                    $newparam['code'] = $api_param[1];
                }
            }
                return $newparam;
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
    public function weixin_get_info($locationurl){
        $defaultlist = $this->get_account();
        if(IS_GET){
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                    $param = $this->parse_oauth_url(1,$locationurl);
                    //判断是否同时存在code和scope
                    if(!empty($param['code'])&&!empty($param['state'])){
                        if($param['state']=='GET_USERINFO'){
                           $url  = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$defaultlist['account_appid'].'&secret='.$defaultlist['account_secret'].'&code='.$param['code'].'&grant_type=authorization_code';
                            $apiinfo     = $this->api_get($url);
                            $userinfourl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$apiinfo['access_token'].'&openid='.$apiinfo['openid'].'&lang=zh_CN';
                            $userinfo    = $this->api_get($userinfourl);
                            if(is_array($userinfo)){
                                //注册账号 同时注册UC WEIXIN
                                $resinfo = api('Wxuser/register_weixin',array('auto'=>true,'fromusername'=>$apiinfo['openid']));
                                if($resinfo[0]==true){
                                    $newdata = array(
                                        'follow'     => 0,
                                        'nickname'   => $userinfo['nickname'],
                                        'sex'        => $userinfo['sex'],
                                        'language'   => $userinfo['language'],
                                        'city'       => $userinfo['city'],
                                        'province'   => $userinfo['province'],
                                        'country'    => $userinfo['country'],
                                        'headimgurl' => $userinfo['headimgurl'],
                                        'privilege'  => implode(',', $userinfo['privilege']),
                                    );
                                    api('Wxuser/update_info_openid',array('openid'=>$apiinfo['openid'],'data'=>$newdata));
                                }
                            }
                            $userinfos  = api('Wxuser/get_info_openid',array('openid'=>$apiinfo['openid']));
                            $this->auto_login($userinfos['ucusername'],$userinfos['ucpassword']);
                        }
                        if($param['state']=='GET_USEROPENID'){
                            //直接识别openid  判断是否存在  登陆后原地址跳转  不存在写入后原地跳转
                            $url  = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$defaultlist['account_appid'].'&secret='.$defaultlist['account_secret'].'&code='.$param['code'].'&grant_type=authorization_code';
                            $apiinfo   = $this->api_get($url);
                            //openid获取用户信息
                            $userinfo  = api('Wxuser/get_info_openid',array('openid'=>$apiinfo['openid']));
                            //如果存在
                            if($userinfo['id']>0){
                                //未同步资料
                                if(empty($userinfo['nickname'])||$userinfo['nickname']=='游客'||empty($userinfo['headimgurl'])){
                                    $api       = api('Weixin','Common',false);
                                    $user_info = $api->get_MembersInfo($apiinfo['openid']);
                                    //更新个人资料
                                    if($user_info[0]==true){
                                        unset($user_info[1]['openid']);
                                        unset($user_info[1]['subscribe']);
                                        unset($user_info[1]['subscribe_time']);
                                        api('Wxuser/update_info_openid',array('openid'=>$apiinfo['openid'],'data'=>$user_info[1]));
                                    }
                                } else {
                                //已同步资料
                                    $this->auto_login($userinfo['ucusername'],$userinfo['ucpassword']);
                                }
                            } else {
                                //如果不存在  提示授权
                                $this->weixin_oauth('snsapi_userinfo');
                            }
                        }
                    } else {
                        //先读取基础
                        //判断是否登陆
                        $loginstatus = is_login();
                        if($loginstatus==0&&IS_GET){
                            $this->weixin_oauth('snsapi_base');
                        }
                    }
                }
        }        
    }
    private function weixin_oauth($scope='snsapi_base'){
        $defaultlist = $this->get_account();
        if(!empty($defaultlist['account_appid'])&&in_array($scope, array('snsapi_base','snsapi_userinfo'))){
            $scope_type     = array(
                   'snsapi_base' => 'GET_USEROPENID',
               'snsapi_userinfo' => 'GET_USERINFO'
               );
            $state          = $scope_type[$scope];
            $snsapi_appid   = $defaultlist['account_appid'];
            //提取后缀前
            $suffix         = C('TMPL_TEMPLATE_SUFFIX');
            $parse_url      = explode($suffix, __SELF__);
            $urlencode      = urlencode('http://'.$_SERVER['HTTP_HOST'].$parse_url[0].$suffix);
            $snsapi_baseurl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$snsapi_appid}&redirect_uri={$urlencode}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
            redirect($snsapi_baseurl);
        }
    }
    protected function api_get($url){
        $output = file_get_contents($url);
        return json_decode($output,true);
    }
}

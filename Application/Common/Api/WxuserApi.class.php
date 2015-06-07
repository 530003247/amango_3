<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
namespace Common\Api;
class WxuserApi {

    //更新用户昵称 支持批量 array('openid=>昵称)
    public static function set_nickname($fromName){
        $statusArray = array();
        $usermodel   = M('Weixinmember');
        foreach ($fromName as $key => $value) {
            $status  = $usermodel->where(array('fromusername'=>$key))->save(array('nickname'=>$value));
            $statusArray[$key] = $status;
        }
           return $statusArray;
    }

    //更新用户更新
    public static function update_follow($fromusername){
        $tr = "update ".C('DB_PREFIX')."weixinmember set follow=(follow+1)%2 where fromusername='$fromusername'";
        return M('Weixinmember')->execute($tr); 
    }

    //随机字符串
    public static function rand_simplekeys($length){
         $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ_#!'; //字符池
         $key     = '';
         for($i=0;$i<$length;$i++){
                $key.=$pattern[mt_rand(0,60)];//生成php随机数
         }
            return $key;
    }

    //注册微信用户  正确返回id  错误返回错误信息
    public static function register_weixin($is_auto=false, $fromusername, $username, $password, $email){
        if(empty($fromusername)&&empty($username)&&empty($password)){
            return array(false,'注册账号不能为空');
        }
        //判断用户是否已经注册
        $info = self::get_info_openid($fromusername);
        if(empty($info)){
            $nowtime  = time();
            $password = empty($password) ? self::rand_simplekeys(9) : $password; //默认密码
            if($is_auto){
                $username = self::rand_simplekeys(9);//默认用户名
                $email    = empty($email) ? $username.'@qq.com' : $email; //默认邮箱
            } else {
                $email    = empty($email) ? self::rand_simplekeys(9).'@qq.com' : $email; //默认邮箱
            }
            $User = new \User\Api\UserApi;
            $uid  = $User->register($username, $password, $email);//同步注册Uenter
            if(0 < $uid){ //注册成功
                $userdata = array(
                    'fromusername'=>$fromusername,
                    'group'       =>'general',
                    'follow'      =>1,
                    'status'      =>1,
                    'sex'         =>1,
                    'lasttime'    =>$nowtime,
                    'regtime'     =>$nowtime,
                    'ucmember'    =>$uid,
                    'ucusername'  =>$username,
                    'ucpassword'  =>$password,
                );
                $wxmodel = M('Weixinmember');
                $status  = $wxmodel->add($userdata);//写入 微信用户关注表
                if($status==false){
                    $MSG = $wxmodel->getError();
                    return array(false,$MSG);
                } else {
                    $userinfo = D('WeixinmemberView')->where(array('ucmember'=>$uid))->find();
                    return array(true,$userinfo);
                }
            } else {
                switch ($uid) {//注册失败，显示错误信息
                    case -1:  $error = '用户名长度必须在16个字符以内！'; break;
                    case -2:  $error = '用户名被禁止注册！'; break;
                    case -3:  $error = '用户名被占用！'; break;
                    case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
                    case -5:  $error = '邮箱格式不正确！'; break;
                    case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
                    case -7:  $error = '邮箱被禁止注册！'; break;
                    case -8:  $error = '邮箱被占用！'; break;
                    case -9:  $error = '手机格式不正确！'; break;
                    case -10: $error = '手机被禁止注册！'; break;
                    case -11: $error = '手机号被占用！'; break;
                    default:  $error = '未知错误';
                }
                    return array(false,$error);
            }
        }
            return array(true,$info['id']);
    }

    public static function get_infos_openid($openid){
        if (empty($openid)) {
            return false;
        } else {
            $info = D("WeixinmemberView")->where(array('fromusername' => $openid))->find();
        }
            return $info;
    }

    public static function get_info_openid($openid,$field=null){
        if (empty($openid)) {
            return false;
        } else {
            $info = M('Weixinmember')->where(array('fromusername'=>$openid))->field($field)->find();
        }
        return $info;
    }

    public static function get_openid2id($openid){
        static $useridlist;
        if(!isset($useridlist[$openid])){
            $useridlist[$openid] = M('Weixinmember')->where(array('fromusername'=>$openid))->getField('id');
        }
            return $useridlist[$openid];
    }

    public static function update_info_id($id,$data){
        $status = M('Weixinmember')->where(array('id'=>$id))->save($data);
        return $status;
    }

    public static function update_info_openid($openid,$data){
        $status = M('Weixinmember')->where(array('fromusername'=>$openid))->save($data);
        return $status;
    }

    /**
     * 获取微信用户的信息
     * @param  integer $id    用户ID
     * @param  string  $field 要获取的字段名
     * @return array         分类信息
     */
    public static function get_info($id=null,$field=null){
        if (empty($id)) {
            return false;
        } else {
            $info = empty($field) ? M('Weixinmember')->where(array('id'=>$id))->find() : M('Weixinmember')->where(array('id'=>$id))->field($field)->find();
        }
        return empty($info) ? false : $info;
    }

    /**
     * 获取微信用户列表
     * @param  string  $field 要获取的字段名
     * @return array          用户列表
     */
    public static function get_info_list($field=null){
        $list = empty($field) ? M('Weixinmember')->select() : M('Weixinmember')->field($field)->select();
        return empty($info) ? false : $list;
    }

    /**
     * 更新微信用户的信息
     * @param  integer $id    用户ID
     * @param  string  $data  要更新的数据
     * @return array          分类信息
     */
    public static function update_info($id=null,$data=null){
        if (empty($id)) {
            return false;
        } 

        if (empty($data)) {
            return true;
        }

        $oldpass = self::get_info($id,'ucpassword,ucmember');
            if(strlen(strtolower($data['ucpassword']))>=9&&$oldpass['ucpassword']!=$data['ucpassword']){
                //同步更改Uenter的密码
                $User    = new \User\Api\UserApi;
                $return  = $User->updateInfo($id, $oldpass['ucpassword'], array('ucpassword'=>$data['ucpassword']));
                if($return['status']===false){
                    return false;
                }
            } else {
                unset($data['ucpassword']);
            }

        M('Weixinmember')->where(array('id'=>$id))->save($data);
        return true;
    }
    
    //表情转换
    static public function replace_emoji($str){
        static $amango_bq =Array("/::)"=>"/微笑","/::~"=>"/撇嘴","/::B"=>"/色","/::|"=>"/发呆","/:8-)"=>"/得意","/::<"=>"/流泪","/::$"=>"/害羞","/::X"=>"/闭嘴","/::Z"=>"/睡","/::'("=>"/大哭","/::-|"=>"/尴尬","/::@"=>"/发怒","/::P"=>"/调皮","/::D"=>"/呲牙","/::O"=>"/惊讶","/::("=>"/难过","/::+"=>"/酷","/:--b"=>"/冷汗","/::Q"=>"/抓狂","/::T"=>"吐","/:,@P"=>"/偷笑","/:,@-D"=>"/愉快","/::d"=>"/白眼","/:,@o"=>"/傲慢","/::g"=>"/饥饿","/:|-)"=>"/困","/::!"=>"/惊恐","/::L"=>"/流汗","/::>"=>"/憨笑","/::,@"=>"/悠闲","/:,@f"=>"/奋斗","/::-S"=>"/咒骂","/:?"=>"/疑问","/:,@x"=>"/嘘","/:,@@"=>"/晕","/::8"=>"/疯了","/:,@!"=>"/衰","/:!!!"=>"/骷髅","/:xx"=>"/敲打","/:bye"=>"/再见","/:wipe"=>"/擦汗","/:dig"=>"/抠鼻","/:handclap"=>"鼓掌","/:&-("=>"/糗大了","/:B-)"=>"/坏笑","/:<@"=>"/左哼哼","/:@>"=>"/右哼哼","/::-O"=>"/哈欠","/:>-|"=>"/鄙视","/:P-("=>"/委屈","/::'|"=>"/快哭了","/:X-)"=>"/阴险","/::*"=>"/亲亲","/:@x"=>"/吓","/:8*"=>"/可怜","/:pd"=>"/菜刀","/:<W>"=>"/西瓜","/:beer"=>"/啤酒","/:basketb"=>"/篮球","/:oo"=>"/乒乓","/:coffee"=>"/咖啡","/:eat"=>"/饭","/:pig"=>"/猪头","/:rose"=>"/玫瑰","/:fade"=>"/凋谢","/:showlove"=>"嘴唇","/:heart"=>"/爱心","/:break"=>"/心碎","/:cake"=>"/蛋糕","/:li"=>"/闪电","/:bome"=>"/炸弹","/:kn"=>"/刀","/:footb"=>"/足球","/:ladybug"=>"/瓢虫","/:shit"=>"/便便","/:moon"=>"/月亮","/:sun"=>"/太阳","/:gift"=>"/礼物","/:hug"=>"/拥抱","/:strong"=>"/强","/:weak"=>"/弱","/:share"=>"/握手","/:v"=>"/胜利","/:@)"=>"/抱拳","/:jj"=>"/勾引","/:@@"=>"/拳头","/:bad"=>"/差劲","/:lvu"=>"/爱你","/:no"=>"/NO","/:ok"=>"/OK","/:love"=>"爱情","/:<L>"=>"/飞吻","/:jump"=>"/跳跳","/:shake"=>"/发抖","/:<O>"=>"/怄火","/:circle"=>"/转圈","/:kotow"=>"/磕头","/:turn"=>"/回头","/:skip"=>"/跳绳","/:oY"=>"/投降","/:#-0"=>"/激动","/:hiphot"=>"/乱舞","/:kiss"=>"/献吻","/:<&"=>"/左太极","/:&>"=>"/右太极");
             $str = strtr($str,$amango_bq);
        return $str;
    }
}
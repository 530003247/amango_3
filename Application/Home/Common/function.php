<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 前台公共库文件
 * 主要定义前台公共函数库
 */

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function check_verify($code, $id = 1){
	$verify = new \Think\Verify();
	return $verify->check($code, $id);
}

/**
 * 获取列表总行数
 * @param  string  $category 分类ID
 * @param  integer $status   数据状态
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_list_count($category, $status = 1){
    static $count;
    if(!isset($count[$category])){
        $count[$category] = D('Document')->listCount($category, $status);
    }
    return $count[$category];
}

/**
 * 获取段落总数
 * @param  string $id 文档ID
 * @return integer    段落总数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_part_count($id){
    static $count;
    if(!isset($count[$id])){
        $count[$id] = D('Document')->partCount($id);
    }
    return $count[$id];
}

/**
 * 获取导航URL
 * @param  string $url 导航URL
 * @return string      解析或的url
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_nav_url($url){
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
        case '#' === substr($url, 0, 1):
            break;        
        default:
            $url = U($url);
            break;
    }
    return $url;
}
/**
 * 获取网站用户昵称
 * @param  string $id 用户id $type 用户类型[admin/user]
 * @return string     用户昵称
 */
function get_cms_username($id,$type='admin'){
    static $cms_username;
    if(!isset($cms_username[$id])){
        if(strtolower($type)=='admin'){
            $cmsname = M('Member')->where(array('uid'=>$id))->getField('nickname');
        } else {
            $cmsname = M('Weixinmember')->where(array('id'=>$id))->getField('nickname');
        }
        $cms_username[$id] = ($cmsname=='')? '匿名用户':$cmsname;
    }
        return $cms_username[$id];
}
/**
 * 获取网站用户头像
 * @param  string $id 用户id $type 用户类型[admin/user]
 * @return string     用户头像地址
 */
function get_cms_userpic($id,$type='admin'){
    static $cms_userpic;
    if(!isset($cms_userpic[$id])){
        if(strtolower($type)=='admin'){
            $headimgurl = __ROOT__.'./Public/'.C('WEB_SITE_QR');
        } else {
            $defaultimg = 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';
            $headimgurl = M('Weixinmember')->where(array('id'=>$id))->getField('headimgurl');
        }
        $cms_userpic[$id] = ($headimgurl=='')? 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==':$headimgurl;
    }
        return $cms_userpic[$id];
}
/**
 * 获取帖子回复数目
 * @param  string $id 帖子id
 * @return string     回复数目
 */
function get_cms_tiezireplys($id){
    //获取回复模型
    $replytablename  = D('Replydocument')->replytable;
    $replynums       = M('Replydocument')->where(array('documentid'=>$id))->count();
    //echo 1111;die;
    return $replynums;
}
/**
 * 替换表情
 * @param  string $id 帖子id
 * @return string     回复数目
 */
function get_cms_samils($content){
    //替换普通表情
    $content = ex_qqface($content);
    //替换emoji表情
    $content = emoji($content);
    return $content;
}
function ex_qqface($str){
    static $newwxemotion;
    if(empty($newwxemotion)){
        $wxemotion = Array("[微笑]"=>0,"[撇嘴]"=>1,"[色]"=>2,"[发呆]"=>3,"[得意]"=>4,"[流泪]"=>5,"[害羞]"=>6,"[闭嘴]"=>7,"[睡]"=>8,"[大哭]"=>9,"[尴尬]"=>10,"[发怒]"=>11,"[调皮]"=>12,"[呲牙]"=>13,"[惊讶]"=>14,"[难过]"=>15,"[酷]"=>16,"[冷汗]"=>17,"[抓狂]"=>18,"[吐]"=>19,"[偷笑]"=>20,"[愉快]"=>21,"[白眼]"=>22,"[傲慢]"=>23,"[饥饿]"=>24,"[困]"=>25,"[惊恐]"=>26,"[流汗]"=>27,"[憨笑]"=>28,"[悠闲]"=>29,"[奋斗]"=>30,"[咒骂]"=>31,"[疑问]"=>32,"[嘘]"=>33,"[晕]"=>34,"[疯了]"=>35,"[衰]"=>36,"[骷髅]"=>37,"[敲打]"=>38,"[再见]"=>39,"[擦汗]"=>40,"[抠鼻]"=>41,"鼓掌]"=>42,"[糗大了]"=>43,"[坏笑]"=>44,"[左哼哼]"=>45,"[右哼哼]"=>46,"[哈欠]"=>47,"[鄙视]"=>48,"[委屈]"=>49,"[快哭了]"=>50,"[阴险]"=>51,"[亲亲]"=>52,"[吓]"=>53,"[可怜]"=>54,"[菜刀]"=>55,"[西瓜]"=>56,"[啤酒]"=>57,"[篮球]"=>58,"[乒乓]"=>59,"[咖啡]"=>60,"[饭]"=>61,"[猪头]"=>62,"[玫瑰]"=>63,"[凋谢]"=>64,"嘴唇]"=>65,"[爱心]"=>66,"[心碎]"=>67,"[蛋糕]"=>68,"[闪电]"=>69,"[炸弹]"=>70,"[刀]"=>71,"[足球]"=>72,"[瓢虫]"=>73,"[便便]"=>74,"[月亮]"=>75,"[太阳]"=>76,"[礼物]"=>77,"[拥抱]"=>78,"[强]"=>79,"[弱]"=>80,"[握手]"=>81,"[胜利]"=>82,"[抱拳]"=>83,"[勾引]"=>84,"[拳头]"=>85,"[差劲]"=>86,"[爱你]"=>87,"[NO]"=>88,"[OK]"=>89,"爱情]"=>90,"[飞吻]"=>91,"[跳跳]"=>92,"[发抖]"=>93,"[怄火]"=>94,"[转圈]"=>95,"[磕头]"=>96,"[回头]"=>97,"[跳绳]"=>98,"[投降]"=>99,"[激动]"=>100,"[乱舞]"=>101,"[献吻]"=>102,"[左太极]"=>103,"[右太极]"=>104);
        foreach ($wxemotion as $key => $value) {
            $newwxemotion[$key] = qqface_path($value);
        }
    }
        $str = strtr($str,$newwxemotion);
        return $str;
}
function qqface_path($wxemotion){
    return '<img src="'.__ROOT__.'/Public/static/kindeditor/plugins/emoticons/images/'.$wxemotion.'.gif"';
}
function countWords($str){
return (mb_strlen($str, 'utf8') + strlen($str))/2;
}
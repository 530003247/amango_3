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
use Common\Api\WxuserApi;

defined('THINK_PATH') or exit();
class WeixinFilterBehavior extends Behavior {
    public function run(&$wechatinfo){
    	//预定义出来表情
        if(!empty($wechatinfo['content'])){
            $wechatinfo['content'] = WxuserApi::replace_emoji($wechatinfo['content']);                    
        }
        //预定义出来语音识别
        //$wechatinfo['powerby'] = 'Amango.微信公众号管理系统';
    }
}
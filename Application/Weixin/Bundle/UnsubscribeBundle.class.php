<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
namespace Weixin\Bundle;
use Common\Controller\Bundle;
use Common\Api\WxuserApi;
class UnsubscribeBundle extends Bundle{
    
    public function run(){
        global $_P;
        WxuserApi::update_follow($_P['fromusername']);
        echo '取消关注成功';die;
    }
    //日志
    public function log(){
            return true;
    }
    //空操作
    public function _empty($type){
        wx_error('请联系管理员添加【'.$type.'】请求类型吧~');
    }
}
?>
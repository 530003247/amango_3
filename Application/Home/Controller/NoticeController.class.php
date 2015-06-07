<?php
namespace Home\Controller;

class NoticeController extends HomeController {
    protected $diypagename   = 'Diypages';
    //展示页面
    public function _empty($pagesname){
        $current_path = AMANGO_FILE_ROOT . '/Application/Home/'.C('default_v_layer').'/'.THEME_NAME.'/'.$this->diypagename.'/'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX');
        if(!file_exists($current_path)){
            $this->error('找不到该页面！');
        } else {
            $this->display($this->diypagename.'/'.ACTION_NAME);
        }  
    }
    //微信反馈通知
    public function wxpay(){
        S('Weixinpay',$_POST);
    }
}

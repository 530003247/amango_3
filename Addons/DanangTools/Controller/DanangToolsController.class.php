<?php

namespace Addons\DanangTools\Controller;
use Home\Controller\AddonsController;

class DanangToolsController extends AddonsController{
	//addonsdnconfig配置表
   public function diyproducts(){
   	   $model = M('Addonsdnconfig');
   	   $key   = 'productlinks';
   	   if(IS_POST){
   	   	   $data['value'] = I('post.productlinks');
           $model->where(array('key'=>$key))->save($data);
           $this->success('更新产品展示链接成功！');
   	   } else {
   	   	    $huodonlinks = $model->where(array('key'=>$key))->getField('value');
   	   	    $this->assign($key,$huodonlinks);
            $this->display();
   	   }
   }
   public function diyviprules(){
   	   $model = M('Addonsdnconfig');
   	   $key   = 'viprules';
   	   if(IS_POST){
   	   	   $data['value'] = I('post.viprules');
           $model->where(array('key'=>$key))->save($data);
           $this->success('更新会员规则成功！');
   	   } else {
   	   	    $huodonlinks[$key] = stripslashes($model->where(array('key'=>$key))->getField('value'));
   	   	    $this->assign($key,$huodonlinks);
            $this->display();
   	   }
   }
   public function diyviplinks(){
   	   $model = M('Addonsdnconfig');
   	   $key   = 'huodonlinks';
   	   if(IS_POST){
   	   	   $data['value'] = I('post.huodonlinks');
           $model->where(array('key'=>$key))->save($data);
           $this->success('更新会员活动链接成功！');
   	   } else {
   	   	    $huodonlinks = $model->where(array('key'=>$key))->getField('value');
   	   	    $this->assign($key,$huodonlinks);
            $this->display();
   	   }
   }
   public function diyvipinfo(){
   	   $model = M('Addonsdnconfig');
   	   $key   = 'vipdesc';
   	   if(IS_POST){
   	   	   //识别积分是否为数字 chuji gaoji zhizun
   	   	   $postinfo = I('post.');
   	   	   if(!is_numeric($postinfo['chuji'])||!is_numeric($postinfo['gaoji'])||!is_numeric($postinfo['zhizun'])){
               $this->error('积分设置必须为数字');
   	   	   }
   	   	   if(($postinfo['chuji']>=$postinfo['gaoji'])||($postinfo['gaoji']>=$postinfo['zhizun'])){
               $this->error('初级积分[小于]高级积分[小于]至尊积分');
   	   	   }
   	   	   $data['value']     = json_encode(array($postinfo['chuji'],$postinfo['gaoji'],$postinfo['zhizun']),true);
   	   	   $datadesc['value'] = $postinfo['vipdesc'];
           $model->where(array('key'=>$key))->save($datadesc);
           $model->where(array('key'=>'scorelist'))->save($data);
           $this->success('更新会员待遇及等级成功！');
   	   } else {
   	   	    $huodonlinks[$key] = stripslashes($model->where(array('key'=>$key))->getField('value'));
   	   	    $scorelist         = json_decode($model->where(array('key'=>'scorelist'))->getField('value'),true);
   	   	    $this->assign($key,$huodonlinks);
   	   	    $this->assign('score',$scorelist);
            $this->display();
   	   }
   }
   //订单详情
   public function productsdetail(){
      if(IS_POST){
        dump(I('post.'));
      } else {
         $id   = I('get.id');
         $info = M('Addonsdntickets')->where(array('id'=>$id))->find();
         if(empty($info)){
            $this->error('请选择要查看的订单');
         } else {
            $allow_type = array('marry'=>'婚礼','meet'=>'会议','golf'=>'高尔夫','party'=>'派对','conference'=>'发布会','other'=>'其他');
            $bus_type   = array('bus1'=>'豪华轿车','bus2'=>'豪华中巴','bus3'=>'商务包车');
            $rom_type   = array('rom1'=>'大床房','rom2'=>'单人房','rom3'=>'双人房');
            $sextype    = array('女士','男士');
            $info['romtype'] = $rom_type[$info['romtype']];
            $info['bustype'] = $bus_type[$info['bustype']];
            $info['type']    = $allow_type[$info['type']];
            $info['sex']     = $sextype[$info['sex']];
            if($info['renttype']==1){
                $info['renttype'] = '租赁';
            } else {
                $info['renttype'] = '不租赁';
            }
            //解析预订类型
            $this->assign('info',$info);
            
            
            $this->display();
         }
      }
   }
   public function tips(){
      $ids = array_unique((array)I('id',0));
      if ( empty($ids) ) {
          $this->error('请选择要提醒的订单ID!');
      }
      dump($ids);
   }
   public function del(){
        $ids = array_unique((array)I('id',0));
        if ( empty($ids) ) {
            $this->error('请选择要删除的订单!');
        }
        $map = array('id' => array('in', $ids) );
        $delstatus = M('Addonsdntickets')->where($map)->delete();
        if($delstatus===false){
            $this->error('删除订单失败');
        } else {
            $this->success('删除【'.$delstatus.'】条订单成功');
        }
   }
}

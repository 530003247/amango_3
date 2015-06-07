<?php
namespace Admin\Controller;

/**
 * 后台配置控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class WxpayController extends AdminController {

    /**
     * 配置管理
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        $model        = D('shop_orders');
        $order_detail = M('order_detail');
        $total        = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $list = $model->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
        $this->assign('_page',$page->show());
        $this->assign('_list',$list);
        $this->display();
    }
    //支付记录
    public function manage(){
        $model = M('wxpay_userhongbao');
        $total = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 20;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $userlist    = $model->limit($page->firstRow.','.$page->listRows)->order('create_time desc')->select();
        $list        = array();
        $detailmodel = M('wxpay_hongbao');
        foreach ($userlist as $key => $value) {
            $info       = array();
            $info       = $detailmodel->where(array('id'=>$value['hongbaoid']))->find();
            $list[$key] = array_merge($value,$info);
        }
        $this->assign('_page',$page->show());
        $this->assign('_list',$list);
        $this->display();
    }
    //删除订单
    public function deloreder(){
        $id = array_unique((array)I('ids'));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $shop_orders = M('shop_orders');
        $item_order  = M('order_detail');
        $item_goods  = M('docunment_shop');
        foreach ($id as $key => $value) {
            $order = array();
            $order = $shop_orders->where(array('orderId'=>$value))->find();
            if(!empty($order)){
                $order_details = array();
                $condition     = array('orderId'=>$value);
                $order_details = $item_order->where($condition)->select();
                foreach ($order_details as $val){
                    $item_goods->where(array('id'=>$val['itemId']))->setInc('shop_goods_stock',$val['quantity']);
                }
                    $item_order->where($condition)->delete();
                    $shop_orders->where($condition)->delete();
            }
        }
            $this->success('删除订单成功!');
    }
    //查看订单详情
    public function order_detail(){
        $orderId = I('get.ids');
        !$orderId && $this->error('该订单不存在');
        $item_order = M('shop_orders');
        $order      = $item_order->where("orderId='$orderId'")->find();
        if(!is_array($order)){
            $this->error('该订单不存在');
        }else {
            $order_detail  = M('order_detail');
            $order_details = $order_detail->where("orderId='".$order['orderId']."'")->select();
            $item_detail   = array();
            foreach ($order_details as $val){
                $items= array('title'=>$val['title'],'img'=>$val['img'],'price'=>$val['price'],'quantity'=>$val['quantity']);
                $item_detail[]=$items;
            }
        }
            $this->assign('_list',$item_detail);
            $this->assign('order',$order);
            $this->display();    

    }
    //更改订单状态
    public function setstatus(){
        $type = I('get.type');
        $ids  = I('get.ids');
        if(in_array($type,array('1','2','3','4'))&&!empty($ids)){
            $status = M('shop_orders')->where(array('orderId'=>$ids))->save(array('status'=>$type));
            if($status){
                $this->success('设置订单状态成功！');
            } else {
                $this->error('设置订单状态失败！');
            }
        }
    }
    //删除支付记录
    public function creditdel(){
        $id = array_unique((array)I('ids'));
        if ( empty($id) ) {
            $this->error('请选择要操作的积分记录!');
        }
        $map = array('id' => array('in', $id) );
        if(M('credits_log')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }
    //用户地址管理
    public function useraddress_index(){
        $model    = D('shop_useraddress');
        $total    = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $list = $model->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
        $this->assign('_page',$page->show());
        $this->assign('_list',$list);
        $this->display();
    }
    //红包列表
    public function hongbaolist(){
        $model    = D('wxpay_hongbao');
        $total    = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $list = $model->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
        $this->assign('_page',$page->show());
        $this->assign('_list',$list);
        $this->display();
    }
    //删除红包
    public function delhongbao(){
        $id = array_unique((array)I('ids'));
        if ( empty($id) ) {
            $this->error('请选择要操作的红包!');
        }
        $map = array('id' => array('in', $id) );
        if(M('wxpay_hongbao')->where($map)->delete()){
            $this->success('删除微信红包成功');
        } else {
            $this->error('删除微信红包失败！');
        }
    }
    //新增微信红包
    public function addhongbao(){
        if(IS_POST){
            $postinfo = I('post.');
            if(empty($postinfo['title'])){
                $this->error('请填写红包简称');
            }
            if(empty($postinfo['img'])){
                $this->error('请设置红包图标');
            }
            if(empty($postinfo['desc'])){
                $this->error('请填写红包介绍');
            }
            if(empty($postinfo['credits'])||!is_numeric($postinfo['credits'])||$postinfo['credits']<1){
                $this->error('请填写消耗积分，只能为正整数哦~');
            }
            if(empty($postinfo['value'])||!is_numeric($postinfo['value'])||$postinfo['value']<1){
                $this->error('请填写红包面值，只能为正整数哦~');
            }
            if(empty($postinfo['allow_group'])){
                $this->error('请选择该红包适用的用户组~');
            } else {
                $postinfo['allow_group'] = implode(',', $postinfo['allow_group']);
            }
            $begin = strtotime($postinfo['start_time']);
            $end   = strtotime($postinfo['end_time']);
            if($begin>=$end){
                $this->error('开始的有效日期必须小于结束的有效日期');
            } else {
                $postinfo['start_time'] = $begin;
                $postinfo['end_time']   = $end;
            }
            $postinfo['status'] = 1;
            if(!empty($postinfo['id'])){
                $map['id'] = $postinfo['id'];
                unset($postinfo['id']);
                $msg = '编辑';
                $status = M('wxpay_hongbao')->where($map)->save($postinfo);
            } else {
                $msg = '新增';
                $status = M('wxpay_hongbao')->add($postinfo);
            }
            if($status==false){
                $this->error($msg.'微信红包失败！');
            } else {
                $this->success($msg.'微信红包成功！',U('hongbaolist'));
            }
        } else {
            $userlist = M('followercate')->where(array('status'=>1))->select();
            $this->assign('userlist',$userlist);
            $this->display();
        }
    }
    //编辑红包
    public function edithongbao(){
        $id   = I('get.ids');
        $info = M('wxpay_hongbao')->where(array('id'=>$id))->find();
        if(empty($info)){
            $this->error('请选择要编辑的用户红包!');
        }  else {
            $userlist = M('followercate')->where(array('status'=>1))->select();
            $this->assign('userlist',$userlist);
            $this->assign('info',$info);
            $this->display('addhongbao');
        }
    }
    //红包操作列表
    public function hongbaomanage(){
        $model    = D('wxpay_userhongbao');
        $total    = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $list = $model->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
        $this->assign('_page',$page->show());
        $this->assign('_list',$list);
        $this->display();
    }
    //删除支付记录
    public function deluserhongbao(){
        $id = array_unique((array)I('ids'));
        if ( empty($id) ) {
            $this->error('请选择要操作用户微信红包记录!');
        }
        $map = array('id' => array('in', $id) );
        if(M('wxpay_userhongbao')->where($map)->delete()){
            $this->success('删除用户微信红包记录成功');
        } else {
            $this->error('删除用户微信红包记录失败！');
        }
    }
    //微信支付配置
    public function config(){
        $paylist = array('wxpay'=>'微信支付','hdfk'=>'货到付款');
        $info    = array();
        $list    = M('wxpay_config')->select();
        foreach ($list as $key => $value) {
            $info[$value['key']] = $value['value'];
        }
        $userlist = M('followercate')->where(array('status'=>1))->select();
        if(IS_POST){
            $data = I('post.');
            $data['Allow_type'] = implode(',', $data['Allow_type']);
            $param = parse_config($data['hb_leiji']);
            foreach ($userlist as $key => $value) {
                $newgroup[] = $value['followercate_title'];
            }
            foreach ($param as $key => $value) {
                if($value>0){
                    if(!in_array($key, $newgroup)){
                        $this->error($key.'会员标识:面值；会员标识必须为可用标识');
                    }
                } else {
                    $this->error('会员标识:面值；面值必须为数值');
                }
            }
            foreach ($data as $key => $value) {
                M('wxpay_config')->where(array('key'=>$key))->save(array('value'=>$value));
            }
                $this->success('配置信息成功！');
        } else {
            $this->assign('paylist',$paylist);
            $this->assign('info',$info);
            $this->assign('userlist',$userlist);
            $this->display();
        }
    }
}

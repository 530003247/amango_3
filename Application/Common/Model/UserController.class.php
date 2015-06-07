<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use User\Api\UserApi;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController {
	public $profile_model = array(
                    'SHOP'=>'shop',
		   );

	/* 用户中心首页 */
	public function index(){
		$this->display();
	}
	/* 注册页面 */
	public function register($username = '', $password = '', $repassword = '', $email = '', $verify = ''){
        if(!C('USER_ALLOW_REGISTER')){
            $this->error('注册已关闭');
        }
		if(IS_POST){ //注册用户
			/* 检测验证码 */
			if(!check_verify($verify)){
				$this->error('验证码输入错误！');
			}
			/* 检测密码 */
			if($password != $repassword){
				$this->error('密码和重复密码不一致！');
			}			
			/* 调用注册接口注册用户 */
            $User = new UserApi;
			$uid = $User->register($username, $password, $email);
			if(0 < $uid){ //注册成功
				//TODO: 发送验证邮件
				$this->success('注册成功！',U('login'));
			} else { //注册失败，显示错误信息
				$this->error($this->showRegError($uid));
			}

		} else { //显示注册表单
			$this->display();
		}
	}

	/* 登录页面 */
	public function login($username = '', $password = '', $verify = ''){
		if(IS_POST){ //登录验证
			/* 检测验证码 暂不检查*/
			// if(!check_verify($verify)){
			// 	$this->error('验证码输入错误！');
			// }
			// $goto = $_GET['amangogoto'];
			// $goto = base64_decode(base64_decode($goto));
   //          dump($goto);die;
			/* 调用UC登录接口登录 */
			$user = new UserApi;
			$uid = $user->login($username, $password);
			if(0 < $uid){ //UC登录成功
				$Member = M('Weixinmember')->where(array('ucmember'=>$uid))->find();
				if(!empty($Member)){ //登录用户
			        $auth = array(
			            'uid'             => $Member['id'],
			            'username'        => $Member['nickname'],
			            'last_login_time' => time(),
			        );
			        session('P', $Member);
			        session('user_auth', $auth);
			        session('user_auth_sign', data_auth_sign($auth));

			        $goto = $_GET['amangogoto'];
			        $goto = base64_decode(base64_decode($goto));
			        $url  = empty($goto) ? U('Home/User/profile') : $goto;

					$this->success('正在进入',$url);
				} else {
					$this->error('请输入正确的授权账号和密码');
				}
			} else { //登录失败
				switch($uid) {
					case -1: $error = 'UC用户不存在或被禁用！'; break; //系统级别禁用
					case -2: $error = 'UC密码错误！'; break;
					default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
				}
				$this->error($error);
			}

		} else { //显示登录表单
			$userinfo = session('user_auth');
			if(!empty($userinfo)){
		        $goto = $_GET['amangogoto'];
		        $goto = base64_decode(base64_decode($goto));
		        $url  = empty($goto) ? U('Home/User/profile') : $goto;
		        redirect($url);
			}
	        $shareurl  = Amango_U('User/login','','',true);
	        $content   = '芒果,是一种校园生活方式';
	        $Shareinfo = array(
						'ImgUrl'     =>'',
						'TimeLink'   =>$shareurl,
						'FriendLink' =>$shareurl,
						'WeiboLink'  =>$shareurl,
						'tTitle'     =>'同一个芒果,演绎不同的精彩',
						'tContent'   =>$content,
						'fTitle'     =>'同一个芒果,演绎不同的精彩',
						'fContent'   =>$content,
						'wContent'   =>$content
	        	        );
	        $this->assign('Share',$Shareinfo);
			empty($_GET['nickname']) || $this->assign('autonickname',$_GET['nickname']);
			empty($_GET['ucusername']) || $this->assign('autoucusername',$_GET['ucusername']);
			empty($_GET['ucpassword']) || $this->assign('autoucpassword',$_GET['ucpassword']);
			$this->display();
		}
	}

	/* 退出登录 */
	public function logout(){
		if(is_login()){
			global $_P;
			$_P = '';
			session('p', null);
			session('P', null);
	        session('user_auth', null);
	        session('user_auth_sign', null);
			$this->success('退出成功！', U('User/login'));
		} else {
			$this->redirect('User/login');
		}
	}

	/* 验证码，用于登录和注册 */
	public function verify(){
		$verify = new \Think\Verify();
		$verify->entry(1);
	}

	/**
	 * 获取用户注册错误信息
	 * @param  integer $code 错误编码
	 * @return string        错误信息
	 */
	private function showRegError($code = 0){
		switch ($code) {
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
		return $error;
	}
    public function is_login(){
		if ( !is_login() ) {
			$this->error( '您还没有登陆',U('User/login') );
		}
    }
    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function profile(){
        $this->is_login();
        if ( IS_POST ) {
        	$userinfo = session('P');
        	if(empty($userinfo['id'])){
                $this->error('请确定您修改的身份');
        	}
        	$nickname = I('nickname');
        	if(empty($nickname)){
                $this->error('昵称不能修改为空');
        	}
            $data['nickname'] = $nickname;
            $data['qq']       = I('qq');
            $data['sex']      = I('sex');
            $data['location'] = I('address');
            $status = M('Weixinmember')->where(array('id'=>$userinfo['id']))->save($data);
            //TODO   UC绑定的数据
            if($status){
            	$Member = M('Weixinmember')->where(array('id'=>$userinfo['id']))->find();
		        $auth = array(
		            'uid'             => $Member['id'],
		            'username'        => $Member['nickname'],
		            'last_login_time' => time(),
		        );
		        session('P', $Member);
		        session('user_auth', $auth);
		        session('user_auth_sign', data_auth_sign($auth));
                $this->success('更新个人资料成功！');
            }else{
                $this->error('更新个人资料失败！');
            }
            //获取参数
            // $uid        =   is_login();
            // $password   =   I('post.old');
            // $repassword = I('post.repassword');
            // $data['password'] = I('post.password');
            // empty($password) && $this->error('请输入原密码');
            // empty($data['password']) && $this->error('请输入新密码');
            // empty($repassword) && $this->error('请输入确认密码');

            // if($data['password'] !== $repassword){
            //     $this->error('您输入的新密码与确认密码不一致');
            // }

            // $Api = new UserApi();
            // $res = $Api->updateInfo($uid, $password, $data);
            // if($res['status']){
            //     $this->success('修改密码成功！');
            // }else{
            //     $this->error($res['info']);
            // }
        }else{
            $uid      = session('user_auth.uid');
            $userinfo = session('P');
        	//读取话题
        	$topics = M('Document')->where(array('uid'=>$uid,'usertype'=>'user'))->count();
        	//读取积分
            $socre  = Credits::getUserCredits($uid);
            $socre  = empty($socre) ? 0 : $socre;
            //读取订单
            $orders_nums = M('shop_orders')->count();
            //读取红包
            $orders_nums = M('shop_orders')->count();
    	    $map['userid'] = $uid;
    	    $map['type']   = 1;
    	    $map['status'] = 1;
        	$hongbao       = M('wxpay_userhongbao')->where($map)->count();
            
            $this->assign('info',$userinfo);
            $this->assign('topics',$topics);
            $this->assign('hongbao',$hongbao);
            $this->assign('orders',$orders_nums);
            $this->assign('score',$socre);
            $this->assign('HideAll',1);
            $this->display();
        }
    }

    /*********** 订单操作开始 ***********/
    private function book_menu($userinfo){
    	$status   = I('get.status',1);
    	$shopid   = M('Model')->where(array('name'=>$this->profile_model['SHOP']))->getField('id');
    	$shoplist = M('Category')->where(array('model'=>$shopid,'pid'=>0,'status'=>1))->select();
    	
        $this->assign('shoplist',$shoplist);
    	$this->assign('status',$status);
    	$this->assign('info',$userinfo);
    }
    public function bookprofile(){
    	$this->is_login();
    	$userinfo = session('P');
        $this->book_menu($userinfo);
    	//订单状态  1待付款  2待发货  3待收货   4完成
	    $item_order   = M('shop_orders');
	    $order_detail = M('order_detail');
	    if(!isset($_GET['status'])){
	       $status=1;
	    } else {
	      	$status=$_GET['status'];
	    }
	    $map['status'] = $status;
	    $map['userId'] = $userinfo['id'];
        $item_orders   = $item_order->order('id desc')->where($map)->select();
        foreach ($item_orders as  $key=>$val){
      	    $order_details = $order_detail->where("orderId='".$val['orderId']."'")->select();
      	        foreach ($order_details as $val){
      	            $items = array('title'=>$val['title'],'img'=>$val['img'],'price'=>$val['price'],'quantity'=>$val['quantity'],'itemId'=>$val['itemId']);
      	            $item_orders[$key]['items'][]=$items;
      	        }
        }
        $this->assign('HideAll',1);
        $this->assign('item_orders',$item_orders);
        $this->assign('status',$status);
    	$this->display();
    }
    public function bookcart(){
    	$this->is_login();
    	//从购物车中移除
    	if(IS_AJAX){
	    	import('@.ORG.Cart');
	    	$cart=new \Cart();
	    	$goodId= I('post.itemId','','intval');//商品ID
	    	$cart->delItem($goodId);
	    	echo json_encode(array('status'=>1));
    	} else {
	    	$userinfo = session('P');
	        $this->book_menu($userinfo);
	        import('@.ORG.Cart');
	        $cart = new \Cart();
		    $this->assign('item',$_SESSION['cart']);
		    $this->assign('sumPrice',$cart->getPrice());
		    $this->assign('HideAll',1);
	    	$this->display();
    	}
    }
    public function bookaddress(){
    	$this->is_login();
    	$userinfo = session('P');
        $user_address_mod = M('shop_useraddress');
        $id   = I('get.id','', 'intval');
        $type = I('get.type','edit', 'intval');
        if (IS_POST) {
			$consignee = I('post.consignee', '','trim');
			$address   = I('post.address', '','trim');
			$mobile    = I('post.phone_mob','', 'trim');
			$sheng     = I('post.sheng', '','trim');
			$shi       = I('post.shi', '','trim');
			$qu        = I('post.qu', '','trim');
            $id        = I('post.id', '','intval');
            if ($id) {
                $result = $user_address_mod->where(array('id'=>$id, 'uid'=>$userinfo['id']))->save(array(
						'consignee' => $consignee,
						'address'   => $address,
						'mobile'    => $mobile,
						'sheng'     => $sheng,
						'shi'       => $shi,
						'qu'        => $qu,
                ));
                if ($result) {
                    $msg = array('status'=>1, 'info'=>'编辑地址成功');
                } else {
                    $msg = array('status'=>0, 'info'=>'编辑地址失败');
                }
            } else {
                $result = $user_address_mod->add(array(
						'uid'       => $userinfo['id'],
						'consignee' => $consignee,
						'address'   => $address,
						'zip'       => $zip,
						'mobile'    => $mobile,
						'sheng'     => $sheng,
						'shi'       => $shi,
						'qu'        => $qu,
                ));
                if ($result) {
                    $msg = array('status'=>1, 'info'=>'新增地址成功');
                } else {
                    $msg = array('status'=>0, 'info'=>'新增地址失败');
                }
            }
                $this->success($msg['info'],U('bookaddress'));
        } else {
	        if ($id) {
	            if ($type == 'del') {
	                $status = $user_address_mod->where(array('id'=>$id, 'uid'=>$userinfo['id']))->delete();
	                $msg = array('status'=>1, 'info'=>'删除地址成功');
	                $this->assign('msg', $msg);
	            } else {
	                $info = $user_address_mod->find($id);
	                $this->assign('info', $info);
	            }
	        }
	        $this->book_menu($userinfo);
	        $address_list = $user_address_mod->where(array('uid'=>$userinfo['id']))->select();
	        $this->assign('address_list', $address_list);
	        $this->assign('HideAll',1);
	        $this->display();
        }
    }
    public function bookedit_address(){
    	$this->is_login();
        $user_address_mod = M('shop_useraddress');
        $id   = I('get.id', 'intval');
        $info = $user_address_mod->find($id);
       
        $this->assign('info', $info);
        $this->assign('HideAll',1);
    	$this->display();
    }
    public function bookeadd_address(){
    	$this->is_login();
    	$userinfo = session('P');
    	if(IS_POST){
			$user_address = M('shop_useraddress');
			$consignee    = I('post.consignee','', 'trim');
			$sheng        = I('post.sheng','', 'trim');
			$shi          = I('post.shi', '','trim');
			$qu           = I('post.qu', '','trim');
			$address      = I('post.address', '','trim');
			$phone_mob    = I('post.phone_mob', '','trim');
	    	
			$data['uid']       = $userinfo['id'];
			$data['consignee'] = $consignee;
			$data['sheng']     = $sheng;
			$data['shi']       = $shi;
			$data['qu']        = $qu;
			$data['address']   = $address;
			$data['mobile']    = $phone_mob;
	        if($user_address->add($data)!==false){
	               $this->redirect('User/bookaddress');
	        }
    	} else {
    		$this->book_menu($userinfo);
    		$this->assign('HideAll',1);
    		$this->display();
    	}
    }
    //取消订单
	public  function cancelOrder(){
    	$this->is_login();
    	$userinfo = session('P');
	    $orderId  = $_GET['orderId'];
	   !$orderId && $this->error('请选择要取消的订单');
		$map['orderId']  = $orderId;
		$map['userId']   = $userinfo['id'];
		$shop_orders     = M('shop_orders');
		$order           = $shop_orders->where($map)->find();
	    if(!is_array($order)){
	  	    $this->error('该订单不存在');
	    }else {
	    	$item_order = M('order_detail');
	   		$order_details = $item_order->where('orderId='.$orderId)->select();
	   		$item_goods    = M('docunment_shop');
	   		foreach ($order_details as $val){
	            $item_goods->where(array('id'=>$val['itemId']))->setInc('shop_goods_stock',$val['quantity']);
	   		}
	   		    $item_order->where('orderId='.$orderId)->delete();
	   		    $shop_orders->where("orderId='$orderId'")->delete();
	   		    $this->success('取消【'.$orderId.'】订单成功！');
	    }
	}
	public  function checkOrder(){//查看订单
    	$this->is_login();
    	$userinfo = session('P');
    	$this->book_menu($userinfo);
	    $orderId  = $_GET['orderId'];
	    !$orderId && $this->error('该订单不存在');
		$status     = $_GET['status'];
		$item_order = M('shop_orders');
		$userId     = $userinfo['id'];
		$order      = $item_order->where("orderId='$orderId' and userId='$userId'")->find();
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
		    $this->assign('item_detail',$item_detail);
			$this->assign('order',$order);
			$this->assign('HideAll',1);
			$this->display();
	}
	//确认收货
	public function confirmOrder(){
    	$this->is_login();
    	$userinfo = session('P');

	    $orderId  = I('get.orderId');
		$status   = I('get.status');
	    !$orderId && $this->error('订单不存在');
	    $item_order  = M('shop_orders');
	    $item        = M('Document_shop');
	    $item_orders = $item_order->where('orderId='.$orderId.' and userId='.$userinfo['id'].' and status=3')->find();
	    if(!is_array($item_orders)){
	     	$this->error('该订单不存在!');
	    }
	    //确认收货
	    $data['status'] = 4;
	    $map['orderId'] = $orderId;
	    $map['userId']  = $userinfo['id'];
	    if($item_order->where($map)->save($data)){
	     	$order_detail  = M('order_detail');
	     	$order_details = $order_detail->where(array('orderId'=>$orderId))->select();
	    	foreach ($order_details as $val){
	     	    $item->where(array('id'=>$val['itemId']))->setInc('shop_buy_num',$val['quantity']);
	        }
	     	    $this->redirect('User/bookprofile?status='.$status);
	    }else {
	     	    $this->error('确认收货失败');
	    }
	}
    /*********** 订单操作结束 ***********/
    public function hongbaoprofile(){
    	$this->is_login();
    	$userinfo = session('P');
        $this->book_menu($userinfo);
        $status = I('get.status',1);
        $socre  = Credits::getUserCredits($userinfo['id']);
        $socre  = empty($socre) ? 0 : $socre;
        //1 已拥有 2 消费  3 官方列表
        switch ($status) {
        	case '2':
        	    $map['userid'] = $userinfo['id'];
        	    $map['type']   = 1;
        	    $map['status'] = 2;
        	    $list          = M('wxpay_userhongbao')->where($map)->select();
        		break;
        	case '3':
        	    //是否允许积分兑换
        	    $wxpay_config     = $this->ag_hongbao_config();
        	    $list = array();
        	    if(!empty($wxpay_config ['hb_dh_auth'])){
			        $time = time();
			        $map['start_time']  = array('lt',$time);
			        $map['end_time']    = array('gt',$time);
			        $map['credits']     = array('gt',1);
			        $map['status']      = 1;
			        //匹配该用户适用的红包
	                $map['allow_group'] = array('like','%'.$userinfo['cate_group'].'%');
			        $list = M('wxpay_hongbao')->where($map)->order('end_time asc')->limit(20)->select();
        	    }
        		break;
        	default:
        	    $map['userid'] = $userinfo['id'];
        	    $map['type']   = 1;
        	    $map['status'] = 1;
        	    $userlist      = M('wxpay_userhongbao')->where($map)->select();
        	    $list          = array();
        	    $detailmodel   = M('wxpay_hongbao');
                
                $time              = time();
		        $map['start_time'] = array('lt',$time);
		        $map['end_time']   = array('gt',$time);
		        $map['credits']    = array('gt',1);
		        $map['status']     = 1;

        	    foreach ($userlist as $key => $value) {
        	    	$info = array();
                    $map['id']  = $value['hongbaoid'];
                    $info       = $detailmodel->where($map)->find();
        	    	$list[$key] = array_merge($value,$info);
        	    }
        		break;
        }
            $this->assign('_list',$list);
            $this->assign('score',$socre);
            $this->assign('HideAll',1);
    	    $this->display();
    }
    //TODO  红包接口
    public function ag_hongbao_config(){
        $list = M('wxpay_config')->select();
        $info = array();
        foreach ($list as $key => $value) {
        	$info[$value['key']] = $value['value'];
        }
        $info['hb_leiji']   = parse_config($info['hb_leiji']);
        $info['Allow_type'] = explode(',', $info['Allow_type']);
        return $info;
    }
    public function gethongbao(){
    	$this->is_login();
    	$userinfo = session('P');
        //读取微信红包领取配置
        $wxpay_config     = $this->ag_hongbao_config();
        //是否限制红包兑换
        if(empty($wxpay_config ['hb_dh_auth'])){
            $this->error('亲，暂时无法领取红包~');
        }
        $wxpay_user_score = $wxpay_config['hb_leiji'][$userinfo['cate_group']];
        //用户组是否可以领红包
        if(empty($wxpay_user_score)){
            $this->error('亲，您所在的用户组暂时无法领取红包~');
        }

    	//兑换比例因子
    	$id    = I('get.id');
        $time  = time();
        $map['start_time'] = array('lt',$time);
        $map['end_time']   = array('gt',$time);
        $map['credits']    = array('gt',1);
        $map['status']     = 1;
        $map['id']         = $id;
        $info  = M('wxpay_hongbao')->where($map)->find();
        //判断今日红包兑换面值是否超过
        $nowdate               = strtotime(date('Y-m-d'));
        $oldmap['create_time'] = array('egt',$nowdate);
        $oldmap['userid']      = $userinfo['id'];
        $all_value = M('wxpay_userhongbao')->where($oldmap)->field('hb_value')->sum('hb_value');
        $now_value = $info['value'] + $all_value;
        if($wxpay_user_score<$now_value){
            $this->error('亲，改天再来吧，您今天兑换面值已经超过咯~');
        }
        $socre = Credits::getUserCredits($userinfo['id']);
        $socre = empty($socre) ? 0 : $socre;
        //兑换积分
        if(!empty($info)&&$info['credits']<=$socre){
        	$nowscore            = $socre - $info['credits'];
        	$data['userid']      = $userinfo['id'];
        	$data['hongbaoid']   = $info['id'];
        	//获取红包详情
            $data['hb_star']     = $info['start_time'];
            $data['hb_end']      = $info['end_time'];
            $data['hb_title']    = $info['title'];
            $data['hb_pic']      = $info['img'];
            $data['hb_value']    = $info['value'];

        	$data['type']        = '1';
        	$data['create_time'] = time();
        	$data['status']      = 1;
        	//TODO  统一积分接口
        	api('Wxuser/update_info_id',array('id'=>$userinfo['id'],'data'=>array('score'=>$nowscore)));
        	M('wxpay_userhongbao')->add($data);
        	$this->success('恭喜您，兑换红包成功',U('hongbaoprofile',array('status'=>1)));
        } else {
        	$this->error('该红包已经过期咯~');
        }
    }
    //TODO 支付Logo
    //支付记录
    public function pay_log($type,$userid,$money,$logdata){
    	$data['userid']      = $userid;
    	$data['usertype']    = 'user';
    	$data['type']        = $type;
    	$data['orderdetail'] = $logdata;
    	$data['status']      = 1;
    	$data['create_time'] = time();
    	$data['money']       = $money;
    	M('wxpay')->add($data);
    }
    //最后完成支付
    public function pay(){
    	$this->is_login();
    	$userinfo = session('P');
		if(IS_POST){
			$payment_hb = $_POST['payment_hb'];
			$payment_id = $_POST['payment_id'];
			$orderid    = $_POST['orderid'];
			$dingdanhao = $_POST['dingdanhao'];
			$userId     = $userinfo['id'];
			$ordermodel = M('shop_orders');
			$item_order = $ordermodel->where("userId='$userId' and orderId='$dingdanhao'")->find();
		
			!$item_order && $this->error('请选择正确的订单号');
            $paycondition = array('userId'=>$userId,'orderId'=>$dingdanhao);
            //读取微信红包领取配置
            $wxpay_config = $this->ag_hongbao_config();
    		$hb_id    = 0;
			$hb_value = 0;
			//启用红包减免费用
			if(!empty($payment_hb)&&!empty($wxpay_config['hb_dh_diyong'])){
				$hbmodel          = M('wxpay_userhongbao');
				//获取红包减免信息
		        $oldmap['userid'] = $userinfo['id'];
		        $oldmap['id']     = $payment_hb;
		        $oldmap['status'] = 1;
		        $hb_value         = $hbmodel->where($oldmap)->getField('hb_value');
		        if (empty($hb_value)) {
		        	$hb_value = 0;
		        }
                $hb_id            = $payment_hb;
		        $newdata  = array();
				$newdata['order_sumPrice'] = $item_order['order_sumPrice']-$hb_value;
				$userId                    = $userinfo['id'];
				$status   = $ordermodel->where($paycondition)->save($newdata);
				$nowtime  = time();
				if($status){
					//红包置为消费
					$hbmodel->where(array('id'=>$payment_hb,'userid'=>$userinfo['id']))->save(array('status'=>2,'create_time'=>$nowtime,'use_desc'=>'消费订单:'.$dingdanhao));
					$this->pay_log('ag_wxpay_hb',$userinfo['id'],$item_order['order_sumPrice'],'红包减免：ID【'.$payment_hb.'】额度【'.$hb_value.'】');
				} else {
					$this->error('使用红包减免失败,重新支付试试吧!');
				}
			}

			//支付类型 wxpay hdfk
			if(in_array($payment_id, $wxpay_config['Allow_type'])){
				//微信支付
				if($payment_id == 'wxpay'){
                    $this->error('Sorry!微信支付失败：');
					// $data['supportmetho'] = 1;
					// $userId=$userinfo['id'];
					// if($ordermodel->where("userId='$userId' and orderId='$dingdanhao'")->data($data)->save()){
					// 	$alipay = M('alipay')->find();
					//     echo "<script>location.href='wapapli/alipayapi.php?WIDseller_email=".$alipay['alipayname']."&WIDout_trade_no=".$dingdanhao."&WIDsubject=".$dingdanhao."&WIDtotal_fee=".$item_order['order_sumPrice']."'</script>";
				}else{
					$data['status']       = 2;
					$data['supportmetho'] = 2;
					$data['support_time'] = time();
                    //红包抵用记录
					$data['hb_id']        = $hb_id;
					$data['hb_value']     = $hb_value;
					if($ordermodel->where($paycondition)->save($data)){
				    	$this->pay_log('ag_wxpay_hdfk',$userinfo['id'],$item_order['order_sumPrice'],'红包减免：ID【'.$payment_hb.'】额度【'.$hb_value.'】');
					    $this->success('货到付款,支付成功!',U('User/bookprofile'));
					}else {
						$this->error('货到付款,支付失败!');
			        }
				}
			} else {
				        $this->error('暂不支持该支付方式!');
			}
		}
    }
    public function orderpay(){
    	$this->is_login();
    	$userinfo = session('P');
		if(IS_POST){
			$payment_hb = $_POST['payment_hb'];
			$payment_id = $_POST['payment_id'];
			$orderid    = $_POST['orderid'];
			$dingdanhao = $_POST['dingdanhao'];
			$userId     = $userinfo['id'];
			$ordermodel = M('shop_orders');
			$item_order = $ordermodel->where("userId='$userId' and orderId='$dingdanhao'")->find();
		
			if(empty($item_order)){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请选择正确的订单号'));
			}
            $paycondition = array('userId'=>$userId,'orderId'=>$dingdanhao);
            //读取微信红包领取配置
            $wxpay_config = $this->ag_hongbao_config();
    		$hb_id    = 0;
			$hb_value = 0;
			//启用红包减免费用
			if(!empty($payment_hb)&&!empty($wxpay_config['hb_dh_diyong'])){
				$hbmodel          = M('wxpay_userhongbao');
				//获取红包减免信息
		        $oldmap['userid'] = $userinfo['id'];
		        $oldmap['id']     = $payment_hb;
		        $oldmap['status'] = 1;
		        $hb_value         = $hbmodel->where($oldmap)->getField('hb_value');
		        if (empty($hb_value)) {
		        	$hb_value = 0;
		        }
                $hb_id            = $payment_hb;
		        $newdata  = array();
				$newdata['order_sumPrice'] = $item_order['order_sumPrice']-$hb_value;
				$userId                    = $userinfo['id'];
				$status   = $ordermodel->where($paycondition)->save($newdata);
				$nowtime  = time();
				if($status){
					//红包置为消费
					$hbmodel->where(array('id'=>$payment_hb,'userid'=>$userinfo['id']))->save(array('status'=>2,'create_time'=>$nowtime,'use_desc'=>'消费订单:'.$dingdanhao));
					$this->pay_log('ag_wxpay_hb',$userinfo['id'],$item_order['order_sumPrice'],'红包减免：ID【'.$payment_hb.'】额度【'.$hb_value.'】');
				} else {
                    $this->ajaxReturn(array('status'=>0,'msg'=>'使用红包减免失败,重新支付试试吧!'));
				}
			}

			//支付类型 wxpay hdfk
			if(in_array($payment_id, $wxpay_config['Allow_type'])){
				$payname = array('wxpay'=>'微信支付','hdfk'=>'货到付款');
				$paytype = array('wxpay'=>1,'hdfk'=>2);
				$data['status']       = 2;
				$data['supportmetho'] = $paytype[$payment_id];
				$data['support_time'] = time();
                //红包抵用记录
				$data['hb_id']        = $hb_id;
				$data['hb_value']     = $hb_value;
				if($ordermodel->where($paycondition)->save($data)){
			    	$this->pay_log('ag_wxpay_'.$payment_id,$userinfo['id'],$item_order['order_sumPrice'],'红包减免：ID【'.$payment_hb.'】额度【'.$hb_value.'】');
			    	$this->ajaxReturn(array('status'=>1,'msg'=>$payname[$payment_id].'成功!'));
				}else {
					$this->ajaxReturn(array('status'=>0,'msg'=>$payname[$payment_id].'失败!'));
		        }
			} else {
				$this->ajaxReturn(array('status'=>0,'msg'=>'暂不支持该支付方式!'));
			}
		}
    }
}

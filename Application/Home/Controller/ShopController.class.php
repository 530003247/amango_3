<?php
namespace Home\Controller;
/**
 * 商城模型控制器
 * 商城模型列表和详情
 */
class ShopController extends HomeController {
	public $logicmodel = 'shop';
    public function add_cart(){
    	//导入购物车
    	import('@.ORG.Cart');
    	$cart=new \Cart();
    	$goodId   = I('post.goodId',  '','intval');
    	$quantity = I('post.quantity','','intval');
    	$item     = api('Document/get_detail',array('id'=>$goodId));
    	if(empty($item)){
    		$data=array('status'=>0,'msg'=>'不存在该商品','count'=>$cart->getCnt(),'sumPrice'=>$cart->getPrice());
    	}elseif($item['shop_goods_stock']<$quantity){
    		$data=array('status'=>0,'msg'=>'没有足够的库存','count'=>$cart->getCnt(),'sumPrice'=>$cart->getPrice());
    	}else {
    		$result= $cart->addItem($item['id'],$item['title'],$item['shop_good_price'],$quantity,$item['cover_id']);
    		if($result==1){//购物车存在该商品
    			$data=array('result'=>$result,'status'=>1,'count'=>$cart->getCnt(),'sumPrice'=>$cart->getPrice(),'msg'=>'该商品已经存在购物车');
    		}else{
    		$data=array('result'=>$result,'status'=>1,'count'=>$cart->getCnt(),'sumPrice'=>$cart->getPrice(),'msg'=>'商品已成功添加到购物车');
    		}
    	}
    	    echo json_encode($data);
    }
    public function change_quantity(){
    	import('@.ORG.Cart');
    	$cart=new \Cart();
    	$goodId   = I('post.itemId',  '','intval');
    	$quantity = I('post.quantity','','intval');
    	$item     = api('Document/get_detail',array('id'=>$goodId));
    	if($item['shop_goods_stock']<$quantity){
    	    $data=array('status'=>0,'msg'=>'该商品的库存不足');
    	}else {
    	    $cart->modNum($goodId,$quantity);
    	    $data=array('status'=>1,'item'=>$cart->getItem($goodId),'sumPrice'=>$cart->getPrice());
    	}
    	    echo json_encode($data);
    }
    private function book_menu($userinfo){
    	$shopid   = M('Model')->where(array('name'=>$this->logicmodel))->getField('id');
    	$shoplist = M('Category')->where(array('model'=>$shopid,'pid'=>0,'status'=>1))->select();
        $this->assign('shoplist',$shoplist);
    	$this->assign('status',$status);
    	$this->assign('info',$userinfo);
    }
    public function is_login(){
		if ( !is_login() ) {
			$this->error( '您还没有登陆',U('User/login') );
		}
    }
    public function jiesuan(){
    	$this->is_login();
    	$userinfo = session('P');
        $this->book_menu($userinfo);
		if(count($_SESSION['cart'])>0){
			$user_address_mod = M('shop_useraddress');
			$address_list     = $user_address_mod->where(array('uid'=>$userinfo['id']))->select();
			$this->assign('address_list', $address_list);

			 import('@.ORG.Cart');
	    	 $cart     = new \Cart();
	    	 $sumPrice = $cart->getPrice();
		    	// var_dump($freearr);
		    	$this->assign('sumPrice',$sumPrice);
			    //$this->assign('pingyou',$pingyou);
				//$this->assign('kuaidi',$kuaidi);
			    //$this->assign('ems',$ems);
				$this->display();
		}else {
			$this->redirect('User/bookprofile');
		}
    }
    //TODO  红包接口
    public function ag_hongbao_config(){
        $list = M('wxpay_config')->select();
        $info = array();
        foreach ($list as $key => $value) {
        	$info[$value['key']] = $value['value'];
        }
        $info['hb_leiji']   = parse_config($info['hb_leiji']);
        return $info;
    }
	public function pay(){
    	$this->is_login();
    	$userinfo     = session('P');
    	$wxpay_config = $this->ag_hongbao_config();
        if(empty($wxpay_config['Allow_type'])){
            $this->error('暂不支持任何形式付款');
        }
		if(IS_POST&&count($_SESSION['cart'])>0){
			import('@.ORG.Cart');
            $cart = new \Cart();
            $user_address = M('shop_useraddress');
			$item_order   = M('shop_orders');
			$order_detail = M('order_detail');
		    //生成订单号
		    $dingdanhao  = date("Y-m-dH-i-s");
		    $dingdanhao  = str_replace("-","",$dingdanhao);
		    $dingdanhao .= rand(1000,9999);
		   
		    $time = time();                                          //订单添加时间
			$address_options = I('post.address_options','','intval');//地址  0：刚填的地址 大于0历史的地址
			$shipping_id     = I('post.shipping_id','','intval');    //配送方式
			$postscript      = I('post.postscript','','trim');       //卖家留言
		   
			if(!empty($postscript)){//卖家留言
				$data['note'] = $postscript;
			}
			//仅支持卖家包邮
		    //if(empty($shipping_id)){//卖家包邮
		    	$data['freetype']       = 0;
		    	$data['order_sumPrice'] = $cart->getPrice();
		    //} else{
				// $data['freetype']       = $shipping_id;
				// $data['freeprice']      = $this->getFree($shipping_id);//取到运费
				// $data['order_sumPrice'] = $cart->getPrice()+$this->getFree($shipping_id);
		    //}
		 		$data['orderId']        = $dingdanhao;//订单号
				$data['add_time']       = $time;//添加时间
				$data['goods_sumPrice'] = $cart->getPrice();//商品总额

				$data['userId']         = $userinfo['id'];//用户ID
			if($address_options==0){
				$consignee              = I('post.consignee','','trim');//真实姓名
				$sheng                  = I('post.sheng','','trim');//省
				$shi                    = I('post.shi','','trim');//市
				$qu                     = I('post.qu','','trim');//区
				$address                = I('post.address','','trim');//详细地址
				$phone_mob              = I('post.phone_mob','','trim');//电话号码
				$save_address           = I('post.save_address','','trim');//是否保存地址
				
				$data['address_name']   = $consignee;//收货人姓名
				$data['mobile']         = $phone_mob;//电话号码
				$data['address']        = $sheng.$shi.$qu.$address;//地址

			    //自动保存地址进数据库
			    if($save_address){
					$add_address['consignee'] = $consignee;
					$add_address['address']   = $address;
					$add_address['mobile']    = $phone_mob;
					$add_address['sheng']     = $sheng;
					$add_address['shi']       = $shi;
					$add_address['qu']        = $qu;
                    $user_address->add($add_address);
		        }
			}else{
				$userId               = $userinfo['id'];
				//提取用户地址
				$address              = $user_address->where("uid='$userId'")->find($address_options);//取到地址
				$data['address_name'] = $address['consignee'];//收货人姓名
				$data['mobile']       = $address['mobile'];//电话号码
				$data['address']      = $address['sheng'].$address['shi'].$address['qu'].$address['address'];//地址
			}
			$orderid  =  $item_order->add($data);
			//添加订单
			if($orderid){
				$orders['orderId'] = $dingdanhao;
				$item_goods        = M('docunment_shop');
				foreach ($_SESSION['cart'] as $item ){
					//减少库存
                    $item_goods->where(array('id'=>$item['id']))->setDec('shop_goods_stock',$item['num']);
					//$item_goods->where('id ='.$item['id'])->setDec('goods_stock',$item['num']);
					$orders['itemId']      = $item['id'];//商品ID
					$orders['title']       = $item['name'];//商品名称
					$orders['img']         = $item['img'];//商品图片
					$orders['price']       = $item['price'];//商品价格 
					$orders['quantity']    = $item['num'];//购买数量
					$order_detail->add($orders);
				}
				$cart->clear();//清空购物车
				$this->assign('orderid',$orderid);//订单ID
				$this->assign('dingdanhao',$dingdanhao);//订单号
				$this->assign('order_sumPrice',$data['order_sumPrice']);
			}else {
				$this->error('生成订单失败!');
			}
		}else if(isset($_GET['orderId'])){
			$item_order = M('shop_orders');
			//订单号
			$userId     = $userinfo['id'];
            //去除单号  安全后缀  空格
            $orderId    =  str_replace(' ', '', str_replace('.html?showwxpaytitle=1', '', $_GET['orderId']));
			$orders     = $item_order->where("userId='$userId' and orderId='$orderId'")->find();
			if(!is_array($orders)){
			    $this->error('该订单不存在');
			}
			Wxpay::get_prepay_id();
			if(empty($orders['supportmetho'])){  
			    //是否已有支付方式
				$this->assign('orderid',$orders['id']);        //订单ID
				$this->assign('dingdanhao',$orders['orderId']);//订单号
				$this->assign('order_sumPrice',$orders['order_sumPrice']);
			}else {
				//微信支付
			    $alipay=M('alipay')->find();
		        echo "<script>location.href='wapapli/alipayapi.php?WIDseller_email=".$alipay['alipayname']."&WIDout_trade_no=".$orderId."&WIDsubject=".$orderId."&WIDtotal_fee=".$orders['order_sumPrice']."'</script>";die;
			}
		}
		        $this->book_menu($userinfo);
		        //选择支付方式  货到付款  微信支付
		        if(!empty($wxpay_config['hb_dh_diyong'])){
			        $time = time();
			        $map['hb_star']  = array('lt',$time);
			        $map['hb_end']   = array('gt',$time);
			        $map['status']   = 1;
			        $map['userid']   = $userinfo['id'];
			        $hblist = M('wxpay_userhongbao')->where($map)->select();
			        $this->assign('hblist',$hblist);
		        }
		            $this->assign('paylist',$wxpay_config['Allow_type']);
		            $this->display();
	}
	private function wxpay_params(){
		//用户资料
		dump(session('P'));
		//商户资料
		$configlist  = M('wxpay_config')->select();
		$wxpayconfig = array();
		foreach ($configlist as $key => $value) {
			$wxpayconfig[$value['key']] = $value['value'];
		}
		dump($wxpayconfig);die;
	}
}

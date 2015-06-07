<?php
namespace Home\Logic;

class ShopLogic extends BaseLogic{
	/* 后台 自动验证规则 */
	protected $_validate = array(
		array('shop_good_hdfk','require','货到付款方式必须填写'),
		array('shop_free','require','运费承担方式必须填写'),
		array('shop_good_price','require','商品价格必须填写'),
		array('shop_goods_stock', 'number', '库存必须为数字', self::MUST_VALIDATE , 'regex', self::MODEL_BOTH),
	);

	/* 自动完成规则 */
	protected $_auto = array(
        array('shop_good_express', 'getExpress', self::MODEL_BOTH, 'callback'),
	);
	
    public function getExpress(){
        $denyuser = I('post.shop_good_express');
        return (empty($denyuser)||$denyuser==',')? '':implode(',', $denyuser);
    }

	/**
	 * 新增或添加一条文章详情
	 * @param  number $id 文章ID
	 * @return boolean    true-操作成功，false-操作失败
	 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
	 */
	public function update($id = 0){
		/* 获取文章数据 */
		$data = $this->create();
		if($data === false){
			return false;
		}

		/* 添加或更新数据 */
		if(empty($data['id'])){//新增数据
			$data['id'] = $id;
			$id = $this->add($data);
			if(!$id){
				$this->error = '新增详细内容失败！';
				return false;
			}
		} else { //更新数据
			$status = $this->save($data);
			if(false === $status){
				$this->error = '更新详细内容失败！';
				return false;
			}
		}

		return true;
	}

	/**
	 * 获取文章的详细内容
	 * @return boolean
	 * @author huajie <banhuajie@163.com>
	 */
	protected function getContent(){
		$type = I('post.type');
		$content = I('post.content');
		if($type > 1){	//主题和段落必须有内容
			if(empty($content)){
				return false;
			}
		}else{			//目录没内容则生成空字符串
			if(empty($content)){
				$_POST['content'] = ' ';
			}
		}
		return true;
	}

	/**
	 * 保存为草稿
	 * @return true 成功， false 保存出错
	 * @author huajie <banhuajie@163.com>
	 */
	public function autoSave($id = 0){
		$this->_validate = array();

		/* 获取文章数据 */
		$data = $this->create();
		if(!$data){
			return false;
		}

		/* 添加或更新数据 */
		if(empty($data['id'])){//新增数据
			$data['id'] = $id;
			$id = $this->add($data);
			if(!$id){
				$this->error = '新增详细内容失败！';
				return false;
			}
		} else { //更新数据
			$status = $this->save($data);
			if(false === $status){
				$this->error = '更新详细内容失败！';
				return false;
			}
		}

		return true;
	}

}

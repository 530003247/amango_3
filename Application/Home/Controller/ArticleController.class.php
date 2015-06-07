<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
namespace Home\Controller;
/**
 * 文档模型控制器
 * 文档模型列表和详情
 */
class ArticleController extends HomeController {

    /* 文档模型频道页 */
	public function index(){
		/* 分类信息 */
		$category = $this->category();
		//只显示顶级模板，非顶级自动跳转到对应列表页面
        if($category['pid']!=0&&empty($category['template_index'])){
        	$listsurl = U('Article/lists',array('category'=>$category['name']));
            redirect($listsurl);
        }
		$this->setShare($category['icon'],Amango_U('Article/index',array('category'=>$category['id'])),$category['title'],$category['description']);
		/* 模板赋值并渲染模板 */
		$this->assign('Title',$category['title']);
		$this->assign('category', $category);
		$this->assign('category_id', $category['id']);
		$this->assign('HideFastmenu','1');
		$this->display($category['template_index']);
	}
	public function ajax_del(){
		//读取用户权限
		if(is_login()){
			$userinfo = session('user_auth');
            $id = I('post.id');
            //判断是否为用户权限 user
            $model = D('Document');
            //检查该用户操作权限
            $userauth = $model->checkDocunmentAuth($userinfo['uid'],$userinfo['uidtype'],$id);
            if($userauth==true){
            	//管理员操作
                $status = $model->setStatus($id);
	            if($status!=false){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'您的帖子放入回收站成功'));
	            } else {
	            	$this->ajaxReturn(array('status'=>0,'msg'=>'您的帖子放入回收站失败'));
	            }
            } else {
            	$this->ajaxReturn(array('status'=>3,'msg'=>'只能删除自己的帖子哦~'));
            }
		} else {
			$this->ajaxReturn(array('status'=>3,'msg'=>'登陆后才能删除哦~'));
		}
	}
    public function ajax_lists(){
    	$latsp = I('post.page');
    	$categoryid = I('post.category');
    	if(empty($latsp)||empty($categoryid)){
             $this->ajaxReturn(array('status'=>3,'p'=>$latsp,'msg'=>'请选择正确的分类'));
    	}
    	$p = $latsp+1;
		/* 获取分类信息 */
		$category = D('Category')->info($categoryid);
		if($category && 1 == $category['status']){
			switch ($category['display']) {
				case 0:
				    $this->ajaxReturn(array('status'=>3,'p'=>$latsp,'msg'=>'该分类禁止显示！'));
					break;
			}
		} else {
			$this->ajaxReturn(array('status'=>3,'p'=>$latsp,'msg'=>'分类不存在或被禁用！'));
		}
        
        $param = array(
			'p'           =>$p,
			'categoryid'  =>$category,
			'list_row'    =>'',
			'extend'      =>true
        );
        
		$newlist  = api('Document/get_list',$param);
		//判断分类是否开启 评论 reply  reply_show
		if(!empty($category['reply'])&&!empty($category['reply_show'])){
			$replymodel = D('Replydocument');
			$tlocation  = '岘港假期';
            foreach ($newlist as $key => $value) {
            	$tongji   .= $value['id'].'|';
            	$newrlist = array();
             	$newrlist = $replymodel->lists_has_comments($value['id']);
             	$newlist[$key]['replylist']    = $newrlist;
             	$newlist[$key]['usernickname'] = get_cms_username($value['uid'],$value['usertype']);
             	$newlist[$key]['create_time']  = date('Y-m-d',$value['create_time']);
             	$newlist[$key]['detailurl']    = U('Article/detail?id='.$value['id']);
             	$newlist[$key]['userheadimg']  = get_cms_userpic($value['uid'],$value['usertype']);
             	$newlist[$key]['tlocation']    = $tlocation;
            }
		}
      	if(empty($newlist)){
            $this->ajaxReturn(array('status'=>2,'p'=>$latsp,'msg'=>'到底咯~'));
      	} else {
      		$this->ajaxReturn(array('status'=>1,'p'=>$p,'msg'=>$newlist));
      	}
    }
	/* 文档模型列表页 */
	public function lists($p = 1){
		/* 分类信息 */
        $category = $this->category();
        $pid      =   I('pid',0);
		/* 获取当前分类列表 */
        $param = array(
			'p'           =>$p,
			'categoryid'  =>$category,
			'list_row'    =>'',
			'extend'      =>true
        );
		$newlist  = api('Document/get_list',$param);
        /* 芒果微信自动生成发表菜单 description icon*/ 
        //自动生成发表字段
        $this->assign('fields', self::getHomefields($newlist[0]['model_id']));
        $this->assign('Share',$Shareinfo);
        $this->setShare($category['icon'],U('Article/lists',array('category'=>$category['name']),'',TRUE),$category['title'],$category['description']);
		/* 模板赋值并渲染模板 */
		$this->assign('category', $category);
		$this->assign('list', $newlist);
        //pid     
        $this->assign('pid', $pid);
        //model_id 
        $this->assign('model_id', $category['model'][0]);
        //cate_id 
        $this->assign('category_id', $category['id']);
        //是否允许发表新
        $this->assign('allow_publish', $category['allow_publish']);
        //公共title 
        $this->assign('Title',$category['title']);
        $this->assign('HideFastmenu','1');
        //完善模板继承
        $template_lists = empty($category['template_lists']) ? api('Category/get_category_template',array('id'=>$category['pid'])) : $category['template_lists'];

		$this->display($template_lists);
	}
	/* 获取前台发表字段 */
	protected function getHomefields($model_id){
		$model  = get_document_model($model_id);
        $fields = get_model_attribute($model['id']);

		$newfields = array();
		foreach ($fields as $key => $value) {
			foreach ($value as $k => $v) {
				if($v['home_show']==1){
				    $newfields[] = $v;
				}
			}
		}
         return $newfields;
    }
	/* 文档模型详情页 */
	public function detail($id = 0, $p = 1){
		global $_K;
		/* 标识正确性检测 */
		if(!($id && is_numeric($id))){
			$this->error('文档ID错误！');
		}

		/* 页码检测 */
		$p = intval($p);
		$p = empty($p) ? 1 : $p;

		/* 获取详细信息 */
		$info  = api('Document/get_detail',array('id'=>$id));
		if(false===$info['status']){
			$this->error($info['info']);
		}
        //芒果用户回复 
        $this->assign('model_id', $info['model_id']);
        $model  = get_document_model($info['model_id']);
        $fields = get_model_attribute($model['id']);
		$newfields = array();
			foreach ($fields[1] as $k => $v) {
				if($v['reply_show']==1){
				    $newfields[] = $v;
				}
			}
		$logicname = get_document_model($info['model_id'],'name');
		/* 分类信息 */
		$category  = $this->category($info['category_id']);
		$tmpl      = $info['template'];
        if(empty($tmpl)){
            $tmpl  = empty($category['template_detail']) ? get_category($category['pid'], 'template_detail') : $category['template_detail'];
	        if(empty($tmpl)){
                $tmpl = 'Article/'. $logicname .'/detail';
	        }
        }
		/* 更新浏览数 */
		$map = array('id' => $id);
		D('Document')->where($map)->setInc('view');
        /* 芒果微信分享信息   */
        $this->setShare($info['cover_id'],U('Article/detail',array('id'=>$id),'',TRUE),$info['title'],$info['description']);
        //是否允许发表新
        $this->assign('reply', $category['reply']);
        $this->assign('reply_show', $category['reply_show']);
        $this->assign('fields', $newfields);
        //一键关注链接
        $this->assign('accountsub', $_K['DEFAULT']['account_sub']);
		/* 模板赋值并渲染模板 */
		$this->assign('category', $category);
		$this->assign('category_id', $category['id']);
		$this->assign('info', $info);
		$this->assign('page', $p); //页码
        //公共title 
        $this->assign('Title',$info['title']);
        $this->assign('HideFastmenu','1');
		$this->display($tmpl);
	}

	/* 获取前台回复字段 */
	protected function getReplyfields($fields){
		$newfields = array();
		foreach ($fields as $key => $value) {
			foreach ($value as $k => $v) {
				if($v['reply']==1){
				    $newfields[] = $v;
				}
			}
		}
         return $newfields;
    }
	/* 文档分类检测 */
	private function category($id = 0){
		/* 标识正确性检测 */
		$id = $id ? $id : I('get.category', 0);
		if(empty($id)){
			$this->error('没有指定文档分类！');
		}

		/* 获取分类信息 */
		$category = D('Category')->info($id);
		if($category && 1 == $category['status']){
			switch ($category['display']) {
				case 0:
					$this->error('该分类禁止显示！');
					break;
				//TODO: 更多分类显示状态判断
				default:
					return $category;
			}
		} else {
			$this->error('分类不存在或被禁用！');
		}
	}
    /**
     * 文档  公共添加
     * @author ChenDenlu <530003247@vip.qq.com>
     */
    private function user_addauth(){
    	$userinfo     = session('user_auth');
    	//初始化操作
    	$_POST['uid'] = $userinfo['uid'];
    	if($userinfo['uidtype']=='admin'){
            $_POST['fromusertype'] = 'admin';
    	} else {
    		$_POST['fromusertype'] = 'user';
    	}
    	    $_POST['type'] = 3;
    	    $_POST['pid']  = 0;
    }
    public function base64pic($base64_image_content,$catepath){
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
		    $type = $result[2];
		}
    	$pictimes = time();
    	if(empty($catepath)){
            $headUpload  = __ROOT__.'./Uploads/Document/'.$pictimes.'.'.$type;
    	} else {
    		$headUpload  = __ROOT__.'./Uploads/Document/'.$pictimes.'.'.$type;
    	}
	    if (file_put_contents($headUpload, base64_decode(str_replace($result[1], '', $base64_image_content)))){
	        return $headUpload;
	    }
    }
    public function add(){
    	if(is_login()){
	        $this->user_addauth();
	        $category_id  = I('post.category_id');
	        $model_id     = I('post.model_id');
	        empty($category_id) && $this->error('请选择要发布的分类不能为空！');
	        empty($model_id) && $this->error('该分类木有绑定模型！');
	        //获取参数
	        $category = D('Category')->info($category_id);
	        //检查该分类是否允许发布
	        //$allow_publish = D('Document')->checkCategory($cate_id);
	        ($category['allow_publish']!=2) && $this->error('该分类不允许发布内容！');
			/* 保存文档内容 */
			if(!empty($_POST['pics'])){
				$imglist = '';
				foreach ($_POST['pics'] as $key => $value) {
					$imglist .= '<img src="'.$this->base64pic($value,$category['name']).'">';
				}
				$_POST['content'] = $_POST['content'].$imglist;
				unset($_POST['pics']);
			}
			$Document = D('Document');
			$status = $Document->update();

			if($status){
				$uid = session('user_auth.uid');
				Credits::ag_cms_topics_add($uid,$category['id'],'发布了一条内容,ID:'.$status['id']);
				$this->success('发布成功！', U('Article/lists?category='.$category['name']));
			} else {
				$this->error($Document->getError());
			}
		} else {
			$this->error('登陆后才能发表哦~');
		}	
    }

    /**
     * 文档  公共回复  
     * @author ChenDenlu <530003247@vip.qq.com>
     */
    public function reply(){
        if(is_login()){
	    	$_POST['documentid'] || $this->error('请选择要回复的资讯');
	    	$this->user_replyauth();
			$Document = D('Replydocument');
			$status = $Document->update($_POST['documentid']);
			if(true!==$status){
				$this->error($status);
			} else {
				$uid = session('user_auth.uid');
				$Documentuid = M('Document')->where(array('id'=>$_POST['documentid']))->getField('uid');
				if($Documentuid!=$uid){
				    Credits::ag_cms_topics_comment($uid,$_POST['documentid'],'评论了一篇内容,ID:'.$_POST['documentid']);
				}
				    $this->success('回复成功！');
			}
		} else {
			$this->error('登陆后才能回复哦~');
		}
    }

    private function user_replyauth(){
    	$userinfo = session('user_auth');
    	$_POST['fromuserid'] = $userinfo['uid'];
    	if($userinfo['uidtype']=='admin'){
            $_POST['fromusertype'] = 'admin';
    	} else {
    		$_POST['fromusertype'] = 'user';
    	}
    }
}

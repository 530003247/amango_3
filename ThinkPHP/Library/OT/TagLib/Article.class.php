<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace OT\TagLib;
use Think\Template\TagLib;
/**
 * 文档模型标签库
 */
class Article extends TagLib{
	/**
	 * 定义标签列表
	 * @var array
	 */
	protected $tags   =  array(
		'partlist'     => array('attr' => 'id,field,page,name', 'close' => 1), //段落列表
		'partpage'     => array('attr' => 'id,listrow', 'close' => 0), //段落分页
		'prev'         => array('attr' => 'name,info', 'close' => 1), //获取上一篇文章信息
		'next'         => array('attr' => 'name,info', 'close' => 1), //获取下一篇文章信息
		'page'         => array('attr' => 'cate,listrow', 'close' => 0), //列表分页
		'position'     => array('attr' => 'pos,cate,limit,filed,name', 'close' => 1), //获取推荐位列表
		'list'         => array('attr' => 'name,category,child,page,row,field', 'close' => 1), //获取指定【分类文章列表】
		'categorylist' => array('attr' => 'name,categoryid,row,order,limit', 'close' => 1), //获取指定【子分类列表】
		'categoryinfo' => array('attr' => 'name,categoryid,field', 'close' => 1),     //获取指定【分类信息】
		'subcategorylist' => array('attr' => 'name,categoryid', 'close' => 1), //获取所有同级分类
		'replylist'    => array('attr' => 'name,documentid,row', 'close' => 1), //获取指定【回复列表】
		'categorygroup'=> array('attr' => 'name,id', 'close' => 1), //获取指定【回复列表】
	);
	/* 获取同类下的分类信息 */
    public function _subcategorylist($tag, $content){
		$name   = $tag['name'];
		$parse  = '<?php ';
		$parse .= '$__ALLCATEGORYL__ = api(\'Category/get_all_category\',array(\'id\'=>'.$tag['categoryid'].'));';
		$parse .= '$row = count($__ALLCATEGORYL__);';
		$parse .= ' ?>';
		$parse .= '<volist name="__ALLCATEGORYL__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}
	/* 获取指定顶类下的分类信息 */
    public function _categorygroup($tag, $content){
		$name   = $tag['name'];
		$parse  = '<?php ';
		$parse .= '$__CATEGORYLGROUP__ = api(\'Category/get_category_group\',array(\'id\'=>'.$tag['id'].'));';
		$parse .= '$row = count($__CATEGORYLGROUP__);';
		$parse .= ' ?>';
		$parse .= '<volist name="__CATEGORYLGROUP__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}
	/* 子分类信息 浏览量 访问人数 */
	public function _categoryinfo($tag, $content){
		$name   = $tag['name'];
		//判断该分类下所有帖子的数目和访问量
		//$info   = api('Category/get_category_condition',array('id'=>$tag['categoryid']));
		$parse  = '<?php ';
		$parse .= '$categoryinfo = api(\'Category/get_category_condition\',array(\'id\'=>'.$tag['categoryid'].'));';
		$parse .= '$__CATEGORYLINFO__ = array(api(\'Category/get_category\',array(\'id\'=>'.$tag['categoryid'].')));';
		$parse .= '$__CATEGORYLINFO__[0][\'views\']     = $categoryinfo[1];';
		$parse .= '$__CATEGORYLINFO__[0][\'tiezinums\'] = $categoryinfo[0];';
		$parse .= ' ?>';
		$parse .= '<volist name="__CATEGORYLINFO__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}
	/* 子分类列表 */
	public function _categorylist($tag, $content){
		$name   = $tag['name'];
		$cate   = $tag['categoryid'];
		$row    = empty($tag['row'])   ? '10' : $tag['row'];
		$order  = empty($tag['order'])   ? '`level` DESC,`digest` DESC,`id` DESC' : $tag['order'];
		$parse  = '<?php ';
		$parse .= '$__CATEGORYLIST__ = D(\'Document\')->page(!empty($_GET["p"])?$_GET["p"]:1,'.$row.')->lists(';
		$parse .= $cate . ',\''.$order.'\');';
		$parse .= '$row = count($__CATEGORYLIST__);';
		$parse .= '$row = ($row>'.$row.') ? '.$row.' : $row;';
		$parse .= ' ?>';
		$parse .= '<volist name="__CATEGORYLIST__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}
	public function _list($tag, $content){
		$name   = $tag['name'];
		$cate   = $tag['category'];
		$child  = empty($tag['child']) ? 'false' : $tag['child'];
		$row    = empty($tag['row'])   ? '10' : $tag['row'];
		$field  = empty($tag['field']) ? 'true' : $tag['field'];

		$parse  = '<?php ';
		$parse .= '$__CATE__ = D(\'Category\')->getChildrenId('.$cate.');';
		$parse .= '$__LIST__ = D(\'Document\')->page(!empty($_GET["p"])?$_GET["p"]:1,'.$row.')->lists(';
		$parse .= '$__CATE__, \'`level` DESC,`id` DESC\', 1,';
		$parse .= $field . ');';
		$parse .= ' ?>';
		$parse .= '<volist name="__LIST__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}
	/* 获取单内容下的 */
	public function _replylist($tag, $content){
		$name         = $tag['name'];
		$documentid   = $tag['documentid'];
		$row          = empty($tag['row'])   ? '10' : $tag['row'];
		$parse  = '<?php ';
		$parse .= '$__REPLYLIST__ = D(\'Replydocument\')->lists('.$documentid.','.$row.');';
		$parse .= ' ?>';
		$parse .= '<volist name="__REPLYLIST__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}

	/* 推荐位列表 */
	public function _position($tag, $content){
		$pos    = $tag['pos'];
		$cate   = $tag['cate'];
		$field  = empty($tag['field']) ? 'true' : $tag['field'];
		$name   = $tag['name'];
		$parse  = '<?php ';
		$parse .= '$__POSLIST__ = api(\'Document/get_position\',array(\'cate\'=>'.$cate.',\'pos\'=>\''.$pos.'\',\'limit\'=>'.$tag['limit'].'));';
		// $parse .= '$__POSLIST__ = D(\'Document\')->position(';
		// $parse .= $pos . ',';
		// $parse .= $cate . ',';
		// $parse .= $limit . ',';
		// $parse .= $field . ');';
		$parse .= ' ?>';
		$parse .= '<volist name="__POSLIST__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}

	/* 列表数据分页 */
	public function _page($tag){
		$cate    = $tag['cate'];
		$listrow = $tag['listrow'];
		$parse   = '<?php ';
		$parse  .= '$__PAGE__ = new \Think\Page(get_list_count(' . $cate . '), ' . $listrow . ');';
		$parse  .= 'echo $__PAGE__->show();';
		$parse  .= ' ?>';
		return $parse;
	}

	/* 获取下一篇文章信息 */
	public function _next($tag, $content){
		$name   = $tag['name'];
		$info   = $tag['info'];
		$parse  = '<?php ';
		$parse .= '$' . $name . ' = D(\'Document\')->next($' . $info . ');';
		$parse .= ' ?>';
		$parse .= '<notempty name="' . $name . '">';
		$parse .= $content;
		$parse .= '</notempty>';
		return $parse;
	}

	/* 获取上一篇文章信息 */
	public function _prev($tag, $content){
		$name   = $tag['name'];
		$info   = $tag['info'];
		$parse  = '<?php ';
		$parse .= '$' . $name . ' = D(\'Document\')->prev($' . $info . ');';
		$parse .= ' ?>';
		$parse .= '<notempty name="' . $name . '">';
		$parse .= $content;
		$parse .= '</notempty>';
		return $parse;
	}

	/* 段落数据分页 */
	public function _partpage($tag){
		$id      = $tag['id'];
		if ( isset($tag['listrow']) ) {
			$listrow = $tag['listrow'];
		}else{
			$listrow = 10;
		}
		$parse   = '<?php ';
		$parse  .= '$__PAGE__ = new \Think\Page(get_part_count(' . $id . '), ' . $listrow . ');';
		$parse  .= 'echo $__PAGE__->show();';
		$parse  .= ' ?>';
		return $parse;
	}

	/* 段落列表 */
	public function _partlist($tag, $content){
		$id     = $tag['id'];
		$field  = $tag['field'];
		$name   = $tag['name'];
		if ( isset($tag['listrow']) ) {
			$listrow = $tag['listrow'];
		}else{
			$listrow = 10;
		}
		$parse  = '<?php ';
		$parse .= '$__PARTLIST__ = D(\'Document\')->part(' . $id . ',  !empty($_GET["p"])?$_GET["p"]:1, \'' . $field . '\','. $listrow .');';
		$parse .= ' ?>';
		$parse .= '<?php $page=(!empty($_GET["p"])?$_GET["p"]:1)-1; ?>';
		$parse .= '<volist name="__PARTLIST__" id="'. $name .'">';
		$parse .= $content;
		$parse .= '</volist>';
		return $parse;
	}
}
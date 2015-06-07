<?php
// +----------------------------------------------------------------------
// | Amango [ 芒果一站式微信营销系统 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.Amango.net All rights reserved.
// +----------------------------------------------------------------------
// | Author: ChenDenlu <530003247@vip.qq.com>
// +----------------------------------------------------------------------
namespace Common\Api;
class DocumentApi {
    /**
     * 获取文档列表 Document
     * @param   num       $p          当前
     * @param   array|num $categoryid 所属文档
     * @param   num       $list_row   每页记录
     * @param   bool      $extend     是否读取子文档
     * @return  array  文档列表
     */
    public static function get_list($p = 1, $categoryid, $list_row, $extend=true){
    	if(empty($categoryid)){
            return '文档列表的分类属性必须为数字';
    	}
    	//获取分类属性
    	$category = is_array($categoryid) ? $categoryid : get_category($categoryid);
        //获取分类属性
    	$list_row = empty($list_row) ? $category['list_row'] : $list_row;
		/* 获取当前分类列表 */
		$Document = D('Document');
		$list = $Document->page($p, $list_row)->lists($category['id']);
		if(false === $list){
			return '获取文档列表数据失败';
		}
		//判断是否读取子文档
		if(true===$extend){
			$newlist   = array();
			$modellist = self::get_sublist($category['model']);
			foreach ($list as $key => $value) {
				$newlist[$key] = array_merge($value,$modellist[$value['id']]);
			}
		} else {
			    $newlist = $list;
		}
		return $newlist;
    }
    /**
     * 获取子文档列表 Document_
     * @param   array|num  $modelid  模型ID
     * @return  array                子模型列表
     */
    public static function get_sublist($modelid){
    	$model_id    = is_array($modelid) ? $modelid[0] : $modelid;
		$model_title = get_document_model($model_id);
		$sublist     = array();
		$newsublist  = array();
		$sublist     = M('Document'.ucfirst($model_title['name']))->select();
		//读取附属全部字段
		foreach ($sublist as $key => $value) {
			$newsublist[$value['id']] = $value;
			unset($newsublist[$value['id']]['id']);
		}
		return $newsublist;
    }
    /**
     * 获取子文档列表 Document_
     * @param   array|num  $modelid  模型ID
     * @return  array                子模型列表
     */
    public static function get_detail($id){
		$Document = D('Document');
		$info = D('Document')->detail($id);
		if(!$info){
			return array('status'=>false,'info'=>$Document->getError());
		}
		return $info;
    }
    private static function get_pid($cateid){
    	static $catepidlist;
        if(!isset($catepidlist[$cateid])){
        	$category = get_category($cateid);
            if($category['pid']==0){
                $catepidlist[$cateid] = $cateid;
            } else {
            	$catepidlist[$cateid] = M('Category')->where(array('id'=>$cateid))->getField('pid');
            }
        }
            return $catepidlist[$cateid];
    }
    private static function get_pids($cateid){
    	static $catepidlist;
        $category = get_category($cateid);
        if($category['pid']==0){
            if(!isset($catepidlist[$cateid])){
                $list = M('Category')->where(array('pid'=>$cateid,'status'=>1))->order('create_time desc')->field('id')->select();
                foreach ($list as $key => $value) {
                	$catepidlist[$cateid][] = $value['id'];
                }
            }
                return $catepidlist[$cateid];
        } else {
        	return $cateid;
        }
    }

    /**
     * 获取分类的推荐列表
     * @param  num $cateid   文档分类ID
     * @param  num $pos      推荐位置ID
     * @param  num $limit    限制条数
     * @param  sting $order  排序顺序
     * @return array         推荐列表文档
     */
    public static function get_position($cateid,$pos,$limit,$order){
    	if(empty($cateid)){
            return false;
    	} else {
    		$category    = get_category($cateid);
    		//判断该分类是否为顶级  是 读取顶级下所有的子分类id
    		if($category['pid']==0){
               $list = M('Category')->where(array('pid'=>$cateid,'status'=>1))->order('create_time desc')->field('id')->select();
                foreach ($list as $key => $value) {
                	$categoryid[] = $value['id'];
                }
                $top = $cateid;
    		} else {
                $categoryid = $cateid;
                $top = self::get_pid($cateid);
    		}
            //表数据模型
    		$model_info = get_document_model($category['model']);
    		$submodel   = M('Document'.ucfirst($model_info['name']));
    		$map['status'] = 1;
			if(is_numeric($categoryid)){
				$map['category_id'] = $categoryid;
			} else {
				$map['category_id'] = array('in', $categoryid);
			}
			$pos = empty($pos) ? 0 : $pos;
			if(strpos($pos, ',')!==false){
				$map['position'] = array('in', $pos);
			}
			$map['create_time'] = array('lt', NOW_TIME);
			$map['_string']     = 'deadline = 0 OR deadline > ' . NOW_TIME;
			$limit    = empty($limit) ? 6 : $limit;
			$order    = empty($order) ? 'level desc,create_time desc' : $order;
			//先读取基础数据
			    $newlist = array();
    		    $lists   = M('Document')->where($map)->order($order)->limit($limit)->select();
    		//拓展数据合并
    		    foreach ($lists as $key => $value) {
    		    	$subinfo = array();
    		    	$subinfo = $submodel->where(array('id'=>$value['id']))->find();
    		    	if(!empty($subinfo)){
                        $newlist[$key] = array_merge($value,$subinfo);
    		    	}
    		    }
    		        return $newlist;
    	}
    }
}
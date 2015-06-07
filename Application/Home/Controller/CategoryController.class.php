<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

/**
 * 后台分类管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class CategoryController extends AdminController {

    /**
     * 分类管理列表
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        $tree = D('Category')->getTree(0,'id,name,title,sort,pid,allow_publish,status');
        $this->assign('tree', $tree);
        C('_SYS_GET_CATEGORY_TREE_', true); //标记系统获取分类树模板
        $this->meta_title = '分类管理';
        $this->display();
    }

    /**
     * 显示分类树，仅支持内部调
     * @param  array $tree 分类树
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function tree($tree = null){
        C('_SYS_GET_CATEGORY_TREE_') || $this->_empty();
        $this->assign('tree', $tree);
        $this->display('tree');
    }

    /* 编辑分类 */
    public function edit($id = null, $pid = 0){
        $Category = D('Category');
        if(IS_POST){
            //原先状态的回复状态
            $oldreply   = $Category->where(array('id'=>I('id',0)))->getField('reply');
            $model_id   = $_POST['model'][0];
            $replyname  = 'reply'.strtolower($_POST['name']);
            $replytitle = $_POST['title'];
            if(false !== $Category->update()){
                //判断是否含有reply修正
                $replytype = I('reply',0);
                //当新旧配置不同 且都为数字时候 激活创建
                if($replytype!=$oldreply){
                    //如果原来有 则删除表
                    if($oldreply==1){
                        $delstatus = api('Model/del_model',array('name'=>$replyname));
                        if(!$delstatus){
                            $this->success('编辑成功！但是清除回复表失败', U('index'));
                        }
                    } else {
                        $setstatus = self::setAutoreply($model_id,$replyname,$replytitle);
                        if($setstatus[0]==false){
                            $this->error('编辑失败！'.$setstatus[1]);
                        }
                    }
                }
                    $this->success('编辑成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = '';
            if($pid){
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,name,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $info = $id ? $Category->info($id) : '';

            $this->assign('info',       $info);
            $this->assign('category',   $cate);
            $this->meta_title = '编辑分类';
            $this->display();
        }
    }
    /**
     * 自动生成回复类型
     * 自动创建该分类的回复数据表   reply_分类标识
     * @author 陈登禄 <530003247@qq.com>
     */
    protected function setAutoreply($model_id,$replyname,$replytitle){
        unset($_POST);
        if(empty($model_id)){
            return array(false,'未绑定模型');
        }
        //判断实际表是否存在
        //获取该分类回复模型id
        $modelinfo = M('Model')->where(array('name'=>$replyname))->find();
        $modelid   = $modelinfo['id'];
        if(empty($modelinfo)){
            //获取基础回复模型 继承基础回复模型
            $replytablename  = D('Replydocument')->replytable;
            $extend_modelid  = api('Model/get_model_id',array('name'=>$replytablename));
            if(empty($extend_modelid)){
                return array(false,'请先创建replydocument回复基础模型');
            }
            $_POST['name']        = $replyname;
            $_POST['title']       = $replytitle.'回复列表';
            $_POST['extend']      = $extend_modelid;
            $_POST['engine_type'] = 'MyISAM';
            $_POST['need_pk']     = 1;
            $addModel     = D('Model');
            $model_info   = $addModel->update();
            if(!$model_info){
                return array(false,$addModel->getError());
            }
            $modelinfo = $_POST;
            //获取 生成的回复列表ID
            $modelid  = $model_info['id'];
        }
        $table_name = C('DB_PREFIX').$modelinfo['name'];
        //判断是否已经有该表
        $hastable   = api('Model/get_table_by_name',array('name'=>$replyname));
        //如果没有表 则创建表
        if(!$hastable){
                $sql = <<<sql
                CREATE TABLE IF NOT EXISTS `{$table_name}` (
                `id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键' ,
                )
                ENGINE={$modelinfo['engine_type']}
                DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
                CHECKSUM=0
                ROW_FORMAT=DYNAMIC
                DELAY_KEY_WRITE=0
                ;
sql;
            $res = M()->execute($sql);
            if($res==false){
                return array(false,'创建回复表失败！');
            }
        }
            $addAttribute = D('Attribute');
            $fieldlist    = array();
            //获取 文章继承的公共默认回复字段
            $fieldlist = $addAttribute->where(array('model_id'=>$model_id,'reply_show'=>1))->field('id,model_id',true)->select();
            //创建表字段
            if(!empty($fieldlist)){
            //循环添加字段  实际创建表
            foreach ($fieldlist as $key => $value) {
                if($value['value'] === ''){
                    $default = '';
                }elseif (is_numeric($value['value'])){
                    $default = ' DEFAULT '.$value['value'];
                }elseif (is_string($value['value'])){
                    $default = ' DEFAULT \''.$value['value'].'\'';
                }else {
                    $default = '';
                }
            $sql = <<<sql
                ALTER TABLE `{$table_name}`
ADD COLUMN `{$value['name']}`  {$value['field']} {$default} COMMENT '{$value['title']}';
sql;

                $res = M()->execute($sql);
                if($res==false){
                    $creatfields = false;
                    break;
                }
            }
            if($creatfields == false){
            //如果任何字段添加错误 则删除表
        $sql = <<<sql
                DROP TABLE {$table_name};
sql;
               $res = M()->execute($sql);
               return array(false,'创建回复表字段失败！');
            }
                //重置字段信息
                $list_grid    = array();
                $list_grid[0] = 'id:ID';
                //批量增加属性
                $listnums     = 1;
                foreach ($fieldlist as $key => $value) {
                    $fieldlist[$key]['model_id'] = $modelid;
                    $list_grid[$listnums++] = $fieldlist[$key]['name'].':'.$fieldlist[$key]['title'];
                }
                $list_grid[$listnums] = "id:操作:[EDIT]|编辑,[DELETE]|删除@ajax-get";            
                 //尾部添加常规操作
                $data['list_grid'] = implode("\n", $list_grid);
                $setStatus  = M('Model')->where(array('id'=>$modelid))->save($data);
                if($setStatus==false){
                    return array(false,'更新该分类回复模型失败');
                } else {
                    foreach ($fieldlist as $key => $value) {
                        $addAttribute->update($value);
                    }
                }
            }
                return true;
    }
    /* 新增分类 */
    public function add($pid = 0){
        $Category = D('Category');

        if(IS_POST){ //提交表单
            if(false !== $Category->update()){
                $model_id   = $_POST['model'][0];
                $replyname  = 'reply'.strtolower($_POST['name']);
                $replytitle = $_POST['title'];
                $tablename  = C('DB_PREFIX').$replyname;
                //原先状态的回复状态
                //原无现有
                if(I('reply',0)==1){
                    $setstatus = self::setAutoreply($model_id,$replyname,$replytitle);
                    if($setstatus[0]==false){
                        $this->error('新增成功！'.$setstatus[1]);
                    }
                }
                    $this->success('新增成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = array();
            if($pid){
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,name,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
            }
            /* 获取分类信息 */
            $this->assign('category', $cate);
            $this->meta_title = '新增分类';
            $this->display('edit');
        }
    }
    /**
     * 删除一个分类
     * @author huajie <banhuajie@163.com>
     */
    public function remove(){
        $cate_id = I('id');
        if(empty($cate_id)){
            $this->error('参数错误!');
        }

        //判断该分类下有没有子分类，有则不允许删除
        $child = M('Category')->where(array('pid'=>$cate_id))->field('id')->select();
        if(!empty($child)){
            $this->error('请先删除该分类下的子分类');
        }

        //判断该分类下有没有内容
        $document_list = M('Document')->where(array('category_id'=>$cate_id))->field('id')->select();
        if(!empty($document_list)){
            $this->error('请先删除该分类下的文章（包含回收站）');
        }

        //删除该分类信息
        $res = M('Category')->delete($cate_id);
        if($res !== false){
            //记录行为
            action_log('update_category', 'category', $cate_id, UID);
            $this->success('删除分类成功！');
        }else{
            $this->error('删除分类失败！');
        }
    }

    /**
     * 操作分类初始化
     * @param string $type
     * @author huajie <banhuajie@163.com>
     */
    public function operate($type = 'move'){
        //检查操作参数
        if(strcmp($type, 'move') == 0){
            $operate = '移动';
        }elseif(strcmp($type, 'merge') == 0){
            $operate = '合并';
        }else{
            $this->error('参数错误！');
        }
        $from = intval(I('get.from'));
        empty($from) && $this->error('参数错误！');

        //获取分类
        $map = array('status'=>1, 'id'=>array('neq', $from));
        $list = M('Category')->where($map)->field('id,title')->select();

        $this->assign('type', $type);
        $this->assign('operate', $operate);
        $this->assign('from', $from);
        $this->assign('list', $list);
        $this->meta_title = $operate.'分类';
        $this->display();
    }

    /**
     * 移动分类
     * @author huajie <banhuajie@163.com>
     */
    public function move(){
        $to = I('post.to');
        $from = I('post.from');
        $res = M('Category')->where(array('id'=>$from))->setField('pid', $to);
        if($res !== false){
            $this->success('分类移动成功！', U('index'));
        }else{
            $this->error('分类移动失败！');
        }
    }

    /**
     * 合并分类
     * @author huajie <banhuajie@163.com>
     */
    public function merge(){
        $to = I('post.to');
        $from = I('post.from');
        $Model = M('Category');

        //检查分类绑定的模型
        $from_models = explode(',', $Model->getFieldById($from, 'model'));
        $to_models = explode(',', $Model->getFieldById($to, 'model'));
        foreach ($from_models as $value){
            if(!in_array($value, $to_models)){
                $this->error('请给目标分类绑定' . get_document_model($value, 'title') . '模型');
            }
        }

        //检查分类选择的文档类型
        $from_types = explode(',', $Model->getFieldById($from, 'type'));
        $to_types = explode(',', $Model->getFieldById($to, 'type'));
        foreach ($from_types as $value){
            if(!in_array($value, $to_types)){
                $types = C('DOCUMENT_MODEL_TYPE');
                $this->error('请给目标分类绑定文档类型：' . $types[$value]);
            }
        }

        //合并文档
        $res = M('Document')->where(array('category_id'=>$from))->setField('category_id', $to);

        if($res){
            //删除被合并的分类
            $Model->delete($from);
            $this->success('合并分类成功！', U('index'));
        }else{
            $this->error('合并分类失败！');
        }

    }
}

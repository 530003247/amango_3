<?php
namespace Home\Model;
use Think\Model;
use Think\Page;

/**
 * 回复基础模型
 */
class ReplydocumentModel extends Model{
    /* 自动验证规则 */
    protected $_validate = array(
        array('documentid', 'require', '回复的帖子ID', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
        array('documentid', 'check_documentid', '该帖子不支持回复', self::MUST_VALIDATE , 'callback', self::MODEL_BOTH),
    );

    /* 自动完成规则 */
    protected $_auto = array(
        //fromuserid fromusertype status bookmarks creattime tousertype touserid
        array('touserid', 'set_touserid', self::MODEL_BOTH, 'callback'),
        array('tousertype', 'set_tousertype', self::MODEL_BOTH, 'callback'),
        array('status', 1, self::MODEL_INSERT),
        array('bookmarks', '', self::MODEL_INSERT),
        array('creattime', NOW_TIME, self::MODEL_BOTH),
    );

    public $page = '';

    public function set_touserid(){
        $toid   = I('post.touserid');
        $totype = I('post.tousertype');

        if(!empty($toid)&&!empty($totype)){
            $nickname = get_cms_username($toid,$totype);
            if(!empty($nickname)){
                return $toid;
            }
        }
        return '';
    }

    public function set_tousertype(){
        $toid   = I('post.touserid');
        $totype = I('post.tousertype');

        if(!empty($toid)&&!empty($totype)){
            $nickname = get_cms_username($toid,$totype);
            if(!empty($nickname)){
                return $totype;
            }
        }
        return '';
    }

    public function check_documentid(){
        $nums = M('Document')->where(array('id'=>I('post.documentid'),'status'=>1))->count();
        if($nums>0){
            return true;
        } else {
            return false;
        }
    }

    protected static function model_msg($info,$status=0){
        return array('status'=>$status, 'info'=>$info);
    }

    /**
     * 获取扩展模型对象
     * @param  integer $model 模型编号
     * @return object         模型对象
     */
    private function setUserinfo($model){
        $name  = parse_name(get_document_model($model, 'name'), 1);
        $class = is_file(MODULE_PATH . 'Logic/' . $name . 'Logic' . EXT) ? $name : 'Base';
        $class = MODULE_NAME . '\\Logic\\' . $class . 'Logic';
        return new $class($name);       
    }

    public function checkReplyDocument($id){
        $info  = M('Document')->where(array('id'=>$id,'status'=>1))->find();
        if(empty($info)){
            return self::model_msg('回复的文档不能为空');
        }
        $Categoryinfo = api('Category/get_category',array('id'=>$info['category_id']));
        $model        = 'reply'.$Categoryinfo['name'];
        $replymodelid = api('Model/get_model_id',array('name'=>$model));
        //如果没有该类型的回复
        if(!empty($replymodelid)){
            $Modelold = D(parse_name(get_table_name($replymodelid),1));
            //检查分类回复的权限
            if($Categoryinfo['status']!=1){
                return self::model_msg('谢谢您的参与！'.$Categoryinfo['title'].'分类评论已关闭');
            }
            if($Categoryinfo['reply']!=1){
                return self::model_msg('谢谢您的参与！'.$Categoryinfo['title'].'分类评论已关闭');
            }
            //检查文档的回复的权限
            if($info['replyunique']!=1){
                return self::model_msg('谢谢您的参与！该篇'.$info['title'].'的内容评论关闭');
            }

            //判断是否报名是否已满 0为无上限
            if($info['replylimit']>0){
                //检查文档的已回复数目
                $has_commontnums = $Modelold->count();
                if($info['replylimit']<$has_commontnums){
                    return self::model_msg('谢谢您的参与！参加评论人数已满');
                }
            }
            //TODO 黑名单用户禁止回复 用户  积分限制等等
            //判断用户回复是否唯一
            if($info['replyunique']==1){
                //判断该用户是否已经发表过
                $hasreply = $this->where(array('fromuserid'=>$userid))->count();
                if($hasreply>0){
                    return self::model_msg('谢谢您的参与！您已经评论过');
                }
            }
            //获取 基础回复模型数据
            $basedata  = $this->create();
            $Thiserror = $this->getError();
            if(!empty($Thiserror)){
                return self::model_msg($Thiserror);
            }
            //检查 检验附属回复模型
            $Model      = $this->checkAttr($Modelold,$replymodelid);
            //获取 附属回复模型数据
            $subdata    = $Model->create();
            $Modelerror = $Model->getError();
            if(!empty($Modelerror)){
                return self::model_msg($Modelerror);
            }

                $baseid = $this->add($basedata);
                if($baseid>0){
                    $subdata['id'] = $baseid;
                    $status = $Model->add($subdata);
                    if($status>0){
                        return true;
                    } else {
                        $this->where(array('id'=>$baseid))->delete();
                        return self::model_msg('不支持回复');
                    }
                } else {
                    return self::model_msg('不支持回复');
                }
        } else {
            //不存在回复模型  TODO  上级模型
            return self::model_msg('不支持回复');
        }
    }
    public function update($id){
        $res    = $this->checkReplyDocument($id);
        if($res==true){
            return true;
        } else {
            return $res['info'];
        }
        //$status = $this->logic($info['model_id'])->reply();
    }
    protected function checkAttr($Model,$model_id){
        $fields     =   get_model_attribute($model_id,false);
        $validate   =   $auto   =   array();
        foreach($fields as $key=>$attr){
            if($attr['is_must']){// 必填字段
                $validate[]  =  array($attr['name'],'require',$attr['title'].'必须填写!');
            }            
            // 自动验证规则
            if(!empty($attr['validate_rule'])) {
                $validate[]  =  array($attr['name'],$attr['validate_rule'],$attr['error_info']?$attr['error_info']:$attr['title'].'验证错误',0,$attr['validate_type'],$attr['validate_time']);
            }
            // 自动完成规则
            if(!empty($attr['auto_rule'])) {
                $auto[]  =  array($attr['name'],$attr['auto_rule'],$attr['auto_time'],$attr['auto_type']);
            }elseif('checkbox'==$attr['type']){ // 多选型
                $auto[] =   array($attr['name'],'arr2str',3,'function');
            }elseif('datetime' == $attr['type']){ // 日期型
                $auto[] =   array($attr['name'],'strtotime',3,'function');
            }elseif ('laiyuanbox'==$attr['type']) {
                $auto[] =   array($attr['name'],'arr2str',3,'function');
            }
        }
        return $Model->validate($validate)->auto($auto);
    }

    /**
     * 获取扩展模型对象
     * @param  integer $model 模型编号
     * @return object         模型对象
     */
    private function logic($model){
        $name  = parse_name(get_document_model($model, 'name'), 1);
        $class = is_file(MODULE_PATH . 'Logic/' . $name . 'Logic' . EXT) ? $name : 'Base';
        $class = MODULE_NAME . '\\Logic\\' . $class . 'Logic';
        return new $class($name);  		
    }
    public function lists($documentid,$rows=10,$order='creattime desc',$status = 1){
        $list = $this->where(array('documentid'=>$documentid,'status'=>$status))->order($order)->limit($rows)->select();
        if(empty($list)){
            return '';
        } else {
            //TODO 联表查询
            $category_id   = M('Document')->where(array('id'=>$documentid))->getField('category_id');
            $category_name = api('Category/get_category',array('id'=>$category_id,'field'=>'name'));
            $submodel      = $this->get_subname($category_name);
            $newlist       = array();
            foreach ($list as $key => $value) {
                $subinfo       = array();
                $subinfo       = $submodel->where(array('id'=>$value['id']))->field('id',true)->find();
                $newlist[$key] = array_merge($value,$subinfo);
            }
                return $newlist;
        }
    }
    public function lists_has_comments($documentid,$rows=10,$order='creattime desc',$status = 1){
        $haslist = $this->lists($documentid,$rows,$order,$status);
        if(!empty($haslist)){
            foreach ($haslist as $key => $value) {
                if(!empty($value['fromuserid'])&&!empty($value['fromuserid'])){
                    $haslist[$key]['fromnickname'] = get_cms_username($value['fromuserid'],$value['fromusertype']);
                    $haslist[$key]['fromheadimg']  = get_cms_userpic($value['fromuserid'],$value['fromusertype']);
                }
                if(!empty($value['touserid'])&&!empty($value['tousertype'])){
                    $haslist[$key]['tonickname']   = get_cms_username($value['touserid'],$value['tousertype']);
                    $haslist[$key]['toheadimg']    = get_cms_userpic($value['touserid'],$value['tousertype']);
                }
                    $haslist[$key]['creat_time']   = date('Y-m-d',$value['creattime']);
                    $haslist[$key]['content']      = get_cms_samils($value['content']);
            }
        }
            return $haslist;
    }
    public function get_subname($name){
        static $subModel;
        if(!isset($subModel[$name])){
            $replymodelid    = api('Model/get_model_id',array('name'=>'reply'.$name));
            $subModel[$name] = D(parse_name(get_table_name($replymodelid),1));
        }
           return $subModel[$name];
    }
}

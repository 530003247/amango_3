<?php
namespace Admin\Controller;

/**
 * 后台配置控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class CreditController extends AdminController {

    /**
     * 配置管理
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        $model = D('Credits');
        $total = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 5;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $list = $model->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('_page',$page->show());

        $action = $this->Credit_ACTION();
        $types  = $this->Credit_TYPE();
        $zhouqi = $this->Credit_ZQ();
        $pinglv = $this->Credit_PL();
        $usergroup = $this->Credit_USER();
        $addtype   = $this->Credit_ADDTYPE();

        foreach ($list as $key => $value) {
            $type = $value['type'];
            $list[$key]['type'] = $types[$value['type']];
            $list[$key]['action'] = $action[$type][$value['action']];
            $list[$key]['zhouqi'] = $zhouqi[$value['zhouqi']];
            $list[$key]['pinlv'] = $pinglv[$value['pinlv']];
            $list[$key]['usergroup']  = $usergroup[$value['usergroup']];
            $list[$key]['value_type'] = $addtype[$value['value_type']];
        }
        $this->assign('_list',$list);
        $this->display();
    }
    public function manage(){
        $model = D('credits_log');
        $total = $model->count();
        $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 5;
        $page = new \Think\Page($total, $listRows);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $list = $model->limit($page->firstRow.','.$page->listRows)->order('create_time desc')->select();
        $this->assign('_page',$page->show());
        

        $action = $this->Credit_ACTION('ALL');
        $usergroup = $this->Credit_USER();
        $addtype   = $this->Credit_ADDTYPE();

        foreach ($list as $key => $value) {
            $list[$key]['action'] = $action[$value['action']];
            $list[$key]['usergroup']  = $usergroup[$value['usergroup']];
            $list[$key]['add_type'] = $addtype[$value['add_type']];
        }
        $this->assign('_list',$list);
        $this->display();
    }
    public function del(){
        $id = array_unique((array)I('ids'));
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map = array('id' => array('in', $id) );
        if(M('Credits')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }
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
    //周期
    public function Credit_TYPE(){
        return array(
               'CMS'     =>'网站',
               'WEIXIN'  =>'微信',
            );
    }
    public function Credit_ACTION($type=null){
        $baseaction = array(
               'CMS'     => array(
                            'ag_cms_newregister'   =>'【前台】cms新注册',
                            'ag_cms_invite'        =>'【前台】cms邀请',
                            'ag_cms_acceptinvite'  =>'【前台】cms接受邀请',
                            'ag_cms_oauth'         =>'【前台】cms实名制认证',
                            'ag_cms_share_links'   =>'【前台】分享推广链接：条件:分类ID',
                            'ag_cms_share_friends' =>'【微信】分享至朋友圈：条件:分类ID',
                            'ag_cms_share_friend'  =>'【微信】分享给朋友：  条件:分类ID',
                            'ag_cms_topics_add'    =>'【前台】发表话题：    条件:分类ID',
                            'ag_cms_topics_comment'=>'【前台】发表评论：    条件:分类ID',
                            'ag_cms_topics_delete' =>'【管理员】删除话题：  条件:分类ID',
                            'ag_cms_topics_settop' =>'【前台】话题置顶：    条件:分类ID',
                            'ag_cms_topics_views'  =>'【前台】访问量：      条件:分类ID',
                            'ag_cms_topics_books'  =>'【前台】收藏量：      条件:分类ID',
                            'ag_cms_topics_goods'  =>'【前台】点赞量：      条件:分类ID',
                        ),
               'WEIXIN'  => array( 
                            'ag_weixin_newregister'      =>'【微信】关注',
                            'ag_weixin_enable_keyword'   =>'【微信】激活关键词',
                            'ag_weixin_enable_menu'      =>'【微信】切换菜单',
                            'ag_weixin_enable_addons'    =>'【微信】插件',
                            'ag_weixin_enable_oauth'     =>'【微信】实名制(修改昵称，完善资料)',
                            'ag_weixin_click_menu'       =>'【微信】指定菜单按钮：条件:菜单标识(方向:LEFT,CENTER,RIGHT;键位:0~4)',
                            'ag_weixin_request_addons'   =>'【微信】指定插件：条件:examl(插件唯一标识)',
                            'ag_weixin_request_keyword'  =>'【微信】指定关键词：条件:1(关键词ID)',
                            'ag_weixin_request_response' =>'【微信】指定响应体：条件:1(响应体ID)',
                            'ag_weixin_request_type'     =>'【微信】激活对应类型关键词：条件:text【支持*/images/location/voice】(消息唯一类型)',
                        ),
            );
        if(in_array($type, array('WEIXIN','CMS'))){
            return $baseaction[$type];
        } else {
            if($type=='ALL'){
                return array_merge($baseaction['CMS'],$baseaction['WEIXIN']);
            } else {
                return $baseaction;
            }
        }
    }
    //周期
    public function Credit_ZQ(){
        return array(
               '*'  =>'每天：无需定义参数',
               '#'  =>'每天/月固定时间段：参数：日:2-18(每天2-18时)；月:2-13:2-13(每个月的2到13号的每天2-13时)',
               '@'  =>'指定日期：         参数：2014-1-2:2-4(2014年1月2号2时至4时)'
            );
    }
    //频率
    public function Credit_PL(){
        return array(
               '!' =>'一次性：无需定义参数',
               '*' =>'每次：无需定义参数',
               '<' =>'最大允许次数: 参数：1(最多一次)当指定日期时，本配置无效',
            );
    }
    //用户组
    public function Credit_USER(){
        $group = M('Followercate')->field('followercate_title,followercate_name')->select();
        foreach ($group as $key => $value) {
            $newgroup[$value['followercate_title']] = $value['followercate_name'];
        }
        return array_merge(array('*'=>'所有用户'),$newgroup);
    }
    //增加规则
    public function Credit_ADDTYPE(){
        return array(
               '1'  =>'【加】',
               '2'  =>'【减】',
               '3'  =>'【乘】',
               '4'  =>'【除】',
               '5'  =>'【累加】(例如,猫扑第一天1分第二天2分第三天3分第四天4分)',
               '6'  =>'【累减】(例如,QQ钻石第一天-1分第二天-2分第三天-3分第四天-4分)',
            );
    }
    public function add(){
        if(IS_POST){
            $data     = I('post.');
            if(empty($data['type'])||!is_numeric($data['value'])){
                if(!is_numeric($data['value'])){
                    $this->error('积分方式只能为数字哦');
                } else {
                    $this->error('请选择正确的规则类型，CMS OR WEIXIN?');
                }
            } else {
                $data['status'] = 1;
                $model  = M('Credits');
                $status = $model->add($data);
                if($status===fase){
                    $this->error('设置积分规则错误,'.$model->getError());
                } else {
                    $this->success('设置积分规则成功！',U('index'));
                }
            }
        } else {
            // $status = Credits::ag_cms_newregister(1,23);
            // dump($status);die;
            $this->assign('addtype',$this->Credit_ADDTYPE());
            $this->assign('zhouqi',$this->Credit_ZQ());
            $this->assign('pinlv',$this->Credit_PL());
            $this->assign('usergroup',$this->Credit_USER());
            $this->assign('type',$this->Credit_TYPE());
            $this->assign('actionlist',$this->Credit_ACTION());
            $this->display();
        }
    }

    /**
     * 编辑配置
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function edit($id = 0){
        if(IS_POST){
            $postid = I('post.id');
            $data   = I('post.');
            unset($data['id']);
            if(empty($data['type'])||!is_numeric($data['value'])){
                if(!is_numeric($data['value'])){
                    $this->error('积分方式只能为数字哦');
                } else {
                    $this->error('请选择正确的规则类型，CMS OR WEIXIN?');
                }
            } else {
                $model  = M('Credits');
                $status = $model->where(array('id'=>$postid))->save($data);
                if($status===fase){
                    $this->error('编辑积分规则错误,'.$model->getError());
                } else {
                    $this->success('编辑积分规则成功！',U('index'));
                }
            }
        } else {
            $id = I('get.ids');
            if(empty($id)){
                $this->error('请选择要操作的积分规则');
            }
            $info = M('Credits')->where(array('id'=>$id))->find();
            //dump($info);die;
            $this->assign('info',$info);
            $this->assign('addtype',$this->Credit_ADDTYPE());
            $this->assign('zhouqi',$this->Credit_ZQ());
            $this->assign('pinlv',$this->Credit_PL());
            $this->assign('usergroup',$this->Credit_USER());
            $this->assign('type',$this->Credit_TYPE());
            $this->assign('actionlist',$this->Credit_ACTION());
            $this->display();
        }
    }
}

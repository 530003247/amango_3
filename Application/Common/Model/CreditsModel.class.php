<?php
namespace Common\Model;
use Think\Model;
/**
 * 积分管理
 * 使用方法 Credits::标签名($userid,额外参数,记录语句);
 */
class CreditsModel extends Model{
	public $deny_static = array('J_user','J_action','J_cycle','J_rate','credits_model','formula');
	private $_logmodel;
	private $_credits_model;
    private $_groupmodel;
	private $_lists;
	private $_userid;
    private $_actionname;
    private $_userinfo;
    private $_cycle_param;
    private $_checkuser    = array('weixin','home');
    private $_credits_type = array('weixinmember','score');
    private $_checklevel   = array('followercate','followercate_title','start_score','end_score');
    
    /**
     * 根据积分 初始化等级
     * @param  num  $usrid 用户ID
     * @return bool        true/false
     */
    public function init($usrid){
        //获取用户当前用户组标识
        $info      = api('Wxuser/get_info',array('id'=>$usrid));
        //如果该用户属于黑名单  冻结名单  锁定名单   无法自动升级
        if(!in_array($info['cate_group'], array('black','block','locked'))){
            //获取基础积分
            $userscore = $this->getUserCredits($usrid);
            $this->group_model();
            $map['status']      = 1;
            //起始积分小于等于用户积分
            $map['start_score'] = array('elt',$userscore);
            //上限积分大于用户积分
            $map['end_score']   = array('gt',$userscore);
            //获取该积分存在区段 的用户组标识
            $group_title        = $this->_groupmodel->where($map)->order('start_score asc')->getField('followercate_title');
            //设置新分组名称
            if($group_title!=$info['cate_group']){
                api('Wxuser/update_info_id',array('id'=>$usrid,'data'=>array('cate_group'=>$group_title)));
            }
                return $group_title;
        }
                return false;
    }
    /**
     * 获取用户积分等级列表
     * @param  string  $type 用户分组类型 1/2 等级/功能
     * @return array         用户分组列表
     */
    public function getUserGroup($type=1){
        static $grouplist;
        if(!isset($grouplist[$type])||empty($grouplist[$type])){
            $this->group_model();
            $group_list = $this->_groupmodel->where(array('type'=>$type))->select();

            $grouplist    = array();
            foreach ($group_list as $key => $value) {
                $grouplist[$type][$value['followercate_title']] = $value;
            }
        }
                return $grouplist[$type];
    }
    private function group_model(){
        if(!isset($this->_groupmodel)||empty($this->_groupmodel)){
            $this->_groupmodel = M($this->_checklevel[0]);
        }
    }
    /**
     * 监听行为
     * @param  string $action     监听标签
     * @param  number $usrid      添加积分用户ID
     * @param  string $params     行为参数
     * @param  string $logcontent 记录用语
     */
	public function listen($action,$usrid,$params,$logcontent){
		if(empty($usrid)||empty($action)){
            return false;
		}
		$usercategroup  = api('Wxuser/get_info',array('id'=>$usrid,'field'=>'cate_group'));
		$cate_group     = $usercategroup['cate_group'];

		if(empty($cate_group)){
            return false;
		}
        $map['action']      = $action;
        $map['status']      = 1;
        $map['usergroup']   = array('in',array($cate_group,'*'));
		$actionlist = $this->where($map)->field('id',true)->select();
        if(empty($actionlist)){
            return false;
		}
            
		$this->_userinfo   = array('user_id'=>$usrid,'user_type'=>'user','user_group'=>$cate_group);
		$this->_actionname = $action;
		$this->log_model();
foreach ($actionlist as $key => $actioninfo) {
        $this->_lists   = $actioninfo;
        //TODO自由设置 哪张表的字段  积分表
        //判断用户
        if($this->J_user($cate_group,$actioninfo['usergroup'])==false){
            return false;
        }
        //判断行为
		if($this->J_action($actioninfo['action_param'],$params)==false){
            return false;
		}
        //判断周期
		if($this->J_cycle($actioninfo['zhouqi'],$actioninfo['zhouqi_param'])==false){
            return false;
		}
		//判断频率
		if($this->J_rate($actioninfo['pinlv'],$actioninfo['pinlv_param'])==false){
            return false;
		}
        $this->save_credits($action,$usrid,$actioninfo['value'],$actioninfo['value_type'],$cate_group,$logcontent);
}
	}
    private function log_model(){
    	if(!isset($this->_logmodel)||empty($this->_logmodel)){
    		$this->_logmodel = M('credits_log');
    	}
    }
    private function credits_model(){
    	if(!isset($this->_credits_model)||empty($this->_credits_model)){
    		$this->_credits_model = M($this->_credits_type[0]);
    	}
    }
    //被操作者用户组判断   【被操作者!=操作者】
    private function J_user($cate_group,$param){
    	if($param=='*'){
            return true;
    	} else {
	            $usgrouplist = explode(',', $param);
	            if(in_array($cate_group, $usgrouplist)){
	                return true;
	            }
    	}
    	    return false;
    }
    private function J_action($param,$params){
    	if(!empty($param)){
	        list($type,$param) = explode(':', $param);
	        empty($param) && list($type,$param) = array('in',$type);
        	switch ($actionparam[0]) {
        		case 'eq':
        		    if($params==$param){
        		    	return true;
        		    }
        			break;
        		default://默认为in
        		    $condition   = explode(',', $param);
        		    if(in_array($params,$condition)){
        		    	return true;
        		    }
        			break;
        	}
        	return false;
    	}
    	    return true;
    }
	//周期判断
    private function J_cycle($type,$param){
    	$now       = date('H');
    	$nowdate   = strtotime(date('Y-m-d'));
    	$tomordate = strtotime(date("Y-m-d",strtotime("+1 day")));
    	switch ($type) {
    		case '*':
            	$map['create_time'] = array('between',array($nowdate,$tomordate));
            	$this->_cycle_param = array(true,$map);
    			return true;
    			break;
    		case '#':
    		    if(empty($param)){
                    return false;
    		    } else {
    		    	$cyq = explode(':', $param);
    		    	if($cyq[0]=='日'){
                        $hourparam = explode('-', $cyq[1]);
                        if($now>=$hourparam[0]&&$now<=$hourparam[1]){
                        	$map['create_time'] = array('between',array($nowdate,$tomordate));
                        	$this->_cycle_param = array(true,$map);
                            return true;
                        }
    		    	} else {
		    			$monthparam = explode('-', $cyq[1]);
		    			$hourparam  = explode('-', $cyq[2]);
    		    		if($cyq[0]=='月'&&$monthparam[1]>$monthparam[0]&&$monthparam[0]>0&&$hourparam[0]>=0&&$hourparam[0]<$hourparam[1]){
    		    			$nowmonth   = date('m');
    		    			if($nowmonth<=$monthparam[1]&&$nowmonth>=$monthparam[0]&&$now>=$hourparam[0]&&$now<=$hourparam[1]){
                        	    $map['create_time']  = array('between',array(strtotime(date('Y-m-01',$nowdate)),strtotime(date('Y-m-d', strtotime(date('Y-m-01', $nowdate).' +1 month -1 day')))));
    		    				$this->_cycle_param = array(true,$map);
                                return true;
    		    			}
    		    		}
    		    	}
    		    }
    			break;
    		case '@':
		    	$cyq = explode(':', $param);
		    	if(count($cyq)!=2){
                   return false;
		    	} else {
    		    	//判断是否为同一天
    		    	if(strtotime(date('Y-m-d',time()))==strtotime(date($cyq[0]))){
    		    		$hourparam = explode('-', $cyq[1]);
                        if($now>=$hourparam[0]&&$now<=$hourparam[1]){
                        	$this->_cycle_param = false;
                            return true;
                        }
    		    	}
		    	}
    			break;
    	}
           return false;
    }
    private function J_rate($type,$param){
    	if(empty($this->_userinfo)||empty($this->_logmodel)){
            return false;
    	}
    	if($this->_cycle_param!=false){
	    	switch ($type) {
	    		case '!':
	    		    $condition           = $this->_userinfo;
	    		    $condition['action'] = $this->_actionname;
	    			$has = $this->_logmodel->where($condition)->count();
	    			if($has==0){
	                    return true;
	    			}
	    			break;
	    		case '*':
	    			return true;
	    			break;
	    		case '<':
					$condition                = $this->_userinfo;
					$condition['create_time'] = $this->_cycle_param[1]['create_time'];
					$condition['action']      = $this->_actionname;
	    		    $has = $this->_logmodel->where($condition)->count();
	    			if($has<$param){
	                    return true;
	    			}
	    			break;
	    	}
	    	return false;
    	} else {
    		return true;
    	}
    }
    public function getUserCredits($userid){
    	$this->credits_model();
    	if(empty($this->_credits_model)){
            return false;
    	} else {
    		$map['id'] = $userid;
    		$scores    = $this->_credits_model->where($map)->getField($this->_credits_type[1]);
    		return $scores;
    	}
    }
    private function formula($action,$score,$value,$value_type,$usertype){
    	if(!is_numeric($value)){
            return false;
    	}
    	empty($score) && $score = 0;
        switch ($value_type) {
        	case '2':
        		$score = $score - $value;
        		break;
        	case '3':
        		$score = $score * $value;
        		break;
          	case '4':
        		$score = $score / $value;
        		break;
        	case '5':
    	        $map['action']   = $action;
    	        $map['add_type'] = $value_type;
    	        $logdata['user_group']   = $usertype;
    	        $lastcredits     = $this->_logmodel->where($map)->order('create_time desc')->getField('credits');
    	        empty($lastcredits) && $lastcredits = 0;
    	        $value = $value + $lastcredits;
        		$score = $score + $value;
        		break;
        	case '6':
    	        $map['action']   = $action;
    	        $map['add_type'] = $value_type;
    	        $logdata['user_group']   = $usertype;
    	        $lastcredits     = $this->_logmodel->where($map)->order('create_time desc')->getField('credits');
    	        empty($lastcredits) && $lastcredits = 0;
    	        $value = $value + $lastcredits;
    	        $score = $score - $value;
        		break;
        	default:
        		$score = $score + $value;
        		break;
        }
            return array($score,$value);
    }

    private function save_credits($action,$userid,$value,$value_type,$usertype,$logcontent){
    	//获取基本积分
		$scores = $this->getUserCredits($userid);
        //计算积分
        $status = $this->formula($action,$scores,$value,$value_type,$usertype);
        if($status!=false){
        	//写入记录
			$logdata['action']       = $action;
			$logdata['user_id']      = $userid;
			$logdata['user_type']    = 'user';
			$logdata['credits_type'] = implode(':', $this->_credits_type);
			$logdata['add_type']     = $value_type;
			$logdata['credits']      = $status[1];
			$logdata['remark']       = $logcontent;
			$logdata['create_time']  = time();
			$logdata['user_group']   = $usertype;
        	$logstatus = $this->_logmodel->add($logdata);
            if($logstatus>0){
            	//更新积分数据
		    	$this->_credits_model->where(array('id'=>$userid))->save(array('score'=>$status[0]));
            }
        }
    }
    /**
     * 积分记录
     * @param  string $action  积分标签
     * @param  num    $userid  用户ID
     * @param  num    $value   新增积分
     * @param  string $logdata 用户积分记录
     * @return bool          
     */
    public function log($action,$userid,$value,$logcontent){
        $this->log_model();
        $logdata['action']       = $action;
        $logdata['user_id']      = $userid;
        $logdata['user_type']    = 'user';
        $logdata['credits_type'] = implode(':', $this->_credits_type);
        $logdata['add_type']     = 1;
        $logdata['credits']      = $value;
        $logdata['remark']       = $logcontent;
        $logdata['create_time']  = time();
        $logdata['user_group']   = 'general';//默认组
        $logstatus = $this->_logmodel->add($logdata);
        return $logstatus;
    }
    /**
     * 新用户  通过关注
     * @param  num   $userid 用户ID
     * @return bool      
     */
    public function ag_weixin_newregister($userid){
        if(!empty($userid)){
            //读取微信注册相关标签
            $map['action'] = 'ag_weixin_newregister';
            $map['status'] = 1;
            $map['type']   = 'WEIXIN';
            $value         = $this->where($map)->getField('value');
            $this->credits_model();
            $this->_credits_model->where(array('id'=>$userid))->setInc('score',$value);
            $this->log('ag_weixin_newregister',$userid,$value,'微信新关注用户，ID：'.$userid);
            return $value;
        }
            return false;
    }
    public function __call($method, $args){
    	if(empty($args[0])){
            return false;
    	} else {
    		$this->listen($method,$args[0], $args[1], $args[2]);
    	}
    }
}
?>

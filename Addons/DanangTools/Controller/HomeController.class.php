<?php

namespace Addons\DanangTools\Controller;
use Home\Controller\AddonsController;
use Home\Controller\Credits;

class HomeController extends AddonsController{
    public $login_action = array(
                'vip_huodon' =>array('errormsg'=>'查看会员活动，请先登陆','errorurl'=>''),
                'diy_t'      =>array('errormsg'=>'定制旅行，请先登陆','errorurl'=>''),
                'set_t'      =>array('errormsg'=>'定制旅行，请先登陆','errorurl'=>''),
                'vip_home'   =>array('errormsg'=>'查看会员积分，请先登陆','errorurl'=>''),
                'vip_card'   =>array('errormsg'=>'查看会员特权，请先登陆！','errorurl'=>''),
                'p_buy'      =>array('errormsg'=>'购买产品，请先登陆','errorurl'=>''),
    );
    protected function setHeader($show=false,$shareurl,$imgurl,$title,$content){
        $this->assign('HideFastmenu','1');
        $defaulturl = Amango_U('Index/index');
        $defaultimg = get_cover_pic('/Addons/DanangTools/Public/vipshow/m_vip.png');
        $d_title    = '【岘港假期】该东南亚最佳避暑区旅游城市之一的避暑区';
        $d_content  = '岘港在韩江曰左岸，北临观港湾。位于越南中部，北连顺化、南接芽庄。背靠五行山，东北有山茶半岛作屏障，海湾呈马蹄形，港阔水深，形势险要，为天然良港。自古中固景口岸。东南35公里则为联合国世界文化遗产会安古镇，从会安古镇码头搭乘摆渡船出发则可以游览秋盆河明珠之迦南岛（Dao Cam Kim），迦南岛主要以水椰林及原生态自然风光而闻名。';
        $shareurl   = empty($shareurl) ? $defaulturl : $shareurl;
        $imgurl     = empty($imgurl) ? $defaultimg : $imgurl;
        $title      = empty($title) ? $d_title : $title;
        $content    = empty($content) ? $d_content : $content;
        $Shareinfo  = array(
                        'ImgUrl'     =>$imgurl,
                        'TimeLink'   =>$shareurl,
                        'FriendLink' =>$shareurl,
                        'WeiboLink'  =>$shareurl,
                        'tTitle'     =>$title,
                        'tContent'   =>$content,
                        'fTitle'     =>$title,
                        'fContent'   =>$content,
                        'wContent'   =>$content
                    );
        $this->assign('Share',$Shareinfo);
        $this->assign('HideFastmenu','1');
        if($show){
            $this->assign('HideAll',1);
        }
    }
    //插件前台展示页面
    public function index(){
        //高级接口判断是否关注
        self::setHeader();
        $this->display();
    }
    //会员活动规则
    public function vip_huodon(){
        $model = M('Addonsdnconfig');
        $huodonlinks = stripslashes($model->where(array('key'=>'huodonlinks'))->getField('value'));
        redirect($huodonlinks);
    }
    //会员规则
    public function vip_rules(){
        $model = M('Addonsdnconfig');
        $huodonlinks = stripslashes($model->where(array('key'=>'viprules'))->getField('value'));

        $this->assign('content',$huodonlinks);
        $this->assign('Title','会员规则');
        self::setHeader(true);
        $this->display();
    }
    //定制旅行
    public function diy_t(){
        $t_type = array(
            array('婚礼','marry','p_marry.jpg'),
            array('会议','meet','p_meet.jpg'),
            array('高尔夫','golf','p_golf.jpg'),
            array('派对','party','p_party.jpg'),
            array('发布会','conference','p_conference.jpg'),
            array('其他','other','p_other.jpg'),
        );
        $this->assign('T_type',$t_type);
        $this->assign('Title','定制旅行');
        self::setHeader(true);
        $this->display();
    }
    //定制表单
    public function set_t(){
        $allow_type = array('marry'=>'婚礼','meet'=>'会议','golf'=>'高尔夫','party'=>'派对','conference'=>'发布会','other'=>'其他');
        $bus_type   = array('bus1'=>'豪华轿车','bus2'=>'豪华中巴','bus3'=>'商务包车');
        $rom_type   = array('rom1'=>'大床房','rom2'=>'单人房','rom3'=>'双人房');

        $type = I('get.type');
        if(IS_POST){
            $postinfo = I('post.');
            if(empty($type)||empty($allow_type[$type])){
                $this->error('请选择您要预订的旅行类型！');
            }
            if(!is_numeric($postinfo['nums'])||$postinfo['nums']<=0){
                $this->error('客人数大于0！');
            }
            $nowtime  = time();
            $booktime = strtotime($postinfo['time']);
            if(empty($postinfo['time'])||$nowtime>=$booktime){
                $this->error('预订日期从明天算起哦！');
            }
            if(!in_array($postinfo['rentcar'],array('1','0'))){
                $this->error('是否需要租赁车辆！');
            }
            if(empty($bus_type[$postinfo['bustype']])){
                $this->error('选择预订的车辆类型！');
            }
            if(empty($rom_type[$postinfo['romtype']])){
                $this->error('选择预订的房间类型！');
            }
            //如果存在，直接下单
            //否则，绑定个人信息
            $data['timeinfo']     = $booktime;
            $data['nums']         = $postinfo['nums'];
            $data['renttype']     = $postinfo['rentcar'];
            $data['desc']         = $postinfo['desc'];
            $data['bustype']      = $postinfo['bustype'];
            $data['romtype']      = $postinfo['romtype'];
            $data['type']         = $type;
            //dump($data);die;
            session('last_p_info',$data);
            $this->success('正在确认个人信息...',addons_url('query_p'));

        } else {
            if(empty($type)||empty($allow_type[$type])){
                $this->error('请选择您要预订的旅行类型！',addons_url('diy_t'));
            } else {
                $last_t = session('last_p_info');
                if(!empty($last_t)){
                    $this->assign('tinfo',$last_t);
                }
                $this->assign('Bus_type',$bus_type);
                $this->assign('Rom_type',$rom_type);
                $this->assign('Title','定制旅行-'.$allow_type[$type]);
                self::setHeader(true);
                $this->display();
            }
        }
    }
    public function query_p(){
        $tickets  = session('last_p_info');
        $userinfo = session('P');
        if(empty($tickets)){
            $this->error('请先定制您的旅行！',addons_url('diy_t'));
        }
        if(IS_POST){
            $info = I('post.');
            if(empty($info['name'])){
                $this->error('个人姓名不能为空！');
            }
            if(!in_array($info['sex'],array('1','0'))){
                $this->error('个人性别不能为空！');
            }
            if(empty($info['passport'])){
                $this->error('个人护照号不能为空！');
            }
            if(empty($info['tel'])&&empty($info['phone'])){
                $this->error('个人座机或者手机必填一个！');
            }
            if(empty($info['email'])){
                $this->error('个人联系邮箱不能为空！');
            }
                $tickets['name']  = $info['name'];$tickets['sex']  = $info['sex'];   $tickets['passport'] = $info['passport'];
                $tickets['tel']   = $info['tel']; $tickets['email'] = $info['email'];$tickets['birthday'] = $info['birthday'];
                $tickets['phone'] = $info['phone'];
                //用户标识
                $tickets['fromusername']  = $userinfo['fromusername'];
                $tickets['status']        = 1;
                $tickets['creattime']     = time();
                $status = M('Addonsdntickets')->add($tickets);
                if($status>0){
                    session('last_p_info','');
                    $this->success('恭喜您~定制旅行成功,返回微信公众号聊天吧！',addons_url('index'));
                } else {
                    $this->error('尊敬的客户，系统出现故障，再试试吧~');
                }
        } else {
            $info = M('Addonsdntickets')->where(array('fromusername'=>$userinfo['fromusername']))->order('id desc')->find();
            if(!empty($info)&&!empty($userinfo)){
                $this->assign('info',$info);
            }
                $t_info = session('last_p_info');
                $this->assign('type',$t_info);
                $this->assign('Title','定制旅行');
                self::setHeader(true);
                $this->display();
        }
    }
    //产品购买
    public function p_buy(){
        $model = M('Addonsdnconfig');
        $huodonlinks = stripslashes($model->where(array('key'=>'productlinks'))->getField('value'));
        redirect($huodonlinks);
    }
    protected function setVipinfo(){
        $userinfo  = session('P');
        $vipmodel  = M('Addonsdnvip');
        $condition = array('fromusername'=>$userinfo['fromusername']);
        $has = $vipmodel->where($condition)->count();
        if($has==0){
            //初始化积分
            $condition['scores'] = 0;
            $condition['status'] = 1;
            $vipmodel->add($condition);
        }
            return $vipmodel;
    }
    //我的积分
    public function vip_home(){
        $vipinfo =  $this->getViplist();
        $uid     = session('user_auth.uid');
        $this->assign('nickname',$vipinfo[0]);
        $this->assign('Title','我的积分');
        $this->assign('score',Credits::getUserCredits($uid));
        $this->assign('name',$vipinfo[1]);
        $this->assign('progress',$vipinfo[3]);
        self::setHeader(true);
        $this->display();
    }
    protected function getViplist(){
        $userinfo   = session('P');
        $vipmodel   = $this->setVipinfo();
        //读取会员积分
        $scores = $vipmodel->where(array('fromusername'=>$userinfo['fromusername']))->getField('scores');
        if(empty($scores)){
            $scores = 0;
        }
        //读取积分等级
        $scorelist  = M('Addonsdnconfig')->where(array('key'=>'scorelist'))->getField('value');
        $score_list = unserialize($scorelist);
        if(empty($score_list[2])){
            $totalsocre = 100;
        } else {
            $totalsocre = $score_list[2];
        }
        if($scores>$score_list[2]){
            $name     = '至尊';
        } else {
            if($scores>$score_list[1]){
                $name = '高级';
            } else {
                $name = '初级';
            }
        }
        $progress = ($scores/$totalsocre)*100;
        return array($userinfo['nickname'],$name,$scores,$progress);
    }
    public function vip_zhizun(){
        $vipinfo = $this->getViplist();
        $this->assign('nickname',$vipinfo[0]);
        $this->assign('name',$vipinfo[1]);
        $this->assign('Title','至尊会员');
        self::setHeader(true);
        $this->display();
    }
    //vip待遇
    public function vip_card(){
        $model = M('Addonsdnconfig');
        $huodonlinks = stripslashes($model->where(array('key'=>'vipdesc'))->getField('value'));

        $this->assign('content',$huodonlinks);
        $this->assign('Title','至尊会员');
        self::setHeader(true);
        $this->display();
    }
    //天气插件
    public function show_weather(){
        $citycode = '1252376';
        $temptype = 'c';
        $url      = 'http://xml.weather.yahoo.com/forecastrss?w='.$citycode.'&u='.$temptype;
        //$xml = file_get_contents($url);
        $reader   = new \XMLReader();
        $reader->open($url,'utf-8');
        while($reader->read()){
            if($reader->name == 'yweather:condition'){
                $code = $reader->getAttribute('code');  //获取天气代码
                $temp = $reader->getAttribute('temp');  //获取温度
            }
            if($reader->name == 'yweather:atmosphere'){
                $humi = $reader->getAttribute('humidity');  //获取湿度
            }
            if($reader->name == 'yweather:wind'){
                $wind = $reader->getAttribute('speed');  //获取风速
            }
            if($reader->name == 'yweather:forecast'){
                $weekinfo[$reader->getAttribute('day')] = array($reader->getAttribute('low'),$reader->getAttribute('high'),$reader->getAttribute('code'));
            }
        }
        $reader->close();
        $weatherinfo = $this->code2char($code);
        if(!empty($weekinfo)){
            $week_cn = array('Thu'=>'周四','Fri'=>'周五','Sat'=>'周六','Sun'=>'周日','Mon'=>'周一','Tue'=>'周二','Wed'=>'周三');
            $i = 0;
            $nowhour = date('h');
            $nowmin  = date('i');
            if($nowhour>=12&&$nowmin>30){
                $tta = array('今天','明天','后天');
                $ppa = array(0,1,2);
                $now = true;
            } else {
                $tta = array('昨天','今天','明天','后天');
                $ppa = array(0,1,2,3);
                $now = false;
            }
            foreach ($weekinfo as $key => $value) {
                $todayname = in_array($i, $ppa) ? $tta[$i] : $week_cn[$key];
                $week_info = array();
                $week_info = $this->code2char($value[2]-1);
                $newweather[] = array(
                           'name' => $todayname,
                           'week' => $week_cn[$key],
                           'type' => $week_info[0],
                           'high' => $value[0],
                           'low'  => $value[1],
                           'pic'  => $week_info[1],
                           'date' => date("m.d",strtotime("+".($now ? $i : ($i-1))." day"))
                );
                $i++;
            }
        }
        if($now === false){
            unset($newweather[0]);
            $this->assign('today',$newweather[1]);
        } else {
            $this->assign('today',$newweather[0]);
        }
        $this->assign('temp',$temp);
        $this->assign('code',$code);
        $this->assign('humi',$humi);
        $this->assign('wind',$wind);
        $this->assign('weatherlist',$newweather);
        $this->display();
    }

    protected function code2char($code){
        if(!is_numeric($code)||$code>47){
            return array('晴朗','http://b2b.gzl.com.cn/Administrator/UploadFile/Editor/Image/2011/10/20111024142542174.jpg');
        } else {
            $codes = array(
            '龙卷风','热带风暴','暴风','大雷雨','雷阵雨','雨夹雪','雪夹雹','冻雾雨','细雨','冻雨','阵雨','阵雨','阵雪','小阵雪',
            '高吹雪','雪','冰雹','雨淞','粉尘','雾','薄雾','烟雾','大风','大风','风','冷','阴','多云','多云','局部多云',
            '局部多云','晴','晴','转晴','转晴','雨夹冰雹','热','局部雷雨','偶有雷雨','偶有雷雨','偶有阵雨','大雪','零星阵雪',
            '大雪','局部多云','雷阵雨','阵雪','局部雷阵雨'
            );
            return array($codes[$code],'weather/'.$code.'.png');
        }
    }
    //景点介绍
    public function mapindex(){
        $this->display();
    }
    //插件Tips返回处理 return 数字
    public function run(){
        return '';
    }
    //插件首页用户后台
    public function profile(){
        self::setHeader(true);
        $this->display();
    }
}

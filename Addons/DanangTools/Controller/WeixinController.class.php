<?php

namespace Addons\DanangTools\Controller;
use Common\Controller\Bundle;

/**
 * DanangTools微信处理Bundle
 */
class WeixinController extends Bundle{
    public $_rules = array(
        'COMMON'    => array(
                            '_action'  => null,
                            '_wtype'   => 'content',
                            '/^天气/' => array(
                                                '_action'  => 'show_weather',
                                                '_replace' => array(self::W_COMMON,'',array(0,2))
                                            ),
                            '/^(景点|地图)/' => array(
                                                '_action'  => 'show_places',
                                                '_replace' => array(self::W_COMMON,'',array(0,2))
                                            ),
                            '/^(会员|积分)/' => array(
                                                '_action'  => 'show_vip',
                                                '_replace' => array(self::W_COMMON,'',array(0,2))
                                            ),
                            ),
        'CLICK'    => array(
                            '_action'  => null,
                            '_wtype'   => 'content',
                            '/^天气/' => array(
                                                '_action'  => 'show_weather',
                                                '_replace' => array(self::W_COMMON,'',array(0,2))
                                            ),
                            '/^(景点|地图)/' => array(
                                                '_action'  => 'show_places',
                                                '_replace' => array(self::W_COMMON,'',array(0,2))
                                            ),
                            '/^(会员|积分)/' => array(
                                                '_action'  => 'show_vip',
                                                '_replace' => array(self::W_COMMON,'',array(0,2))
                                            ),
                            )
    );
    //插件微信处理默认入口
	public function index(){
        wx_success('Hello World!这是岘港定制插件的微信Bundle！');
	}
    //实现的微信天气
    public function show_weather(){
        $citycode = '1252376';
        $temptype = 'c';
        $url = 'http://xml.weather.yahoo.com/forecastrss?w='.$citycode.'&u='.$temptype;
        //$xml = file_get_contents($url);
        $reader = new \XMLReader();
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
                $wind = $reader->getAttribute('speed');  //获取湿度
            }
            if($reader->name == 'yweather:forecast'){
                $weekinfo[$reader->getAttribute('day')] = array($reader->getAttribute('low'),$reader->getAttribute('high'),$reader->getAttribute('code'));
            }
        }
        $reader->close();
        $weatherinfo    = $this->code2char($code);
        //".$wind."Km/h  
        $article[0] = array(
          'Title'       => "[今天] 岘港  ".$weatherinfo[0]."  ".$temp."℃",
          'Description' => "[今天]  白天:  夜间:\n[明天]  白天: 夜间:",
          'PicUrl'      => 'http://b2b.gzl.com.cn/Administrator/UploadFile/Editor/Image/2011/10/20111024142542174.jpg',
          'Url'         => $this->create_loginurl('show_weather'),
        );

        if(!empty($weekinfo)){
            $week_cn = array('Thu'=>'星期四','Fri'=>'星期五','Sat'=>'星期六','Sun'=>'星期日','Mon'=>'星期一','Tue'=>'星期二','Wed'=>'星期三');
            $i = 1;
            foreach ($weekinfo as $key => $value) {
                $week_info = array();
                $week_info = $this->code2char($value[2]-1);
                $article[$i++] = array(
                  'Title'       => $week_cn[$key]."  ".$week_info[0]."\n最高:".$value[1]."℃  最低:".$value[0]."℃",
                  'Description' => "岘港天气",
                  'PicUrl'      => $week_info[1],
                  'Url'         => $this->create_loginurl('show_weather'),
                );
            }
        }

        $this->news($article);
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
            return array($codes[$code],ADDON_PUBLIC.'weather/'.$code.'.png');
        }
    }
    //实现的微信地图
    public function show_places(){
        $this->success('查询地图工具');
    }
    //实现的微信会员
    public function show_vip(){
        global $_P;
        $article[0] = array(
          'Title'       => "亲爱的 ".$_P['nickname'],
          'Description' => "[当前积分]  0分\n点击进入会员中心>>",
          'PicUrl'      => $weatherinfo[1],
          'Url'         => $this->create_loginurl('index'),
        );
        $this->news($article);
    }
    public function run(){
        return true;
    }
    //插件展示微信TAG方法
    public function showTips(){
        return '';
    }
}

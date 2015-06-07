<?php
namespace Common\Model;
use Think\Model;
/**
 * 微信支付
 * 使用方法 Wxpay::方法($userid,额外参数,记录语句);
 */
class WxpayModel extends Model{
    public $CONFIG           = '';
    public $curl_timeout     = 30;

    //获取微信支付配置
    public function get_config(){
        //获取微信支付参数
        $configlist  = M('wxpay_config')->select();
        $wxpayconfig = array();
        foreach ($configlist as $key => $value) {
            $wxpayconfig[$value['key']] = $value['value'];
        }
        return $this->CONFIG = $wxpayconfig;
    }

    /**
     * 获取 微信预支付ID
     * @param  string $openid    支付用户的Openid
     * @param  array  $orderinfo 订单详情
     * @param  string $type      支付类型  默认JSAPI
     * @return array             预支付详细参数
     *
     *     ["return_code"] => string(7) "SUCCESS"
     *     ["return_msg"]  => string(2) "OK"
     *     ["appid"]       => string(18) "111111"
     *     ["mch_id"]      => string(10) "22221111"
     *     ["nonce_str"]   => string(16) "Wfq09PEV8YjTc3bR"
     *     ["sign"]        => string(32) "FE0CA5D5B46AD3F75CB27A7F61093D48"
     *     ["result_code"] => string(7) "SUCCESS"
     *     ["prepay_id"]   => string(36) "wx20150203144012e873ed13080732311829"
     *     ["trade_type"]  => string(5) "JSAPI"
     */
    public function get_prepay_id($openid,$orderinfo=array(),$type='JSAPI'){
        //获取基础参数 Mchid appID
        $this->get_config();
        $pay_url    = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $notify_url = 'http://ag.amango.net'.U('Notice/wxpay','','','',true);

        $orderinfo['body']         = empty($orderinfo['body']) ? '该订单支付于:'.date('Y年m月d日H时i分') : $orderinfo['body'];
        $orderinfo['out_trade_no'] = empty($orderinfo['out_trade_no']) ? time() : $orderinfo['out_trade_no'];

        if(empty($orderinfo['total_fee'])){
            return array(false,array('请填写金额'));
        }

        if(empty($openid)){
            return array(false,array('支付用户不明确'));
        }

        //自定义订单号
        $timeStamp  = time();
        $paypackage = array();
        $paypackage["openid"]        = $openid;                    //支付用户OPENID
        $paypackage["body"]          = $orderinfo['body'];         //订单简介
        $paypackage["out_trade_no"]  = $orderinfo['out_trade_no']; //订单号
        $paypackage["total_fee"]     = $orderinfo['total_fee'];    //支付价格
        $paypackage["notify_url"]    = $notify_url;                //通知地址
        $paypackage["trade_type"]    = 'JSAPI';                    //签名
        $paypackage["appid"]         = $this->CONFIG['appId'];     //公众号ID
        $paypackage["mch_id"]        = $this->CONFIG['Mchid'];     //商户号
        $paypackage["nonce_str"]     = $this->createNoncestr();    //随机字符串
        $package['time_start']       = date('YmdHis', $timeStamp);
        $package['time_expire']      = date('YmdHis', $timeStamp + 600);
        $package['spbill_create_ip'] = get_client_ip(0);

        //生成签名 
        $paypackage["sign"]          = $this->getSign($paypackage,$this->CONFIG['Key']);

        $xml      = $this->arrayToXml($paypackage);
        $response = $this->postXmlCurl($xml,$pay_url,30);
        $result   = $this->xmlToArray($response);
        if($result['result_code']=='FAIL'){
            return array(false,$result);
        } else {
            $backstring = '';
            $callback['appId']     = $this->CONFIG['appId'];
            $callback['timeStamp'] = strval($timeStamp);
            $callback['nonceStr']  = $this->createNoncestr();
            $callback['package']   = 'prepay_id='.$result['prepay_id'];
            $callback['signType']  = 'MD5';
            ksort($callback, SORT_STRING);
            foreach($callback as $key => $v) {
                $backstring .= "{$key}={$v}&";
            }
            $backstring .= "key=".$this->CONFIG['Key'];
            $callback['paySign'] = strtoupper(md5($backstring));
            return array(true,$callback);
        }
    }


    //作用：产生随机字符串，不长于32位
    public function createNoncestr( $length = 8 ) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        }  
        return $str;
    }
    //作用：array转xml
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
             if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">"; 
             } else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">"; 
             }
        }
        $xml.="</xml>";
        return $xml; 
    }
    //作用：将xml转为array
    public function xmlToArray($xml){       
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);      
        return $array_data;
    }
    //作用：生成签名
    public function getSign($Parameters,$KEY){
        ksort($Parameters,SORT_STRING);
        $String = '';
        foreach($Parameters as $key => $v) {
            $String .= "{$key}={$v}&";
        }
        $String .= "key=".$KEY;
        $result_ = strtoupper(md5($String));
        return $result_;
    }
    //作用：以post方式提交xml到对应的接口url
    public function postXmlCurl($xml,$url,$second=30){       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        curl_close($ch);
        if($data){
            return $data;
        } else { 
            return false;
        }
    }
}
?>

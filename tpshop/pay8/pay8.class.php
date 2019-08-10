<?php

//namespace plugins\payment\pay8;
 
use think\Model; 
use think\Request;
use app\admin\logic\RefundLogic;

/**
 * 支付 逻辑定义
 * Class Pay8
 * @package Home\Payment
 */

class pay8 extends Model
{    
    public $tableName = 'plugin'; // 插件表        
    public $pay8_config = array();// 支付宝支付配置参数
    
    /**
     * 析构流函数
     */
    public function  __construct() {           
        parent::__construct();     
        unset($_GET['pay_code']);   // 删除掉 以免被进入签名
        unset($_REQUEST['pay_code']);// 删除掉 以免被进入签名
        
        $paymentPlugin = M('Plugin')->where("code='pay8' and  type = 'payment' ")->find(); // 找到支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化        
        $this->pay8_config['merchant_id'] = $config_value['merchant_id']; //商户id
        $this->pay8_config['apikey'] = $config_value['apikey'];//apikey
        $this->pay8_config['signkey'] = $config_value['signkey'];//apikey
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function get_code($order, $config_value)
    {
        $html = '<form name="pay8sumbit" action="https://cashier.pay8.org/?l=zh-cn" method="post" style="padding:10px;">';
        $body = $config_value['body'];
        !$body && $body = "TPshop商品" ;
        $params = [
            'total_amount' => $order["order_amount"], //付款金额
            'out_trade_no' => $order["order_sn"], //商户订单号
            'body' => urlencode($body),
            'redirect_url' => urlencode(SITE_URL.U('Payment/returnUrl',array('pay_code'=>'pay8'))."?out_trade_no={$order["order_sn"]}"), //页面跳转同步通知页面路径
            'notify_url' => urlencode(SITE_URL.U('Payment/notifyUrl',array('pay_code'=>'pay8'))), //服务器异步通知页面路径 //必填，不能修改
            'member_id' => $this->pay8_config['merchant_id'],
            'apikey' => $this->pay8_config['apikey'],
        ];
        foreach ($params as $k => $v){
            if ($k == "coin" || $k == "issuer") {
                continue;
            }
            $html .= '<input type="hidden" name="'.$k.'" value="'.$v.'"/>';
        }
        $html .= '<!--<input type="submit">--></form></form><script>document.forms["pay8sumbit"].submit();</script>';

        return $html;
    }

    /**
     * 签名算法
     */
    function getSign($params, $signkey){
        ksort($params);
        $str = "";
        foreach ($params as $key => $value) {
            if(strtoupper($key) == 'SIGN' || strtoupper($key) == '__HASH__' || $value === '') continue;
            $str .= $key. "=" . $value . "&";
        }
        $str .= "stringSignTemp=" . $signkey;
        $sign = md5(strtoupper($str));
        return $sign;
    }

    /**
     * 服务器点对点响应操作给支付接口方调用
     *
     */
    function response()
    {
        if(!empty($_POST)){
            $param = $_POST;
            $sihnkey = $this->pay8_config['signkey'];
            $sign = $this->getSign($param, $sihnkey);
            if($sign == $param['sign']) //验证成功
            {
                $order_sn = $out_trade_no = $_POST['out_trade_no']; //商户订单号
                $trade_no = $_POST['trade_no']; //pay8交易号

                //判断订单金额和用户实际支付金额是否一样
                $order_amount = M('order')->where(['order_sn'=>"$order_sn"])->value('order_amount');
                if ($order_amount == $_POST['received_amount']){
                    update_pay_status($order_sn,array('transaction_id'=>$trade_no)); // 修改订单支付状态
                    echo "success";
                } else {
                    echo "fail"; //验证失败
                }
            } else {
                echo "fail"; //验证失败
            }
        }else{
            echo "fail"; //验证失败
        }

    }
    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function respond2()
    {
        if(!empty($_GET)){
            $order_sn = $out_trade_no = $_GET['out_trade_no']; //商户订单号
            $order_status = M('order')->where(['order_sn'=>"$order_sn"])->value('order_status');
            $pay_status = M('order')->where(['order_sn'=>"$order_sn"])->value('pay_status');

            if($order_status == 0 && $pay_status == 1)
            {
                return array('status'=>1,'order_sn'=>$order_sn);//跳转至成功页面
            }
            else {
                return array('status'=>0,'order_sn'=>$order_sn); //跳转至失败页面
            }
        }else{
            return array('status'=>0,'order_sn'=>$_GET['out_trade_no']);//跳转至失败页面
        }
    }
}
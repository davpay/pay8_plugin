<?php

class pay8 {

    private $config;

    public function __construct($payment_info = array(), $order_info = array()) {
        if (!empty($payment_info)) {
            $this->config = array(
                //商户ID
                'merchant_id' => $payment_info['payment_config']['pay8_merchant_id'],
                //apikey
                'apikey' => $payment_info['payment_config']['pay8_apikey'],
                //signkey
                'signkey' => $payment_info['payment_config']['pay8_signkey'],
                //异步通知地址
                'notify_url' => str_replace('/index.php', '', HOME_SITE_URL) . '/payment/notify.html', //通知URL,
                //同步跳转
                'redirect_url' => str_replace('/index.php', '', HOME_SITE_URL) . "/payment/return_verify.html", //返回URL,
                //编码格式
                'charset' => "UTF-8",
            );
        }
    }

    /**
     * 获取支付接口的请求地址
     *
     * @return string
     */
    public function get_payform($order_info) {
        $html = '<form name="pay8sumbit" action="https://cashier.pay8.org/?l=zh-cn" method="post">';
        $body = "dsmall商品" ;
        $params = [
            'total_amount' => $order_info["api_pay_amount"], //付款金额
            'out_trade_no' => $order_info["order_list"][0]['order_sn'], //商户订单号
            'body' => urlencode($body),
            'redirect_url' => urlencode($this->config['redirect_url'])."?payment_code=pay8&order_sn={$order_info["order_list"][0]['order_sn']}",
            'notify_url' => urlencode($this->config['notify_url'])."?payment_code=pay8",
            'member_id' => $this->config["merchant_id"],
            'apikey' => $this->config["apikey"],
        ];
        foreach ($params as $k => $v){
            $html .= '<input type="hidden" name="'.$k.'" value="'.$v.'"/>';
        }
        $html .= '<!--<input type="submit">--></form></form><script>document.forms["pay8sumbit"].submit();</script>';
        echo $html;
        exit;
    }

    public function return_verify() {
        $arr = $_GET;
        $return_result = array(
            'trade_status' => '0',
        );

        if (is_numeric($arr['order_sn'])){
            //获取订单信息
            $order_info = db("order")->where('order_sn',$arr['order_sn'])->find();
            if ($order_info && $order_info['order_state'] == 20){
                $return_result = array(
                    'out_trade_no' => $order_info['pay_sn'], #支付订单号
                    'total_fee' => $order_info['order_amount'],
                    'order_type' => 'real_order',//实物
                    'trade_status' => '1',
                );
            }
        }
        
        return $return_result;
    }

    public function verify_notify() {
        $arr = $_POST;
        $notify_result = array(
            'trade_status' => '0',
        );
        $sign = $this->getSign($arr, $this->config['signkey']);
        if ($arr['sign'] == $sign){
            //商户订单处理逻辑

            $order_sn = $_POST['out_trade_no'];//商户订单号
            $log_id = substr($order_sn,strpos($order_sn,"-")+1);

            //获取订单信息
            $order_info = db("order")->where('order_sn',$arr['out_trade_no'])->find();
            //判断订单金额和用户实际支付的金额是否一样
            if ($order_info['order_amount'] == $arr['received_amount']){
                $notify_result = array(
                    'out_trade_no' => $order_info['pay_sn'], #交易号
                    'trade_no' => $arr['trade_no'], #交易凭据单号
                    'total_fee' => $arr['received_amount'], #涉及金额
                    'order_type' => 'real_order',
                    'trade_status' => '1',
                );
            }
        }

        return $notify_result;
    }

    protected function getSign($params, $signkey){
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

}

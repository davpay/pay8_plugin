<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/pay8.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'pay8_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'https://www.pay8.org/';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'pay8_merchant_id',           'type' => 'text',   'value' => ''),
        array('name' => 'pay8_apikey',               'type' => 'text',   'value' => ''),
        array('name' => 'pay8_signkey',               'type' => 'text',   'value' => ''),
    );

    return;
}

/**
 * 类
 */
class pay8
{
    function __construct()
    {
        $this->pay8();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function pay8()
    {
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        $html = '<form target="_blank" action="https://cashier.pay8.org/?l=zh-cn" method="post" style="padding:10px;">';
        $params = [
            'total_amount' => $order["order_amount"],
            'out_trade_no' => $order["order_sn"] ."-" . $order['log_id'],//商户订单号-支付日志id
            'body' => urlencode($order["goods_name"]),
            'redirect_url' => urlencode(return_url(basename(__FILE__, '.php'))."?code=pay8&out_trade_no={$order['order_sn']}-{$order['log_id']}"),
            'notify_url' => urlencode(return_url(basename(__FILE__, '.php'))),
            'member_id' => $payment["pay8_merchant_id"],
            'apikey' => $payment["pay8_apikey"],
        ];
        foreach ($params as $k => $v){
            if ($k == "coin" || $k == "issuer") {
                continue;
            }
            $html .= '<input type="hidden" name="'.$k.'" value="'.$v.'"/>';
        }
        $html .= '<p style="width: 100%;height: 30px;font-size: 30px;line-height: 30px;"><button type="submit" style="margin: auto;">'.$GLOBALS['_LANG']['pay_button'].'</button></p></form>';

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
     * 响应操作
     */
    function respond()
    {
        if(!empty($_POST)){
            $param = $_POST;
            $payment = get_payment('pay8');//获取支付配置信息
            $sign = $this->getSign($param, $payment['pay8_signkey']);
            if($param['sign']==$sign){
                //商户订单处理逻辑

                $order_sn = $_POST['out_trade_no'];//商户订单号
                $log_id = substr($order_sn,strpos($order_sn,"-")+1);

                //判断订单金额和用户实际支付的金额是否一样
                if (!check_money($log_id, $_POST['received_amount'])){
                    //签名失败返回失败通知
                    return false;
                }

                /* 改变订单状态 */
                order_paid($log_id, 2);
                return true;

            }else{
                //签名失败返回失败通知
                return false;
            }
        }else{
            //判断订单情况
            $out_trade_no = $_GET['out_trade_no'];
            $order_arr = explode("-",$out_trade_no);
            $order_sn = $order_arr[0];
            $log_id = $order_arr[1];
            if (is_numeric($order_sn) && is_numeric($log_id)){
                $is_paid = $GLOBALS['db']->getOne("SELECT is_paid FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE log_id = {$log_id}");
                $order_status = $GLOBALS['db']->getOne("SELECT order_status FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = {$order_sn}");
                $pay_status = $GLOBALS['db']->getOne("SELECT pay_status FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = {$order_sn}");
                if ($is_paid == 1 && $order_status == 1 && $pay_status == 2){
                    return true;
                }
            }
            return false;
        }
    }
}

?>

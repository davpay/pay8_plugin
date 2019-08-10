<?php

return array(
    'payment_code' => 'pay8',
    'payment_name' => '数字货币支付',
    'payment_desc' => '数字货币支付接口',
    'payment_is_online' => '1',
    'payment_platform' => 'pc', #支付平台 pc h5 app
    'payment_author' => 'SBC',
    'payment_website' => 'http://www.pay8.org',
    'payment_version' => '1.0',
    'payment_config' => array(
        array('name' => 'pay8_merchant_id', 'type' => 'text', 'value' => '', 'desc' => '描述'),
        array('name' => 'pay8_apikey', 'type' => 'text', 'value' => '', 'desc' => '描述'),
        array('name' => 'pay8_signkey', 'type' => 'text', 'value' => '', 'desc' => '描述'),
    ),
);
?>

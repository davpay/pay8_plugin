<?php

return array(
    'code'=> 'pay8',
    'name' => '数字货币支付',
    'version' => '1.0',
    'author' => 'sbc',
    'desc' => 'pay8聚合支付插件',
    'icon' => 'logo.jpg',
    'scene' => 0,  // 使用场景 0 PC+手机 1 手机 2 PC ,3 APP
    'config' => array(
        array('name' => 'merchant_id','label'=>'商户ID','type' => 'text',   'value' => '','hint'=>''),
        array('name' => 'apikey',   'label'=>'APIKEY', 'type' => 'text',   'value' => ''),
        array('name' => 'signkey',   'label'=>'SIGNKEY', 'type' => 'text',   'value' => ''),
    ),
);
<?php

return [
    [
        'name' => 'key',
        'title' => '应用key',
        'type' => 'string',
        'content' => [],
        'value' => 'LTAI5tQf49wfnCcQrw3YMtd1',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'secret',
        'title' => '密钥secret',
        'type' => 'string',
        'content' => [],
        'value' => 'ISItlwQroie6lX4i2jA3m2ZSJSmRQm',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'sign',
        'title' => '签名',
        'type' => 'string',
        'content' => [],
        'value' => '点小泉科技',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'template',
        'title' => '短信模板',
        'type' => 'array',
        'content' => [],
        'value' => [
            'register' => 'SMS_229610462',
            'changepwd' => 'SMS_229645326 ',
            'login' => 'SMS_228980079',
            'resetpwd' => 'SMS_229645326 ',
        ],
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => '__tips__',
        'title' => '温馨提示',
        'type' => 'string',
        'content' => [],
        'value' => '应用key和密钥你可以通过 https://ak-console.aliyun.com/?spm=a2c4g.11186623.2.13.fd315777PX3tjy#/accesskey 获取',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
];

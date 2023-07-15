<?php

return [
    [
        'name' => 'maptype',
        'title' => '默认地图类型',
        'type' => 'radio',
        'content' => [
            'baidu' => '百度地图',
            'amap' => '高德地图',
            'tencent' => '腾讯地图',
        ],
        'value' => 'tencent',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'location',
        'title' => '默认检索城市',
        'type' => 'string',
        'content' => [],
        'value' => '南宁',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'zoom',
        'title' => '默认缩放级别',
        'type' => 'string',
        'content' => [],
        'value' => '15',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'lat',
        'title' => '默认Lat',
        'type' => 'string',
        'content' => [],
        'value' => '22.81762',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'lng',
        'title' => '默认Lng',
        'type' => 'string',
        'content' => [],
        'value' => '108.36635',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'baidukey',
        'title' => '百度地图KEY',
        'type' => 'string',
        'content' => [],
        'value' => 'quidvmWpnPgkk2VK6HAZjCjEVfsQxUC2',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'amapkey',
        'title' => '高德地图KEY',
        'type' => 'string',
        'content' => [],
        'value' => '985f36d969b3ba74a1712b08b07840a4',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => 'tencentkey',
        'title' => '腾讯地图KEY',
        'type' => 'string',
        'content' => [],
        'value' => 'SEFBZ-F65KI-EEQGT-56MJ5-BHHMZ-WZFNZ',
        'rule' => 'required',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => '',
    ],
    [
        'name' => '__tips__',
        'title' => '温馨提示',
        'type' => '',
        'content' => [],
        'value' => '请先申请对应地图的Key，配置后再使用',
        'rule' => '',
        'msg' => '',
        'tip' => '',
        'ok' => '',
        'extend' => 'alert-danger-light',
    ],
];
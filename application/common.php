<?php

// 公共助手函数

use app\common\exception\ApiMsgSend;
use Symfony\Component\VarExporter\VarExporter;
use think\Db;
use think\exception\HttpResponseException;
use think\Response;

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @param int    $precision 小数位数
     * @return string
     */
    function format_bytes($size, $delimiter = '', $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $cdnurl = \think\Config::get('upload.cdnurl');
        $url = preg_match($regex, $url) || ($cdnurl && stripos($url, $cdnurl) === 0) ? $url : $cdnurl . $url;
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = isset($ids[$v['field']]) ? $model->where($primary, 'in', $ids[$v['field']])->column("{$primary},{$v['column']}") : [];
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $v[$fieldsArr[$n]['display']] = implode(',', array_intersect_key($result[$n], $curr));
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function var_export_short($data, $return = true)
    {
        return var_export($data, $return);
        $replaced = [];
        $count = 0;

        //判断是否是对象
        if (is_resource($data) || is_object($data)) {
            return var_export($data, $return);
        }

        //判断是否有特殊的键名
        $specialKey = false;
        array_walk_recursive($data, function (&$value, &$key) use (&$specialKey) {
            if (is_string($key) && (stripos($key, "\n") !== false || stripos($key, "array (") !== false)) {
                $specialKey = true;
            }
        });
        if ($specialKey) {
            return var_export($data, $return);
        }
        array_walk_recursive($data, function (&$value, &$key) use (&$replaced, &$count, &$stringcheck) {
            if (is_object($value) || is_resource($value)) {
                $replaced[$count] = var_export($value, true);
                $value = "##<{$count}>##";
            } else {
                if (is_string($value) && (stripos($value, "\n") !== false || stripos($value, "array (") !== false)) {
                    $index = array_search($value, $replaced);
                    if ($index === false) {
                        $replaced[$count] = var_export($value, true);
                        $value = "##<{$count}>##";
                    } else {
                        $value = "##<{$index}>##";
                    }
                }
            }
            $count++;
        });

        $dump = var_export($data, true);

        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump); // Starts
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump); // Ends
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump); // Empties
        $dump = preg_replace('#\)$#', "]", $dump); //End

        if ($replaced) {
            $dump = preg_replace_callback("/'##<(\d+)>##'/", function ($matches) use ($replaced) {
                return isset($replaced[$matches[1]]) ? $replaced[$matches[1]] : "''";
            }, $dump);
        }

        if ($return === true) {
            return $dump;
        } else {
            echo $dump;
        }
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                $response = Response::create('跨域检测无效', 'html', 403);
                throw new HttpResponseException($response);
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                $response = Response::create('', 'html');
                throw new HttpResponseException($response);
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('check_ip_allowed')) {
    /**
     * 检测IP是否允许
     * @param string $ip IP地址
     */
    function check_ip_allowed($ip = null)
    {
        $ip = is_null($ip) ? request()->ip() : $ip;
        $forbiddenipArr = config('site.forbiddenip');
        $forbiddenipArr = !$forbiddenipArr ? [] : $forbiddenipArr;
        $forbiddenipArr = is_array($forbiddenipArr) ? $forbiddenipArr : array_filter(explode("\n", str_replace("\r\n", "\n", $forbiddenipArr)));
        if ($forbiddenipArr && \Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $forbiddenipArr)) {
            $response = Response::create('请求无权访问', 'html', 403);
            throw new HttpResponseException($response);
        }
    }
}
if (!function_exists('send_request')) {
    function send_request($url, $data=null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if(!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
if (!function_exists('get_access_token')) {
    function get_access_token()
    {
        $accessToken = cache('access_token');
        if(!$accessToken){
            $appId = config('site.wx_app_id');
            $appSecret = config('site.wx_app_secret');
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
            $res = json_decode(send_request($url), true);
            if(isset($res['errcode'])){
                return false;
            }
            cache('access_token',$res['access_token'],6000);
            $accessToken = $res['access_token'];
        }
        return $accessToken;
    }
}
if (!function_exists('get_wx_user_info')) {
    function get_wx_user_info($openid) {
        $accessToken = get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$accessToken.'&openid='.$openid.'&lang=zh_CN';
        $res = json_decode(send_request($url), true);
        if(isset($res['errcode'])){
            return false;
        }
        return $res;
    }
}
/**
 * API 接口 报错 强制终止
 * @param string $message
 * @param int $code
 * @throws ApiMsgSend
 */
if (!function_exists('throw_api_error')) {
    function throw_api_error($message = 'error',$status = 400, $data = null){
        $result = [
            'data' => $data,
            'message' => $message,
            'status' => $status
        ];
        throw new ApiMsgSend($result);
    }
}

// $ext_param中可以放json_encode中所需的第二个参数，例如$ext_param = ['json_encode_param' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT ]
if (!function_exists('commonReturnSuccess')) {
    function commonReturnSuccess($data = [], $message = 'success', $status = 200, $ext_param = []) {
        $result = [
            'data' => $data,
            'message' => $message,
            'status' => $status
        ];
        return json($result, 200, [], $ext_param);
    }
}

// 以后返回json格式统一使用该方案
if (!function_exists('commonReturnError')) {
    function commonReturnError($message = 'error', $status = 400, $data = null, $ext_param = []) {
        $result = [
            'data' => $data,
            'message' => $message,
            'status' => $status
        ];
        return json($result, 200, [], $ext_param);
    }
}
//生成token
if (!function_exists('get_new_token')) {
    function get_new_token()
    {
        return md5(time().rand(100000,999999));
    }
}

function aesDecode($message) {
    $key = base64_decode(config('msg_aes_key').'=');
    $iv = substr($key, 0, 16);
    $decrypted = openssl_decrypt(base64_decode($message), 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);
    try{
        $result = decode($decrypted);
        if(strlen($result) < 16)
            return "";
        $content = substr($result, 16, strlen($result));
        $len_list = unpack("N", substr($content, 0, 4));
        $xml_len = $len_list[1];
        $xml_content = substr($content, 4, $xml_len);
        $from_appid = substr($content, $xml_len + 4);
    }catch(Exception $e){
        return false;
    }
    if($from_appid != config('site.wx_app_id')){
        return false;
    }
    return $xml_content;
}
function isimplexmlLoadString($string, $class_name = 'SimpleXMLElement', $options = 0, $ns = '', $is_prefix = false) {
    libxml_disable_entity_loader(true);
    if(preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $string)){
        return false;
    }
    return simplexml_load_string($string, $class_name, $options, $ns, $is_prefix);
}

function get_order_sn()
{
    $orderSn = date("YmdHis").rand(100000000,999999999);
    return $orderSn;
}
/**
 * 获取微信access_token
 */
if (!function_exists('get_wx_access_token')) {
    function get_wx_access_token()
    {
        $accessToken = cache('access_token');
        if(!$accessToken){
            $appId = config('site.wx_app_id');
            $appSecret = config('site.wx_app_secret');
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
            $res = json_decode(send_request($url), true);
            if(!isset($res['access_token'])){
                return false;
            }
            cache('access_token',$res['access_token'],6000);
            $accessToken = $res['access_token'];
        }
        return $accessToken;
    }
}
function send_msg_to_station($createtime,$stationId,$orderSn,$addressId,$arriveTime)
{
    $url = 'pages/serveBill/serveBill';
    $stationInfo = Db::name('index_users')->field('open_id,b_address')->where('id',$stationId)->find();
    $openId = $stationInfo['open_id'];
    $endAddress = Db::name('user_address')->where('id',$addressId)->value('final_address');
    //01.下单成功，提示接单 02.开始配送，提醒客户
    $template_id = "x_R9Df5CilALqllTwTWStw6Dx3sMi00Sit8ZhJlUBDQ";
    $data = [
        "touser"=>$openId,
        "template_id"=>$template_id,
        "page"=>$url,
        "data"=>[
            "character_string4"=> [
                "value"=>$orderSn
            ],
            "date6"=>[
                "value"=>$createtime
            ],
            "thing10"=>[
                "value"=>mb_substr($endAddress,0,9).'....'
            ],
            "time15"=> [
                "value"=>$arriveTime
            ]
        ]
    ];
    $str = json_encode($data);
    $access_token = get_wx_access_token();
    _request('https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$access_token,true,'post', $str);
}
function _request($curl,$https=true,$method='get',$data=null)
{
    $ch=curl_init(); //初始化
    curl_setopt($ch,CURLOPT_URL,$curl);
    curl_setopt($ch,CURLOPT_HEADER,false);//设置不需要头信息
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);//获取页面内容，但不输出
    if($https)
    {
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);//不做服务器认证
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);//不做客户端认证
    }
    if($method=='post')
    {
        curl_setopt($ch, CURLOPT_POST,true);//设置请求是post方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置post请求数据
    }
    $str=curl_exec($ch);//执行访问
    curl_close($ch);//关闭curl，释放资源
    return $str;
}

/**
* 发送配送模版消息
*/
function send_msg_to_user($orderId)
{
    $orderInfo = Db::name('goods_order')->field('c_user_id,worker_id')->where('id',$orderId)->find();
    $openId = Db::name('index_users')->where('id',$orderInfo['c_user_id'])->value('open_id');
    $url = 'pages/bill/bill';
    $workerId = $orderInfo['worker_id'];
    $workerInfo = Db::name("water_workers")->where('id',$workerId)->find();
    //01.下单成功，提示接单 02.开始配送，提醒客户
    $templateId = "H--UltGCl1bXrBKbnAstblkzNX8lBPnUuopoWFrayaU";
    $data = [
        "touser"=>$openId,
        "template_id"=>$templateId,
        "page"=>$url,
        "data"=>[
            "name4"=>[
                "value"=>$workerInfo['worker_name']
            ],
            "phone_number10"=>[
                "value"=>$workerInfo['worker_mobile']
            ],
            "thing11"=>[
                "value"=>"配送小哥正快马加鞭赶过来"
            ]
        ]
    ];
    $str = json_encode($data);
    $access_token = get_wx_access_token(); 
    send_request('https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$access_token, $str);
    
}
function get_user_phone($code)
{
    $accessToken = get_wx_access_token();
    $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=$accessToken";
    $data = [
        'code' => $code
    ];
    $resJson = send_request($url,json_encode($data));
    $res = json_decode($resJson,true);
    if($res['errcode'] != 0){
        return false;
    }
    return $res['phone_info']['phoneNumber'];

}
/**
 * 判断是否首单，赠送无门槛优惠券
 */
function check_is_first($userId,$sumMoney)
{
    //判断用户是否来自分享
    $shareRecord = Db::name('user_subscribe')->where('user_id',$userId)->find();
    if($shareRecord){
        //水票订单
        $where = [
            'c_user_id' => $userId,
            'is_pay' => 1
        ];
        $ticketOrderRecord = Db::name('ticket_order')->where($where)->select();
        //水订单
        $goodsOrderRecord = Db::name('goods_order')->where($where)->select();
        //首次下单
        if((count($ticketOrderRecord)==1 && count($goodsOrderRecord)==0) || count($ticketOrderRecord)==0 && count($goodsOrderRecord)==1){
            $parentUserId = $shareRecord['parent_user_id'];
            $shareProportion = floatval(config('site.share_proportion'));
            $givingMoney = round($sumMoney*$shareProportion/100,2);
            $shareMinThreshold = round(floatval(config('site.share_min_threshold')),2);
            $effective = intval(config('site.giving_coupons_effective'));
            $nowTime = time();
            $endTime = $nowTime+$effective*24*60*60;
            $endDate = date("Y-m-d",$endTime);
            $data = [
                'c_user_id' => $parentUserId,
                'coupons_value' => $givingMoney,
                'createtime' => date("Y-m-d H:i:s"),
                'threshold_price' => $shareMinThreshold,
                'end_time' => $endDate
            ];
            Db::name('user_coupons')->strict(false)->insert($data);
        }
    }
}
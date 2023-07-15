<?php

namespace addons\third\library;

use fast\Http;
use think\Cache;
use think\Config;
use think\Exception;
use GuzzleHttp\Client;
/**
 * 微信
 */
class Weapp
{
    const GET_ACCESS_TOKEN = "https://api.weixin.qq.com/cgi-bin/token";
    const GET_USERINFO_URL = "https://api.weixin.qq.com/sns/jscode2session";
    const GET_UNLIMITED_URL = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit';
    const GET_CREATQRCODE_URL = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode';
    
    
    const GET_SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send';
    
    

    /**
     * 配置信息
     * @var array
     */
    private $config = [];

    public function __construct($options = [])
    {
        if ($config = Config::get('third.weapp')) {
            $this->config = array_merge($this->config, $config);
        }
        $this->config = array_merge($this->config, is_array($options) ? $options : []);
    }

    /**
     * 获取用户信息
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getUserInfo($params = [])
    {
        $session = $this->code2Session($params['code']);
        $userInfo = $this->decryptData($session['session_key'], $params['encryptedData'], $params['iv']);

//        print_r($session);
//        print_r($userInfo);exit;
        return [
            'access_token'  => $session['session_key'],
            'refresh_token' => '',
            'expires_in'    => 7200,
            'openid'        => isset($userInfo['openId'])?$userInfo['openId']:$session['openid'],
            'unionid'       => isset($userInfo['unionId'])?$userInfo['unionId']:(isset($session['unionid'])?$session['unionid']: ''),
            'userinfo'      => [
                'nickname' => $userInfo['nickName'],
                'gender' => $userInfo['gender'],
                'avatar' => isset($userInfo['avatarUrl']) ? $userInfo['avatarUrl'] : '',
            ]
        ];
    }

    /**
     * 获取access_token
     * @param string code
     * @return array
     * @throws Exception
     */
    public function code2Session($code = '')
    {
        if (!$code) {
            throw new Exception('授权CODE不能为空，请重新登陆');
        }
        $queryarr = array(
            "appid"      => $this->config['app_id'],
            "secret"     => $this->config['app_secret'],
            "js_code"       => $code,
            "grant_type" => "authorization_code",
        );
       
        
         $response = Http::get(self::GET_USERINFO_URL, $queryarr);
        
        
        
        $ret = json_decode($response, true);
        
        if (isset($ret['errcode']) && $ret['errcode'] != 0) {
            throw new Exception($ret['errmsg']);
        }
        
        return $ret;
    }

    /**
     * 获取无线数量的二维码
     * @param string $scene
     * @param int $width
     * @param string $page
     */
    public function getUnlimited($scene, $width = 430, $page = 'pages/index/index')
    {
        $accessToken = $this->getAccessToken();
        $data = json_encode([
            'scene' => $scene,
            'width' => $width,
        ]);

        $response = Http::post(self::GET_UNLIMITED_URL . '?access_token=' . $accessToken, $data, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($data)
            ],
        ]);
        $result = (array)json_decode($response, true);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            throw new Exception( $result['errmsg']);
        }
        
        return $response;
    }
    
    
    
    /**
     * 发送消息
     * @param string $scene
     * @param int $width
     * @param string $page
     */
    public function send($openid='',$templateId, $msg='备注')
    {
        $accessToken = $this->getAccessToken();
       

        $data = array(
            'date4' => array('value' => date('Y-m-d H:i:s', time()) ),
            'thing26' => array('value' => $msg),
        
        );
        $params1 = array(
            "touser" => $openid,
            "template_id" => $templateId,
            "data" => $data,
        );
        
        $response = Http::post(self::GET_SEND_URL . '?access_token=' . $accessToken, json_encode($params1));
        
        $result = (array)json_decode($response, true);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            $result['touser']=$openid;
            $result['template_id']=$templateId;
            addlog($result);
        }
        return $response;
        
       
    }
    
    
    
    
    
    
    

    /**
     * 获取服务端授权码
     * @return mixed
     */
    public function getAccessToken()
    {
        $queryArr = array(
                "appid"      => $this->config['app_id'],
                "secret"     => $this->config['app_secret'],
                "grant_type" => "client_credential",
            );
            $response = Http::get(self::GET_ACCESS_TOKEN, $queryArr);
            $result = (array)json_decode($response, true);
            if (empty($result)) {
                throw new Exception('服务端ACCESS_TOKEN授权失败');
            }
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                throw new Exception('服务端ACCESS_TOKEN授权失败，' . $result['errmsg']);
            }
            return $result['access_token'];
    }

    /**
     * 解密用户数据
     * @param string $sessionKey
     * @param string $encryptedData
     * @param string $iv
     * @return array
     * @throws Exception
     */
    public function decryptData($sessionKey, $encryptedData, $iv)
    {
        if (strlen($sessionKey) != 24) {
            throw new Exception('授权KEY长度错误，请重新登陆');
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            throw new Exception('授权IV长度错误，请重新登陆');
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            throw new Exception('授权信息解密失败，请重新登陆');
        }

        $data = json_decode($result, true);
        if ($data['watermark']['appid'] != $this->config['app_id']) {
            throw new Exception('授权信息不一致，请重新登陆');
        }
        return $data;
    }
}

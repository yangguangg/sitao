<?php
namespace app\user\controller;

use app\common\controller\Publics;
use app\user\action\SmsAction;
use app\user\action\UserAction;
use EasyWeChat\Factory;
use Exception;
use think\Db;

class User extends Publics
{
    private $appId;
    private $appSecret;
    private $userAction;
    public function __construct()
    {
        parent::__construct();
        $this->appId = config('site.wx_app_id');
        $this->appSecret = config('site.wx_app_secret');
        $this->userAction = new UserAction();
    }
    /**
     * 获取code 已添加至接口文档
     * return array
     */
    public function get_code()
    {
        $hostAddress = config('site.host_address');
        $redirectUrl = $hostAddress.'/user/user/get_open_id';
        $appId = $this->appId;
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$redirectUrl&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        $data = ['url'=>$url];
        return commonReturnSuccess($data);
    }
    /**
     * 获取用户open_id 已添加至接口文档 公众号
     * return array
     */
    public function get_open_id()
    {
        $code = input('code');
        $appId = $this->appId;
        $appSecret = $this->appSecret;
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appId&secret=$appSecret&code=$code&grant_type=authorization_code";
        $res = json_decode(send_request($url),true);
        if(isset($res['errmsg'])){
            return commonReturnError('系统错误，请稍后再试');
        }
        $openId = $res['openid'];
        $redirect = "http://haoyah.mx1991.com?open_id=".$openId;
        $this->redirect($redirect, 302);
    }

    public function _request($curl,$https=true,$method='get',$data=null)
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
     * 检测是否完成注册--已添加至接口文档
     */
    public function check_is_registered()
    {
        $openId = input('open_id');
        $res = $this->userAction->check_is_registered($openId);
        return commonReturnSuccess($res);
    }
    /**
     * 获取手机验证码--已添加至接口文档
     */
    public function get_sms_code()
    {
        $mobile = input('mobile');
        $event = input("event");
        $event = $event ? $event : 'register';
        $ip = request()->ip();
        $smsAction = new SmsAction();
        $res = $smsAction->send($mobile,$event,$ip);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    /**
     * 用户端注册--已添加至接口文档
     * return array
     */
    public function register()
    {
        $mobile = input('mobile');
        $openId = input('open_id');
        $userType = 0;
        $event = input("event");
        $event = $event ? $event : 'register';
        $captcha = input("captcha");
        $res = $this->userAction->register($mobile,$openId,$userType,$event,$captcha);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    /**
     * 用户端登录--已添加至接口文档
     */
    public function login()
    {
        $mobile = input('mobile');
        $open_id = input('open_id','');
        $event = input("event");
        $event = $event ? $event : 'login';
        $captcha = input("captcha");
        $userType = input('user_type',0);
        $res = $this->userAction->login($mobile,$event,$captcha,$userType,$open_id);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        if($res['code']==2){
            return commonReturnError($res['msg'],402);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 获取微信事件推送
     */
    public function api()
    {
        $post = file_get_contents('php://input');
        $jsonEncode = json_encode(simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA));
        $notifyArr = json_decode($jsonEncode,true);
        switch ($notifyArr['MsgType']){
            case "event":
                //捕获扫描事件
                //一. 扫描自定义带有参数的公众号二维码
                if(isset($notifyArr["EventKey"]) && !is_array($notifyArr["EventKey"])){
                    //首次关注
                    $parentUserId = substr($notifyArr["EventKey"], 8);
                    if($notifyArr["Event"] === 'subscribe' && is_numeric(intval($parentUserId)) && intval($parentUserId) <= 100000){
                        $openId = $notifyArr['FromUserName'];
                        //生成用户记录
                        //01.查看用户记录是否存在
                        $userInfoRecord = Db::name('index_users')->where('open_id',$openId)->find();
                        if(!$userInfoRecord){
                            //获取用户昵称和头像
                            $userInfo = get_wx_user_info($openId);
                            $headImg = '';
                            $nickname = '';
                            $sex = 0;
                            if($userInfo){
                                $headImg = $userInfo['headimgurl'];
                                $nickname = $userInfo['nickname'];
                                $sex = $userInfo['sex'];
                            }
                            $data = [
                                'head_img' => $headImg,
                                'sex' => $sex,
                                'nickname' => $nickname,
                                'open_id' => $openId,
                                'createtime' => date('Y-m-d H:i:s'),
                                'user_type' => 0
                            ];
                            Db::startTrans();
                            try{
                                $userId = Db::name('index_users')->strict(false)->insertGetId($data);
                                //生成关注记录
                                //查看是否有扫码关注历史
                                $subscribedRecord = Db::name('user_subscribe')->where('open_id',$openId)->find();
                                if(!$subscribedRecord){
                                    //上级获取红包
                                    $redBag = floatval(config('site.giving_red_bag'));
                                    //插入记录
                                    $data = [
                                        'parent_user_id' => $parentUserId,
                                        'user_id' => $userId,
                                        'open_id' => $openId,
                                        'createtime' => date("Y-m-d H:i:s"),
                                        'red_bag' => $redBag
                                    ];
                                    Db::name('user_subscribe')->strict(false)->insert($data);
                                    Db::name('index_users')->where('id',$parentUserId)->setInc('now_money',$redBag);
                                    Db::name('index_users')->where('id',$parentUserId)->setInc('add_money',$redBag);
                                    Db::commit();
                                }
                            }catch(Exception $e){
                                Db::rollback();
                                $errorStr = date("Y-m-d H:i:s").' '.$e->getMessage().PHP_EOL;
                                file_put_contents("./subscribeError.txt",$errorStr,FILE_APPEND);
                            }
                        }
                    }
                }
                break;
        }
        exit('success');
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = 'uiguihooiiyg89898';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        return $_GET['echostr'];
        if( $tmpStr == $signature ){

        }else{
            return false;
        }
    }
    /**
     * 用户端注册协议--已添加至接口文档
     */
    public function get_user_register_agreement()
    {
        $text = config('site.user_register_agreement');
        $data = [
            'text' => $text
        ];
        return commonReturnSuccess($data);
    }
    /**
     * 生成公众号菜单
     */
    public function index()
    {
        $config = [
            'app_id' => 'wxb015b2d3be4c8fae',
            'secret' => 'cb609c57be2dbf3a6d86c3e4538ed875',
        ];
        $app = Factory::officialAccount($config);
        $buttons = [
            [
                "type" => "view",
                "name" => "订水",
                "url"  => "https://haoyah.mx1991.com/"
            ],
            [
                "name"       => "用户",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "个人中心",
                        "url"  => "https://haoyah.mx1991.com/pages/mine/mine"
                    ],
                    [
                        "type" => "view",
                        "name" => "我的水票",
                        "url"  => "https://haoyah.mx1991.com/pages/mine/mineOther/myWater"
                    ],
                    [
                        "type" => "view",
                        "name" => "我的订单",
                        "url" => "https://haoyah.mx1991.com/pages/bill/bill"
                    ],
                    [
                        "type" => "view",
                        "name" => "一键送水",
                        "url" => "https://haoyah.mx1991.com/pages/giveWater/giveWater"
                    ]
                ],

            ]
        ];
        $res = $app->menu->create($buttons);
        var_dump($res);
        exit();
    }
    /**
     * 登录或者注册页获取头像--已添加至接口文档
     */
    public function get_logo()
    {
        $logo = cdnurl(config('site.station_logo'),true);
        return commonReturnSuccess(['logo_img'=>$logo]);
    }
    /**
     * 获取优惠券--已添加至接口文档
     */
    public function get_coupons_info()
    {
        $openId = input('open_id');
        $res = $this->userAction->get_coupons_info($openId);
        if($res['code']==0){
            return commonReturnError($res['msg'],403);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }

    /**
     * 获取客服电话
     */
    public function get_service_phone()
    {
        $servicePhone = config('site.service_phone');
        return commonReturnSuccess(['service_phone'=>$servicePhone]);
    }
    /**
     * 使用帮助
     */
    public function get_help_text()
    {
        $helpText = config('site.help_text');
        $data = ['help_text'=>$helpText];
        return commonReturnSuccess($data);
    }
    /**
     * 注册01
     * 获取用户open_id--小程序
     */
    public function get_wx_app_open_id()
    {
        $code = input('code');
        $appId = $this->appId;
        $appSecret = $this->appSecret;
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$appSecret&js_code=$code&grant_type=authorization_code";
        $res = json_decode(send_request($url),true);
        if(isset($res['openid'])){
            $openId = $res['openid'];
            $data = ['open_id'=>$openId];
            return commonReturnSuccess($data);
        }
        return commonReturnError('系统错误，请稍后再试');
    }
    /**
     * 注册02
     * 微信小程序--注册+分销功能实现
     * request param *
     * mobile
     * captcha
     * open_id
     * nickname
     * head_img
     * p_user_id
     */
    public function wxapp_register()
    {
        $param = request()->param();
        $res = $this->userAction->wxapp_register($param);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    public function test()
    {
        send_msg_to_user(16);
    }
    /**
     * 获取企业定水详情
     */
    public function get_intro_content()
    {
        $introContent = config('site.intro_content');
        return commonReturnSuccess(['intro_content'=>$introContent]);
    }
    /**
     * 获取手机号
     */
    public function get_user_phone()
    {
        $code = input('code');
        if(!$code){
            return commonReturnError('code不能为空');
        }
        $res = get_user_phone($code);
        if(!$res){
            return commonReturnError('手机号获取失败');
        }
        return commonReturnSuccess(['phone'=>$res]);
    }


    /**
     * 获取vip说明内容
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $event 事件名称
     * @param string $captcha 验证码
     */
    public function vip_content()
    {
        return commonReturnSuccess(config('site.vip_content'));
    }

    /**
     * 获取首页优惠券图片
     */
    public function coupon_img()
    {
        return commonReturnSuccess(cdnurl(config('site.coupon_img')));
    }
    /**
     * 自动登录
     */
    public function auto_login()
    {
        $openId = input('open_id');
        $field = 'id,mobile,open_id,user_type,b_name';
        $info = Db::name('index_users')->field($field)->where('open_id', $openId)->find();
        $newToken = get_new_token();
        cache($newToken, $info, 60 * 60 * 24 * 15);
        $data = ['token' => $newToken, 'user_id' => $info['id'],'user_type'=>$info['user_type']];
        return ['code' => 1, 'msg' => '登录成功', 'data' => $data];
    }
}

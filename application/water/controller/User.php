<?php
namespace app\water\controller;

use app\common\controller\Publics;
use app\water\action\UserAction;
use think\Db;

class User extends Publics
{
    private $userAction;
    private $appId;
    private $appSecret;
    public function __construct()
    {
        parent::__construct();
        $this->userAction = new UserAction();
        $this->appId = config('site.wx_app_id');
        $this->appSecret = config('site.wx_app_secret');
    }
     /**
     * 水站端注册--已添加至接口文档
     * return array
     */
    public function register()
    {
        $param = request()->param();
        $res = $this->userAction->register($param);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 水站端登录-已添加至接口文档
     */
    public function login()
    {
        $mobile = input('mobile');
        $password = input('password');
        $res = $this->userAction->login($mobile,$password);
        if($res['code']==0){
            return commonReturnSuccess($res['data'],$res['msg']);
        }elseif($res['code']==1){
            return commonReturnError($res['msg'],402);
        }else{
            return commonReturnError($res['msg']);
        }
    }
    /**
     * 获取code 已添加至接口文档
     * return array
     */
    public function get_code()
    {
        $hostAddress = config('site.host_address');
        $redirectUrl = $hostAddress.'/water/user/get_open_id';
        $appId = $this->appId;
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appId&redirect_uri=$redirectUrl&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        $data = ['url'=>$url];
        return commonReturnSuccess($data);
    }
    /**
     * 获取用户open_id
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
        $redirect = "http://haoyah.mx1991.com/pages/serveBill/serveBill?open_id=".$openId;
        $this->redirect($redirect, 302);
    }
    /**
     * 忘记密码-已添加至接口文档
     * return array
     */
    public function reset_password()
    {
        $mobile = input("mobile");
        $event = input("event");
        $event = $event ? $event : 'resetpwd';
        $captcha = input("captcha");
        $newPassword = input('new_password');
        $res = $this->userAction->reset_password($mobile,$event,$captcha,$newPassword);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
     /**
     * 获取系统消息--已添加至接口文档
     */
    public function get_news_list()
    {
        $type = strval(input('type',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $list = $this->userAction->get_news_list($type,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 获取系统消息详情--已添加至接口文档
     */
    public function get_news_detail()
    {
        $newsId = input('news_id');
        $res = $this->userAction->get_news_detail($newsId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    public function get_water_register_agreement()
    {
        $text = config('site.water_register_agreement');
        $data = [
            'text' => $text
        ];
        return commonReturnSuccess($data);
    }
    /**
     * 注册02
     * 微信小程序--注册+分销功能实现
     * request param *
     * mobile
     * event
     * captcha
     * open_id
     * nickname
     * head_img
     * password
     * b_name
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



}
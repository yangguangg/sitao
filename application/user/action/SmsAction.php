<?php
namespace app\user\action;

use think\Controller;
use app\common\library\Sms as Smslib;
use app\user\model\UserModel;
use think\Db;
use think\Hook;

class SmsAction extends Controller
{
    /**
     * 检测验证码
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $event 事件名称
     * @param string $captcha 验证码
     */
    public function check($mobile,$event,$captcha)
    {
        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            return ['code'=>0,'msg'=>'手机号不正确','data'=>[]];
        }
        if ($event) {
            $userModel = new UserModel();
            $userinfo = $userModel->get(['mobile'=>$mobile]);
            if ($event == 'register' && $userinfo) {
                //已被注册
                return ['code'=>0,'msg'=>'已被注册','data'=>[]];
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                return ['code'=>0,'msg'=>'已被占用','data'=>[]];
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                return ['code'=>0,'msg'=>'未注册','data'=>[]];
            }
        }
        $ret = Smslib::check($mobile, $captcha, $event);
        if ($ret) {
            return ['code'=>1,'msg'=>'成功','data'=>[]];
        } else {
            return ['code'=>0,'msg'=>'验证码不正确','data'=>[]];
        }
    }
     /**
     * 发送验证码
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    public function send($mobile,$event,$ip)
    {
        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            return ['code'=>0,'msg'=>'手机号不正确','data'=>[]];
        }
        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 5) {
            return ['code'=>0,'msg'=>'发送频繁','data'=>[]];
        }
        $ipSendTotal = \app\common\model\Sms::where(['ip' => $ip])->whereTime('createtime', '-1 hours')->count();
        if ($ipSendTotal >= 20) {
            return ['code'=>0,'msg'=>'发送频繁','data'=>[]];
        }
        if ($event) {
            $userinfo = Db::name('index_users')->where('mobile',$mobile)->find();
            if ($event == 'register' && $userinfo) {
                //已被注册
                return ['code'=>0,'msg'=>'已被注册','data'=>[]];
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                return ['code'=>0,'msg'=>'已被占用','data'=>[]];
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                return ['code'=>0,'msg'=>'未注册','data'=>[]];
            }
        }
        if (!Hook::get('sms_send')) {
            return ['code'=>0,'msg'=>'请在后台插件管理安装短信验证插件','data'=>[]];
        }
        $ret = Smslib::send($mobile, null, $event);
        if ($ret) {
            return ['code'=>1,'msg'=>'发送成功','data'=>[]];
        } else {
            return ['code'=>0,'msg'=>'发送失败，请检查短信配置是否正确','data'=>[]];
        }
    }
}
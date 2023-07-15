<?php

namespace app\user\action;

use app\user\model\UserModel;
use Exception;
use think\Controller;
use think\Db;

class UserAction extends Controller
{
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function register($mobile, $openId, $userType, $event, $captcha)
    {
        if (!$mobile) {
            return ['code' => 0, 'msg' => '请输入手机号', 'data' => []];
        }
        if (!$captcha) {
            return ['code' => 0, 'msg' => '请输入验证码', 'data' => []];
        }
        if (!$openId) {
            return ['code' => 0, 'msg' => '关键参数缺失', 'data' => []];
        }
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($mobile, $event, $captcha);
        if ($res['code'] == 0) {
            return $res;
        }
        //逻辑处理
        //查看是否有用户记录
        $record = $this->userModel->get(['open_id' => $openId]);
        if ($record) {
            if ($record['mobile']) return ['code' => 0, 'msg' => '该账号已注册', 'data' => []];
            //更新用户记录
            $record->mobile = $mobile;
            $res = $record->save();
            if ($res) {
                return ['code' => 1, 'msg' => '注册成功', 'data' => $record];
            }
            return ['code' => 0, 'msg' => '系统错误，请稍后再试', 'data' => []];
        }
        //获取用户昵称和头像
        $userInfo = get_wx_user_info($openId);
        $headImg = '';
        $nickname = '';
        $sex = 0;
        if ($userInfo) {
            $headImg = $userInfo['headimgurl'];
            $nickname = $userInfo['nickname'];
            $sex = $userInfo['sex'];
        }
        $data = [
            'mobile' => $mobile,
            'head_img' => $headImg,
            'sex' => $sex,
            'nickname' => $nickname,
            'open_id' => $openId,
            'createtime' => date('Y-m-d H:i:s'),
            'user_type' => $userType
        ];
        Db::startTrans();
        try {
            $id = Db::name("index_users")->strict(false)->insertGetId($data);
            //上级分佣，红包发放至账户余额
            $subscribeRecord = Db::name('user_subscribe')->where('open_id', $openId)->find();
            if ($subscribeRecord) {
                $parentUserId = $subscribeRecord['parent_user_id'];
                $gettingMoney = floatval(config('site.giving_red_bag'));
                Db::name('index_users')->where('id', $parentUserId)->setInc('now_money', $gettingMoney);
                Db::name('index_users')->where('id', $parentUserId)->setInc('add_money', $gettingMoney);
                //更新用户关注历史表
                $updateData = ['red_bag' => $gettingMoney];
                Db::name('user_subscribe')->where('open_id', $openId)->update($updateData);
            }
            //缓存操作
            $newToken = get_new_token();
            $data['id'] = $id;
            cache($newToken, $data, 60 * 60 * 24 * 15);
            $data = ['token' => $newToken, 'user_id' => $id];
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => '系统错误，请稍后再试', 'data' => []];
        }
        return ['code' => 1, 'msg' => '注册成功', 'data' => $data];
    }

    /**
     * 登录
     * return array
     */
    public function login($mobile, $event, $captcha, $userType,$openId)
    {
        //校验手机验证码
        $smsAction = new SmsAction();
//        测试，不验证
        $res = $smsAction->check($mobile,$event,$captcha);
        if($res['code']==0){
            return $res;
        }
//        测试，不验证end
        if (!$mobile) {
            return ['code' => 0, 'msg' => '请输入手机号', 'data' => []];
        }
        if (!$captcha) {
            return ['code' => 0, 'msg' => '请输入验证码', 'data' => []];
        }
        if (!$openId) {
            return ['code' => 0, 'msg' => 'openid不能为空', 'data' => []];
        }
        $field = 'id,mobile,open_id,user_type,b_name';
        $info = Db::name('index_users')->field($field)->where('mobile', $mobile)->find();
        if (!$info) {

            //获取用户昵称和头像
            $userInfo = get_wx_user_info($openId);
            $headImg = '';
            $nickname = '';
            $sex = 0;
            if ($userInfo) {
                $headImg = $userInfo['headimgurl'];
                $nickname = $userInfo['nickname'];
                $sex = $userInfo['sex'];
            }
            $data = [
                'mobile' => $mobile,
                'head_img' => $headImg,
                'sex' => $sex,
                'nickname' => $nickname,
                'open_id' => $openId,
                'createtime' => date('Y-m-d H:i:s'),
                'user_type' => $userType
            ];
            Db::startTrans();
            try {
                $id = Db::name("index_users")->strict(false)->insertGetId($data);
                //上级分佣，红包发放至账户余额
                $subscribeRecord = Db::name('user_subscribe')->where('open_id', $openId)->find();
                if ($subscribeRecord) {
                    $parentUserId = $subscribeRecord['parent_user_id'];
                    $gettingMoney = floatval(config('site.giving_red_bag'));
                    Db::name('index_users')->where('id', $parentUserId)->setInc('now_money', $gettingMoney);
                    Db::name('index_users')->where('id', $parentUserId)->setInc('add_money', $gettingMoney);
                    //更新用户关注历史表
                    $updateData = ['red_bag' => $gettingMoney];
                    Db::name('user_subscribe')->where('open_id', $openId)->update($updateData);
                }
                //缓存操作
                $data['id'] = $id;
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return ['code' => 0, 'msg' => '系统错误，请稍后再试', 'data' => []];
            }
            //return ['code' => 2, 'msg' => '该手机号尚未注册', 'data' => []];
            $info = $data;
        }
        //身份判断
        if ($userType == 1) {
            // if (!$info['b_name']) {
            //return ['code' => 0, 'msg' => '您还没有注册水站', 'data' => []];
            // }
        }
        $newToken = get_new_token();
        cache($newToken, $info, 60 * 60 * 24 * 15);
        $data = ['token' => $newToken, 'user_id' => $info['id'], 'user_type' => $userType];
        return ['code' => 1, 'msg' => '登录成功', 'data' => $data];
    }


    /**
     * 检测是否完成注册
     */
    public function check_is_registered($openId)
    {
        $record = Db::name('index_users')->where('open_id', $openId)->find();
        $status = 0;
        if ($record) {
            $status = 1;
        }
        return ['register_status' => $status];
    }

    /**
     * 弹窗优惠券
     */
    public function get_coupons_info($openId)
    {
        return ['code' => 0, 'msg' => '已领取', 'data' => []];
        $userId = Db::name('index_users')->where('open_id', $openId)->value('id');
        $where = [
            'c_user_id' => $userId,
            'type' => 1
        ];
        $record = Db::name('user_coupons')->where($where)->find();
        if ($record) {
            return ['code' => 0, 'msg' => '已领取', 'data' => []];
        }
        $field = 'id,coupons_value,threshold_price';
        $info = Db::name('coupons')->field($field)->order('id', 'desc')->find();
        return ['code' => 1, 'msg' => '未领取', 'data' => $info];
    }


    /**
     * 领券(领券中心)
     */
    public function get_coupon($id, $user_id)
    {
        // return ['code' => 1, 'msg' => '暂停领取', 'data' => []];
        // return ['code'=>1,'msg'=>'错误提示, 暂停领取','data'=>[]];
        $couponinfo = Db::name('coupons')->where('id', $id)->find();
        if (empty($couponinfo)) {
            return ['code' => 1, 'msg' => '该优惠券不存在', 'data' => []];
        }
        if ($couponinfo['deletetime']) {
            return ['code' => 1, 'msg' => '该优惠券不存在.', 'data' => []];
        }
        $where = [
            'c_user_id' => $user_id,
            'coupons_id' => $id,
        ];
        $record = Db::name('user_coupons')->where($where)->find();
        if ($record) {
            return ['code' => 1, 'msg' => '已领取', 'data' => []];
        }
        $uinfo = Db::name('index_users')->where('id', $user_id)->find();
        if($uinfo['vip'] != 2){
            if($couponinfo['status'] == 3){
                return ['code' => 1, 'msg' => '会员才能领取.', 'data' => []];
            }
        }
        $data = [
            'c_user_id' => $user_id,
            'coupons_value' => $couponinfo['coupons_value'],
            'threshold_price' => $couponinfo['threshold_price'],
            'createtime' => date('Y-m-d H:i:s'),
            'end_time' => date('Y-m-d',strtotime('+'.config('site.vip_get_coupon_days').' days')),
            'status' => 0,
            'coupons_id' => $id,
            'type' => 2,
        ];
        $r = Db::name('user_coupons')->insert($data);
        if($r){
            return ['code' => 1, 'msg' => '领取成功', 'data' => $data];
        }else{
            return ['code' => 1, 'msg' => '领取失败', 'data' => []];
        }
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
     * p_user_id
     */
    public function wxapp_register($param)
    {
        if (!isset($param['mobile']) || !$param['mobile']) {
            return ['code' => 0, 'msg' => '请输入手机号', 'data' => []];
        }
        if (!isset($param['captcha']) || !$param['captcha']) {
            return ['code' => 0, 'msg' => '请输入验证码', 'data' => []];
        }
        if (!isset($param['open_id']) || !$param['open_id']) {
            return ['code' => 0, 'msg' => '关键参数缺失1', 'data' => []];
        }
        $mobile = $param['mobile'];
        $captcha = $param['captcha'];
        $event = 'register';
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($mobile, $event, $captcha);
        if ($res['code'] == 0) {
            return $res;
        }
        //检测用户是否完成注册
        $where = [
            'mobile' => $mobile
        ];
        $record = Db::name("index_users")->where($where)->find();
        if ($record) {
            return ['code' => 0, 'msg' => '该手机号已注册', 'data' => []];
        }
        //注册加分佣同时实现
        Db::startTrans();
        try {
            //01.实现注册
            //插入记录
            $openId = $param['open_id'];
            $data = [
                'mobile' => $mobile,
                'open_id' => $openId,
                'nickname' => $param['nickname'],
                'head_img' => $param['head_img'],
                'user_type' => 0,
                'createtime' => date("Y-m-d H:i:s")
            ];
            $userId = Db::name('index_users')->strict(false)->insertGetId($data);
            $data['id'] = $userId;
            //存入缓存
            $newToken = get_new_token();
            $data['id'] = $userId;
            cache($newToken, $data, 60 * 60 * 24 * 15);
            //02.实现分佣
            if (isset($param['p_user_id']) && $param['p_user_id']) {
                //获取红包金额
                //增加余额
                //查看是否有分享历史
                $subscribedRecord = Db::name('user_subscribe')->where('open_id', $openId)->find();
                if (!$subscribedRecord) {
                    $parentUserId = $param['p_user_id'];
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
                    Db::name('index_users')->where('id', $parentUserId)->setInc('now_money', $redBag);
                    Db::name('index_users')->where('id', $parentUserId)->setInc('add_money', $redBag);
                    Db::name('index_users')->where('id', $parentUserId)->setInc('child_nums');
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => '系统错误，请稍后再试', 'data' => []];
        }
        $data = ['token' => $newToken, 'user_id' => $userId];
        return ['code' => 1, 'msg' => '注册成功', 'data' => $data];
    }
}

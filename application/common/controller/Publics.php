<?php
namespace app\common\controller;

use think\Controller;
use think\Db;

class Publics extends Controller
{
    protected $userId;
    protected $mobile;
    protected $openId;
    protected $userType;
    protected $token;
    protected $vip;
    public function __construct()
    {
        parent::__construct();

        //跨域请求检测
        check_cors_request();
        $this->checkLogin();
    }
    /**
     * 校验签名
     * @return array
     */
    public function checkSign()
    {
        $param = request()->param();
        $sign = isset($param['sign'])?$param['sign']:false;
        if(!$sign){
            throw_api_error('签名不能为空');
        }
        unset($param['sign']);
        $key = 'e1def8a46b7c673d5a4a111920f24e8c';
        ksort($param);
        $str = '';
        foreach ($param as $k => $v){
            $str .= $k.'='.$v;
        }
        $str.=$key;
        if($sign !== md5($str)){
            throw_api_error('签名错误');
        }
    }

    /**
     * 验证是否登录，没有登录的也不报错
     * @throws \app\common\exception\ApiMsgSend
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkLogin()
    {
        $token = input('token','');
        if(!$token || !cache($token)){
            return ;
            throw_api_error('请重新登录',401);
        }
        
        $this->token = $token;
        $data = cache($token);
        $this->userId = $data['id'];
        $this->mobile = $data['mobile'];
        $this->openId = $data['open_id'];
        $record = Db::name('index_users')->where("open_id",$this->openId)->find();
        
        if(!$record){
            return ;
            throw_api_error('请重新登录',401);
        }
        $this->vip = $record['vip'];
        
        $this->userType = $data['user_type'];
        cache($token,$data,60*60*24*15);
    }
}
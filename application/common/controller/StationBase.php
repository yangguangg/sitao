<?php
namespace app\common\controller;

use think\Db;

class StationBase extends Publics
{
    protected $userId;
    protected $mobile;
    protected $openId;
    protected $userType;
    protected $token;
    public function __construct()
    {
        parent::__construct();
        $this->checkLogin();
    }
    public function checkLogin()
    {
        $token = input('token','');
        if(!$token || !cache($token)){
            throw_api_error('请重新登录',401);
        }
        $this->token = $token;
        $data = cache($token);
        $this->userId = $data['id'];
        $this->mobile = $data['mobile'];
        $this->openId = $data['open_id'];
        $record = Db::name('index_users')->where("open_id",$this->openId)->find();
        if(!$record){
            throw_api_error('请重新登录',401);
        }
        if($record['status']!='1'){
            throw_api_error('请进行实名认证，并耐心等待审核结果!',400);
        }
        $this->userType = $data['user_type'];
        cache($token,$data,60*60*24*15);
    }
}
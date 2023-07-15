<?php
namespace app\common\controller;

use think\Db;

class BaseApi extends Publics
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
        $this->checkLogin();
    }
    public function checkLogin()
    {
       
        $token = input('token','');
        
        $tokenb = input('tokenb','');
        if($tokenb){
            return;
        }
        
        if(!$token || !cache($token)){
            throw_api_error('请重新登录11',401);
        }
        $this->token = $token;
        $data = cache($token);
        $this->userId = $data['id'];
        $this->mobile = $data['mobile'];
        $this->openId = $data['open_id'];
        $record = Db::name('index_users')->where("open_id",$this->openId)->find();
        if(!$record){
            throw_api_error('请重新登录2',401);
        }
        $this->vip = $record['vip'];
        $this->userType = $data['user_type'];
        cache($token,$data,60*60*24*15);
    }
}
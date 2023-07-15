<?php
namespace app\water\action;

use app\user\action\SmsAction;
use app\user\model\UserModel;
use Exception;
use think\Controller;
use think\Db;
use think\Validate;

class UserAction extends Controller
{
    private $userModel;
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    /**
     * 水站端--注册
     */
    public function register($param)
    {
        //参数验证
        $rules = [
            'captcha' => 'require',
            'b_name' => 'require',
            'apply_address' => 'require',
            'mobile' => 'require|^1\d{10}$'
        ];
        $msgs = [
            'captcha.require' => '请输入验证码',
            'b_name.require' => '请输入水站名称',
            'apply_address.require' => '请设置详细配送地址',
            'mobile.require' => '请输入手机号',
            'mobile' => '手机号格式不正确'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($param);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }

        $mobile = $param['mobile'];
        $openId = $param['open_id'];
        $event = $param["event"];
        // $password = $param['password'];
        $event = $event ? $event : 'register';
        $captcha = $param["captcha"];
        $stationName = $param['b_name'];
        $applyAddress = $param['apply_address'];
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($mobile,$event,$captcha);
        if($res['code']==0){
            return $res;
        }
        //逻辑处理
        //查看是否有用户记录
        if(!$openId){
            return ['code'=>0,'msg'=>'关键参数缺失!','data'=>[]];
        }
        $record = $this->userModel->get(['b_name'=>$stationName]);
        if($record){
            return ['code'=>0,'msg'=>'水站名称已被使用','data'=>[]];
        }
        $record = $this->userModel->get(['open_id'=>$openId]);
        // $password = md5($password);
        if($record){
            if($record['mobile'])return ['code'=>0,'msg'=>'该账号已注册!','data'=>[]];
            //更新用户记录
            $record->mobile = $mobile;
            $record->apply_address = $applyAddress;
            $record->b_name = $stationName;
            $res = $record->save();
            if($res){
                //缓存操作
                $newToken = get_new_token();
                cache($newToken,$record,60*60*24*15);
                $data = ['token'=>$newToken];
                return ['code'=>1,'msg'=>'注册成功!','data'=>$data];
            }
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
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
            'mobile' => $mobile,
            'head_img' => $headImg,
            'sex' => $sex,
            'nickname' => $nickname,
            'open_id' => $openId,
            'createtime' => date('Y-m-d H:i:s'),
            'user_type' => 1,
            'apply_address' => $applyAddress,
            'b_name' => $stationName
        ];
        Db::startTrans();
        try{
            $id = Db::name('index_users')->strict(false)->insertGetId($data);
            //缓存操作
            $newToken = get_new_token();
            $data['id'] = $id;
            cache($newToken,$data,['expire'=>60*60*24*15]);
            $data = ['token'=>$newToken,'user_id'=>$id];
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'注册成功!','data'=>$data];
    }
    /**
     * 登录
     * return array
     */
    public function login($mobile,$password)
    {
        $info = Db::name('index_users')->where('mobile',$mobile)->find();
        if(!$info){
            return ['code'=>1,'msg'=>'该手机号尚未注册','data'=>[]];
        }
        if(md5($password) !== $info['b_password']){
            return ['code'=>2,'msg'=>'密码错误','data'=>[]];
        }
        $newToken = get_new_token();
        cache($newToken,$info,60*60*24*15);
        $data = ['token'=>$newToken,'user_id'=>$info['id']];
        return ['code'=>0,'msg'=>'登录成功!','data'=>$data];
    }
    /**
     * 忘记密码
     * return array
     */
    public function reset_password($mobile,$event,$captcha,$newPassword)
    {
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($mobile,$event,$captcha);
        if($res['code']==0){
            return $res;
        }
        $newPassword = md5($newPassword);
        $data = [
            'b_password' => $newPassword
        ];
        Db::name('index_users')->where('mobile',$mobile)->update($data);
        return ['code'=>1,'msg'=>'重置成功!','data'=>[]];
    }
    /**
     * 获取系统消息列表
     */
    public function get_news_list($type,$page,$limit)
    {
        $field = 'id,title,second_title,createtime,content';
        $where = [
            'status' => '0',
            'deletetime' => null,
            'type' => $type
        ];
        $list = Db::name('water_news')->field($field)->where($where)->page($page,$limit)->select();
        foreach($list as &$v){
            $v['createtime'] = date("Y-m-d H:i:s",$v['createtime']);
        }
        return $list;
    }
    /**
     * 获取系统消息详情
     */
    public function get_news_detail($newsId)
    {
        if(!$newsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $detail = Db::name('water_news')->where('id',$newsId)->find();
        return ['code'=>1,'msg'=>'查询成功','data'=>$detail];
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
    public function wxapp_register($param)
    {
         //参数验证
         $rules = [
            'captcha' => 'require',
            'b_name' => 'require',
            'apply_address' => 'require',
            'mobile' => 'require|^1\d{10}$'
        ];
        $msgs = [
            'captcha.require' => '请输入验证码',
            'b_name.require' => '请输入水站名称',
            'apply_address.require' => '请设置详细配送地址',
            'mobile.require' => '请输入手机号',
            'mobile' => '手机号格式不正确'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($param);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }
        $mobile = $param['mobile'];
        $captcha = $param['captcha'];
        $event = 'register';
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($mobile,$event,$captcha);
        if($res['code']==0){
            return $res;
        }
        //检测水站名称是否重名
        $stationName = $param['b_name'];
        $record = $this->userModel->get(['b_name'=>$stationName]);
        if($record){
            return ['code'=>0,'msg'=>'水站名称已被使用','data'=>[]];
        }
        //注册,插入记录
        $openId = $param['open_id'];
        //$password = $param['password'];
        $applyAddress = $param['apply_address'];
        $data = [
            'mobile' => $mobile,
            'open_id'=>$openId,
            'nickname'=>$param['nickname'],
            'head_img'=>$param['head_img'],
            'user_type'=>1,
            'apply_address' => $applyAddress,
            'b_name' => $stationName,
            'createtime' => date("Y-m-d H:i:s")
        ];
        $userId = Db::name('index_users')->strict(false)->insertGetId($data);
        $data['id'] = $userId;
        //存入缓存
        $newToken = get_new_token();
        cache($newToken,$data,60*60*24*15);
        $data = ['token'=>$newToken,'user_id'=>$userId];
        return ['code'=>1,'msg'=>'注册成功','data'=>$data];
    }
}
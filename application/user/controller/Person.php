<?php
namespace app\user\controller;

use app\admin\model\QianBucket;
use app\admin\model\QianBucketReport;
use app\admin\model\YaBucket;
use app\admin\model\YaBucketBack;
use app\common\controller\BaseApi;
use app\user\action\PersonAction;
use app\user\action\UserAction;
use think\Db;

class Person extends BaseApi
{
    private $personAction;
    public function __construct()
    {
        parent::__construct();
        //$this->checkSign();
        $this->personAction = new PersonAction();
        $this->userAction = new UserAction();
    }
    /**
     * 获取个人信息--已添加至接口文档
     */
    public function get_person_info()
    {
        $userId = $this->userId;
        $info = $this->personAction->get_person_info($userId);
        return commonReturnSuccess($info);
    }
    /**
     * 我的桶押金
     */
    public function get_my_barrel()
    {
        $userId = $this->userId;
        $info = $this->personAction->get_my_barrel($userId);
        return commonReturnSuccess($info);
    }
    /**
     * 我的水票--已添加至接口文档
     */
    public function get_my_tickets()
    {
        $userId = $this->userId;
        $page = input('page',1);
        $list = $this->personAction->get_my_tickets($userId,$page);
        return commonReturnSuccess($list);
    }
    /**
     * 邀请好友--生成海报--已添加至接口文档
     */
    public function get_posters_path()
    {
        $userId = $this->userId;
        $finalFilePath = $this->personAction->get_posters_path($userId);
        $data = [
            'path'=>'https://www.banlv999.top'.'/'.$finalFilePath,
            
            'pathb'=>'https://www.banlv999.top/static/posters/poster1.png',
        ];
        return commonReturnSuccess($data);
    }
    /**
     * 我的--分销中心--提现--已添加至接口文档
     */
    public function my_money()
    {
        $userId = $this->userId;
        $info = $this->personAction->my_money($userId);
        return commonReturnSuccess($info);
    }
    /**
     * 领取优惠券
     */
    public function get_coupons()
    {
        $openId = input('id');
        $res = $this->userAction->get_coupon($openId,$this->userId);
        if($res['code']==0){
            return commonReturnError($res['msg'],403);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 提现操作--已添加至接口文档
     */
    public function get_money_action()
    {
        $userId = $this->userId;
        $params = request()->param();
        $res = $this->personAction->get_money_action($userId,$params);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 我的--分销中心--邀请记录--已添加至接口文档
     */
    public function get_invited_records()
    {
        $userId = $this->userId;
        $page = input('page',1);
        $limit = input('limit',15);
        $list = $this->personAction->get_invited_records($userId,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 我的--分销中心--展示--已添加至接口文档
     */
    public function get_account_info()
    {
        $userId = $this->userId;
        $info = $this->personAction->get_account_info($userId);
        return commonReturnSuccess($info);
    }
    /**
     * 我的--分销中心--提现记录--已添加至接口文档
     */
    public function get_withdrawal_record()
    {
        $userId = $this->userId;
        $page = input('page',1);
        $limit = input('limit',15);
        $list = $this->personAction->get_withdrawal_record($userId,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 领取优惠券---已添加至接口文档(首页领取,并非领券中心)
     */
    public function get_my_coupons()
    {
        $userId = $this->userId;
        $couponsId = input('coupons_id');
        $res = $this->personAction->get_my_coupons($userId,$couponsId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 修改手机号
     */
    public function change_mobile()
    {
        $newMobile = input('new_mobile');
        $captcha = input("captcha");
        $userId = $this->userId;
        $res = $this->personAction->change_mobile($newMobile,$captcha,$userId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess([],$res['msg']);
    }


    /**
     * 20220510 新增
     * 用户的欠桶记录
     * type 1=增加 2=减少
     * page 页码
     * limit 每页显示条数
     */
    public function get_qian_list()
    {
        $type = strval(input('type',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'index_users_id' => $water_id,
        ];
        if(in_array($type,[1,2])){
            $where['type'] = $type;
        }
        $model = new QianBucket();
        $list = $model->with(['indexusers','water','bucketcate'])->where($where)->order('id desc')->paginate($limit);
        return commonReturnSuccess($list);
    }
    
    
    /**
     * 20220510 新增
     * 用户的欠桶记录
     * page 页码
     * limit 每页显示条数
     */
    public function get_qian_report_list()
    {
        $type = strval(input('type',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'index_users_id' => $water_id,
        ];
        if(in_array($type,[1,2])){
            $where['type'] = $type;
        }
        $model = new QianBucketReport();
        $list = $model->with(['indexusers','water','bucketcate'])->where($where)->paginate($limit);
        return commonReturnSuccess($list);
    }


    /**
     * 20220510 新增
     * 获取押桶列表
     * user_id 用户id
     * page 页码
     * limit 每页显示条数
     */
    public function get_ya_list()
    {
        $type = strval(input('type',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'index_users_id' => $water_id,
        ];
        $model = new YaBucket();
        $list = $model->with(['indexusers','water','bucketcate'])->where($where)->order('id desc')->paginate($limit);
        return commonReturnSuccess($list);
    }


    /**
     * 20220510 新增
     * 申请退桶
     * order_sn 订单号
     * num 退桶数量
     * aliname 支付宝姓名
     * aliaccount 支付宝账号
     */
    public function ya_tui()
    {
        $water_id = $this->userId;
        $order_sn = strval(input('order_sn'));
        $aliname = input('aliname');
        $aliaccount = input('aliaccount');
        $num = input('num',0);
        $model = new YaBucket();
        $r = $model->back_ya($water_id, $order_sn, $aliname, $aliaccount, $num);
        if($r['code'] != 200){
            return commonReturnError($r['msg']);
        }else{
            return commonReturnSuccess('',$r['msg']);
        }
    }

    /**
     * 20220510 新增
     * 查看退桶详情
     * order_sn 订单号
     */
    public function ya_tui_detail()
    {
        $water_id = $this->userId;
        $order_sn = strval(input('order_sn'));
        $model = new YaBucketBack();
        $where = [
            'order_sn' => $order_sn,
            'index_users_id' => $water_id,
        ];
        $info = $model->where($where)->order('id desc')->find();
        if(empty($info)){
            return commonReturnError('未查询到该数据');
        }else{
            return commonReturnSuccess($info,'获取成功');
        }
    }
    
    
    // public function get_vip_bg_img(){
    //     $img = (config('site.vip_bg_img'));
    //     return commonReturnSuccess($img,'获取成功');
    // }
}
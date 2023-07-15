<?php
namespace app\water\controller;

use app\admin\model\BucketCate;
use app\admin\model\QianBucket;
use app\admin\model\QianBucketAllreport;
use app\admin\model\QianBucketReport;
use app\admin\model\YaBucket;
use app\admin\model\YaBucketAllreport;
use app\common\controller\BaseApi;
use app\water\action\PersonAction;
use think\Db;

class Person extends BaseApi
{
    private $personAction;
    public function __construct()
    {
        parent::__construct();
        $this->personAction = new PersonAction();
    }
    /**
     * 修改密码--已添加至接口文档
     */
    public function edit_password()
    {
        $mobile = input("mobile");
        $event = input("event");
        $event = $event ? $event : 'changepwd';
        $captcha = input("captcha");
        $oldPassword = input('old_password');
        $newPassword = input('new_password');
        $res = $this->personAction->edit_password($mobile,$event,$captcha,$oldPassword,$newPassword);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    /**
     * 获取手机号--已添加至接口文档
     */
    public function get_mobile()
    {
        $mobile = $this->mobile;
        return commonReturnSuccess(['mobile'=>$mobile]);
    }
    /**
     * 绑定身份证--已添加至接口文档
     */
    public function sub_id_card_info()
    {
        $param = request()->param();
        $userId = $this->userId;
        $res = $this->personAction->sub_id_card_info($param,$userId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 数据统计--已添加至接口文档
     */
    public function get_station_statistical()
    {
        $userId = $this->userId;
        $params = request()->param();
        $page = input('page',1);
        $limit = input('limit',10);
        $datas = $this->personAction->get_station_statistical($userId,$params,$page,$limit);
        return commonReturnSuccess($datas);
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
     * 获取实名认证信息
     */
    public function get_true_user_info()
    {
        $userId = $this->userId;
        $info = $this->personAction->get_true_user_info($userId);
        return commonReturnSuccess($info);
    }


    /**
     * 20220510 新增
     * 增加欠桶
     * type 1=增加 2=减少
     * mobile 用户手机号
     * num 处理数量
     * bucket_cate_id 桶类型
     */
    public function get_qian_create()
    {
        $water_id = $this->userId;
        $type = strval(input('type',0));
        $mobile = strval(input('mobile',0));
        $bucket_cate_id = strval(input('bucket_cate_id',0));
        $num = strval(input('num',0));
        $model = new QianBucket();
        $r = $model->handle_qian($water_id, $mobile, $bucket_cate_id, $num, $type);
        if($r['code'] != 200){
            return commonReturnError($r['msg']);
        }else{
            return commonReturnSuccess('',$r['msg']);
        }
    }

    /**
     * 20220510 新增
     * 欠桶记录
     * type 1=增加 2=减少
     * user_id 用户id
     * bucket_cate_id 桶分类
     * page 页码
     * limit 每页显示条数
     */
    public function get_qian_list()
    {
        $type = strval(input('type',0));
        $user_id = strval(input('user_id',0));
        $bucket_cate_id = strval(input('bucket_cate_id',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'water_id' => $water_id,
            // 'bucket_cate_id' => $bucket_cate_id,
        ];
        if($bucket_cate_id > 0){
            $where['bucket_cate_id'] = $bucket_cate_id;
        }
        if(in_array($type,[1,2])){
            $where['type'] = $type;
        }
        if($user_id > 0){
            $where['index_users_id'] = $user_id;
        }
        $model = new QianBucket();
        $list = $model->with(['indexusers','water','bucketcate'])->where($where)->order('id desc')->paginate($limit);
        return commonReturnSuccess($list);
    }
    
    

    /**
     * 20220510 新增
     * 欠桶总统计列表
     * page 页码
     * limit 每页显示条数
     */
    public function get_qian_tong_list()
    {
        $type = strval(input('type',0));
        $user_id = strval(input('user_id',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'water_id' => $water_id,
        ];
        $model = new QianBucketAllreport();
        $list = $model->with(['indexusers','water'])->where($where)->order('id desc')->paginate($limit);
         
        $yaModel = new YaBucketAllreport();
        foreach($list as $vo){
            $ya_num = $yaModel->where(['index_users_id'=>$vo['index_users_id'],'water_id'=>$vo['water_id']])->value('left_num');
            $vo['ya_left_num'] = ($ya_num > 0 ? $ya_num : 0);
        }
        return commonReturnSuccess($list);
    }

    /**
     * 20220510 新增
     * 获取桶类型
     * page 页码
     * limit 每页显示条数
     */
    public function get_bucket_cate_list()
    {
        $page = input('page',1);
        $limit = input('limit',15);
        $where = [
            'status' => 1,
        ];
        $model = new BucketCate();
        $list = $model->where($where)->order('id desc')->paginate($limit);
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
        $user_id = strval(input('user_id',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'water_id' => $water_id,
        ];

        if($user_id > 0){
            $where['index_users_id'] = $user_id;
        }
        $model = new YaBucket();
        $list = $model->with(['indexusers','water','bucketcate'])->where($where)->order('id desc')->paginate($limit);
        return commonReturnSuccess($list);
    }
    
    
    /**
     * 20220510 新增
     * 获取押桶列表
     * user_id 用户id
     * page 页码
     * limit 每页显示条数
     */
    public function get_yaback_list()
    {
        $type = strval(input('type',0));
        $user_id = strval(input('user_id',0));
        $page = input('page',1);
        $limit = input('limit',15);
        $water_id = $this->userId;
        $where = [
            'water_id' => $water_id,
            'ya_bucket.status' => 3,
        ];

        if($user_id > 0){
            $where['index_users_id'] = $user_id;
        }
        $model = new YaBucket();
        $list = $model->with(['indexusers','water','bucketcate'])->where($where)->order('id desc')->paginate($limit);
        return commonReturnSuccess($list);
    }
    /**
     * 20220510 新增
     * 增加押桶
     * mobile 用户手机号
     * num 押桶数量
     * bucket_cate_id 桶类型
     * price 押桶金额
     */
    public function ya_create()
    {
        $water_id = $this->userId;
        $price = input('price',0);
        $mobile = strval(input('mobile',0));
        $bucket_cate_id = strval(input('bucket_cate_id',0));
        $num = strval(input('num',0));
        $model = new YaBucket();
        $r = $model->create_ya($water_id, $mobile, $bucket_cate_id, $num, $price);
        if($r['code'] != 200){
            return commonReturnError($r['msg']);
        }else{
            return commonReturnSuccess('',$r['msg']);
        }
    }
    /**
     * 20220510 新增
     * 取消押桶
     * order_sn 订单号
     */
    public function ya_cancel()
    {
        $water_id = $this->userId;
        $order_sn = strval(input('order_sn'));
        $model = new YaBucket();
        $r = $model->cancel_ya($water_id, $order_sn);
        if($r['code'] != 200){
            return commonReturnError($r['msg']);
        }else{
            return commonReturnSuccess('',$r['msg']);
        }
    }

    /**
     * 20220510 新增
     * 退桶处理
     * mobile 用户手机号
     * num 处理数量
     * bucket_cate_id 桶类型
     * price 押桶金额
     * remark 退桶备注
     */
    public function ya_tui_handle()
    {
        $water_id = $this->userId;
        $order_sn = strval(input('order_sn'));
        $price = input('price',0);
        $remark = input('remark','');
        $model = new YaBucket();
        $r = $model->agree_ya($water_id, $order_sn,$price,$remark);
        if($r['code'] != 200){
            return commonReturnError($r['msg']);
        }else{
            return commonReturnSuccess('',$r['msg']);
        }
    }
}
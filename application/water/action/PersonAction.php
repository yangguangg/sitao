<?php
namespace app\water\action;

use think\Controller;
use app\user\action\SmsAction;
use app\user\model\GoodsOrderModel;
use think\Db;
use think\Validate;

class PersonAction extends Controller
{
    /**
     * 修改密码
     * return array
     */
    public function edit_password($mobile,$event,$captcha,$oldPassword,$newPassword)
    {
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($mobile,$event,$captcha);
        if($res['code']==0){
            return $res;
        }
        $record = Db::name('index_users')->where('mobile',$mobile)->find();
        if($oldPassword !== md5($record['b_password'])){
            return ['code'=>0,'msg'=>'旧密码错误','data'=>[]];
        }
        $newPassword = md5($newPassword);
        $data = [
            'b_password' => $newPassword
        ];
        $res = Db::name('index_users')->where('mobile',$mobile)->update($data);
        if(!$res){
            return ['code'=>0,'msg'=>'新密码与旧密码相同','data'=>[]];
        }
        return ['code'=>1,'msg'=>'修改成功!','data'=>[]];
    }
    /**
     * 绑定身份证
     */
    public function sub_id_card_info($param,$userId)
    {
        //参数验证
        $rules = [
            'true_name' => 'require',
            'b_name' => 'require',
            'apply_address' => 'require',
            'positive_image' => 'require',
            'reverse_image' => 'require',
        ];
        $msgs = [
            'true_name.require' => '请填写真实姓名',
            'b_name.require' => '请设置水站名称',
            'apply_address.require' => '请设置详细配送地址',
            'positive_image.require' => '请上传身份证正面照片',
            'reverse_image.require' => '请上传身份证反面照片'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($param);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }
        $stationName = $param['b_name'];
        $applyAddress = $param['apply_address'];
        $trueName = $param['true_name'];
        $positiveImage = $param['positive_image'];
        $reverseImage = $param['reverse_image'];
        $data = [
            'true_name' => $trueName,
            'positive_image' => $positiveImage,
            'reverse_image' => $reverseImage,
            'status' => '0',
            'user_type' => 1,
            'b_name' => $stationName,
            'apply_address' => $applyAddress
        ];
        $res = Db::name('index_users')->where('id',$userId)->update($data);
        if(!$res){
            return ['code'=>0,'msg'=>'请勿重复提交','data'=>[]];
        }
        return ['code'=>1,'msg'=>'提交成功，后台会尽快审核哦!','data'=>[]];
    }
    /**
     * 数据统计
     */
    public function get_station_statistical($userId,$params,$page,$limit)
    {
        $findType = $params['find_type'];//0今日，1筛选
        $where = [
            'status' => ['in',['1','2','3','4','5']],
            'b_user_id' => $userId
        ];
        $hasWhere = [];
        if($findType==0){
            $nowDate = date('Y-m-d');
            // $where['createtime'] = ['between',[$nowDate.' 00:00:00',$nowDate.' 23:59:59']];
        }else{
            if(isset($params['title'])&& $params['title']){
                $hasWhere = [
                    'goods_title' => ['like','%'.$params['title'].'%']
                ];
            }
            if(isset($params['start_date']) && isset($params['end_date']) && $params['end_date'] && $params['start_date']){
                $startDate = $params['start_date'];
                $endDate = $params['end_date'];
                $where['createtime'] = ['>',$startDate.' 00:00:00'];
                $where['createtime'] = ['<',$endDate.' 23:59:59'];
            }
        }
        if(empty($hasWhere)){
            $Db = GoodsOrderModel::with(['extend','address']);
        }else{
            $Db = GoodsOrderModel::hasWhere('extend',$hasWhere)->with(['extend','address']);
        }
        $list = $Db->where($where)->page($page,$limit)->select();
        if(empty($hasWhere)){
            $Db = new GoodsOrderModel();
        }else{
            $Db = GoodsOrderModel::hasWhere('extend',$hasWhere);
        }
        $allList = $Db->where($where)->select();
        $sumOrderNums = count($allList);
        $sumOrderAmount = 0.00;
        foreach($allList as $v){
            $sumOrderAmount += $v['sum_money'];
        }
        $sumOrderAmount = round($sumOrderAmount,2);
        return ['list'=>$list,'sum_order_nums'=>$sumOrderNums,'sum_order_amount'=>$sumOrderAmount];
    }
    /**
     * 身份证认证状态
     */
    public function get_certification_status($userId)
    {
        $certificationStatus = Db::name('index_users')->where('id',$userId)->value('status');
        //状态:0=待审核,1=审核通过,2=审核拒绝
        if($certificationStatus === null){
            $certificationStatus = 3;//待提交
        }
        
        $data = ['certification_status'=>$certificationStatus];
        return $data;
    }
     /**
     * 获取个人信息--已添加至接口文档
     */
    public function get_person_info($userId)
    {
        $field = 'id,true_name,head_img,nickname,sex,status,b_name,mobile'; 
        $info = Db::name('index_users')->field($field)->where('id',$userId)->find();
        $info['mobile'] = substr_replace($info['mobile'],'****',3,4);
        //状态为2时才能进入提交页面
        //  '0' 阻止 正在审核中，请耐心等待；'1' 阻止 审核已通过
        if($info['status'] === null || !$info['b_name']){
            $info['status'] =  '2';//待提交
        }
        return $info;
    }
    /**
     * 获取实名认证信息
     */
    public function get_true_user_info($userId)
    {
        $field = 'id,positive_image,apply_address,reverse_image,b_name,true_name'; 
        $info = Db::name('index_users')->field($field)->where('id',$userId)->find();
        return $info;
    }
}
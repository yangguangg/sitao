<?php
namespace app\user\action;

use app\user\model\UserModel;
use app\user\model\UserTicketModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Validate;

class PersonAction extends Controller
{
    public $userModel;
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }
/**
     * 获取个人信息--已添加至接口文档
     */
    public function get_person_info($userId)
    {
        $field = 'id,mobile,head_img,nickname,sex,user_type,status,vip,vip_deadtime,qian_num,ya_num,ya_price';
        $info = $this->userModel->field($field)->where('id',$userId)->find();
        $info['mobile'] = substr_replace($info['mobile'],'****',3,4);
        //状态为2时才能进入提交页面
        //  '0' 阻止 正在审核中，请耐心等待；'1' 阻止 审核已通过
        if($info['status'] === null){
            $info['status'] =  '2';//待提交
        }
        $info['vip_deadtime_text'] = date('Y-m-d',$info['vip_deadtime']);
        
         $info['kaiguan'] = 2;
        return $info;
    }
    /**
     * 我的桶押金
     */
    public function get_my_barrel($userId)
    {
        $info = $this->userModel->field('used_barrel,unused_barrel')->where('id',$userId)->find();
        $barrelSums = $info['used_barrel'] + $info['unused_barrel'];
        $sumMoney = $barrelSums * 30;
        return ['barrel_sums'=>$barrelSums,'sum_money'=>$sumMoney,'kefu_phone'=>config("site.service_phone"),'barrel_price'=>floatval(config('site.barrel_price'))];
    }
    /**
     * 我的水票
     */
    public function get_my_tickets($userId,$page)
    {
        $userTicketModel = new UserTicketModel();
        $list = $userTicketModel::with('goods')
            ->field('id,goods_id,unused_tickets,used_tickets')
            ->where('c_user_id',$userId)
            ->where('unused_tickets','>',0)
            ->page($page,15)
            ->select();
        $count = $userTicketModel
            ->field('id,goods_id,unused_tickets,used_tickets')
            ->where('c_user_id',$userId)
            ->where('unused_tickets','>',0)
            ->count('id');
        return ['list'=>$list,'count'=>$count];
    }
    /**
     * 邀请好友 -- 生成海报
     */
    public function get_posters_path($userId)
    {
        
        
        $config = get_addon_config('third');
        $app_id=$config['wechat']['app_id'];
    
       
        $app = new \addons\third\library\Application($config);
        
        $platform='weapp';
        
        $width=input('width',430);
        $page=input('page','');
        if(empty($page)){
            $page='pages/index/index';
        }
        
        $content = $app->{$platform}->getUnlimited($userId,$width,$page);
        
        
       
        
        $rand = $userId;
        $wxQrCodePath = "static/qrcode/$rand.png";
        $res = file_put_contents($wxQrCodePath,$content);
        if($res){
            
            return $wxQrCodePath;
        }
    }
    
    
    
    public function get_posters_pathold($userId)
    {
        $finalFilePath = Db::name("user_qrcode")->where("user_id",$userId)->value("qrcode");
        if($finalFilePath){
            return $finalFilePath;
        }
        $access_token = get_access_token();
        // 获取二维码
        $url = $this->_getQRCode($access_token,$userId);
        //下载图片到本地
        $result = send_request($url);
        $rand = $userId;
        $wxQrCodePath = "static/qrcode/$rand.png";
        $res = file_put_contents($wxQrCodePath,$result);
        if($res){
            //最终海报路径
            $finalFilePath = 'static/posters/'.$rand.'.png';//最终海报路径
            $playbillPath = config('site.poster_img_path');
            $playbillPath = substr($playbillPath,1);
            $finalFilePath = $this->mergeImgs($playbillPath,$wxQrCodePath,$finalFilePath);
            $data = [
                "user_id"=>$userId,
                "qrcode" => $finalFilePath,
                'createtime' => date("Y-m-d H:i:s")
            ];
            Db::name("user_qrcode")->strict(false)->insert($data);
            return $finalFilePath;
        }
    }
      /**
     * @param $post_template 海报素材路径
     * @param $TDC  二维码
     * @return string
     */
    private function  mergeImgs($playbillPath,$wxQrCodePath,$finalFilePath){
        require(ROOT_PATH.'vendor/topthink/think-image/src/Image.php');
        require(ROOT_PATH.'vendor/topthink/think-image/src/image/Exception.php');
        $image = \think\Image::open($playbillPath);// 海报素材路径
        //合成
        //对二维码进行缩放
        \think\Image::open($wxQrCodePath)->thumb(300, 300)->save($wxQrCodePath);
        //放上二维码
        $image->water($wxQrCodePath, [125, 174])->save($finalFilePath);// 添加图片水印
        // $font_size = 18;
        // $ttf = './static/css/SourceHanSansCN-Regular.otf';
        //放上文字
        // $image->text('河南省 濮阳市', $ttf, $font_size, '#ffffff', [40, 150]);
        // $font_size = 12;
        // $image->text('清丰县第三实验小学', $ttf, $font_size, '#ffffff', [40, 200])->save($finalFilePath);
        return $finalFilePath;
    }
    /* 通过ticket获取二维码 */
    public function _getQRCode($access_token,$key,$sceneType = 2)
    {
        $content = json_decode($this->_getTicket($access_token,"permanent",$key,$sceneType));
        $ticket=$content->ticket;
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        return $url;
    }
    /*
     * 获取ticket
     * expire_seconds:二维码有效期（秒）
     * type：二维码类型（临时或永久）
     * scene:场景编号
     */
    public function _getTicket($access_token,$type="temp",$scene='',$sceneType = 1,$expire_seconds=604800)
    {
        if($type=="temp"){
            $data='{"expire_seconds": '.$expire_seconds.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_str": '.$scene.'}}}';
            return send_request("https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token,$data);
        }else
        {
            if($sceneType == 1){
                $data = "{\"action_name\": \"QR_LIMIT_STR_SCENE\", \"action_info\": {\"scene\": {\"scene_str\": \"$scene\"}}}";
            }else{
                $data = "{\"action_name\": \"QR_LIMIT_SCENE\", \"action_info\": {\"scene\": {\"scene_id\": $scene}}}";
            }
            return send_request("https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token,$data);
        }
    }
    public function my_money($userId)
    {
        $money = Db::name('index_users')->where('id',$userId)->value('now_money');
        $lowestWithdrawal = config('site.lowest_withdrawal');
        $highestWithdrawal = config('site.highest_withdrawal');
        $commissionRatio = config('site.commission_ratio');
        $alipayHistoryInfo = Db::name('withdrawal_record')->field('account,true_name')->where('c_user_id',$userId)->where('get_way',0)->order('id','desc')->find();
        $bankHistoryInfo = Db::name('withdrawal_record')->field('account,true_name,bank_name')->where('c_user_id',$userId)->where('get_way',1)->order('id','desc')->find();
        $info = [
            'money'=>$money,
            'lowest_withdrawal' => $lowestWithdrawal,
            'highest_withdrawal' => $highestWithdrawal,
            'commission_ratio' => $commissionRatio,
            'alipay_history_info' => $alipayHistoryInfo,
            'bank_history_info' => $bankHistoryInfo
        ];
        return $info;
    }
    /**
     * 提现操作
     */
    public function get_money_action($userId,$params)
    {
        //参数校验
        $rules = [
            'get_way' => 'require',
            'account' => 'require',
            'true_name' => 'require',
        ];
        $msgs = [
            'get_way.require' => '请选择提现方式',
            'account.require' => '请输入账号',
            'true_name.require' => '请输入真实姓名'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($params);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }
        if($params['get_way']==1 && !$params['bank_name']){
            return ['code'=>0,'msg'=>'请输入开户行名称','data'=>[]];
        }
        $actMoney = $params['act_money'];
        //余额校验
        $money = Db::name('index_users')->where('id',$userId)->value('now_money');
        if($actMoney > $money){
            return ['code'=>0,'msg'=>'提现金额超出账户余额','data'=>[]];
        }
        //记录校验
        //01.单日只能提现一次
        $date = date('Y-m-d');
        $startTime = strtotime($date.' 00::00:01');
        $endTime = strtotime($date.' 23:59:59');
        $where['c_user_id'] = $userId;
        $record = Db::name('withdrawal_record')
                    ->where($where)
                    ->whereBetween('createtime',[$startTime,$endTime])
                    ->find();
        if($record){
            return ['code'=>0,'msg'=>'单日只能提现一次','data'=>[]];
        }
        //02.是否有待审核记录
        $where['status'] = '0';
        $record = Db::name('withdrawal_record')
                    ->where('c_user_id',$userId)
                    ->where($where)
                    ->find();
        if($record){
            return ['code'=>0,'msg'=>'您有提现申请正在审核哦','data'=>[]];
        }
        //提现校验
        if($actMoney <= 0){
            return ['code'=>0,'msg'=>'请输入正确的提现金额','data'=>[]];
        }
        $lowestWithdrawal = floatval(config('site.lowest_withdrawal'));
        $highestWithdrawal = floatval(config('site.highest_withdrawal'));
        $commissionRatio = 1-(floatval(config('site.commission_ratio'))/100);
        if($actMoney < $lowestWithdrawal){
            return ['code'=>0,'msg'=>'提现金额没有达到最低门槛','data'=>[]];
        }
        if($actMoney > $highestWithdrawal){
            return ['code'=>0,'msg'=>'单次提现金额超过最高门槛','data'=>[]];
        }
        //数据库操作
        $data = [
            'act_money' => $actMoney,
            'c_user_id' => $userId,
            'createtime' => date("Y-m-d H:i:s"),
            'final_money' => round($actMoney*$commissionRatio,2),
            'get_way' => $params['get_way'],
            'account' => $params['account'],
            'true_name' => $params['true_name'],
            'bank_name' => $params['bank_name']
        ];
        $res = Db::name('withdrawal_record')->strict(false)->insert($data);
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'后台会尽快审核哦，请耐心等待','data'=>[]];
    }
    /**
     * 我的--分销中心--邀请记录
     */
    public function get_invited_records($userId,$page,$limit)
    {
        $list = Db::name('user_subscribe')->alias('a')
            ->join('index_users b','a.user_id = b.id','left')
            ->field('a.red_bag,b.head_img,b.nickname')
            ->where('a.parent_user_id',$userId)
            ->page($page,$limit)
            ->select();
        return $list;
    }
    /**
     * 我的--分销中心--展示
     */
    public function get_account_info($userId)
    {
        $info = Db::name('index_users')->field('now_money,add_money')->where('id',$userId)->find();
        $info['invitation_img'] = cdnurl(config('site.invitation_img'),true);
        return $info;
    }
    /**
     * 我的--分销中心--提现记录
     */
    public function get_withdrawal_record($userId,$page,$limit)
    {
        $list = Db::name('withdrawal_record')
                    ->where('c_user_id',$userId)
                    ->page($page,$limit)
                    ->select();
        return $list;
    }
    /**
     * 领取优惠券(首页领取,并非领券中心)
     */
    public function get_my_coupons($userId,$couponsId)
    {
        return;
        if(!$couponsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $where = [
            'c_user_id' => $userId,
            'type' => 1
        ];
        $record = Db::name('user_coupons')->where($where)->find();
        if($record){
            return ['code'=>0,'msg'=>'已领取','data'=>[]];
        }
        $couponsInfo = Db::name('coupons')->where('id',$couponsId)->find();
        $effective = intval(config('site.new_coupons_effective_time'));
        $nowTime = time();
        $endTime = $nowTime+$effective*24*60*60;
        $endDate = date("Y-m-d",$endTime);
        $data = [
            'c_user_id' => $userId,
            'coupons_value' => $couponsInfo['coupons_value'],
            'threshold_price' => $couponsInfo['threshold_price'],
            'createtime' => date("Y-m-d H:i:s"),
            'end_time' => $endDate,
            'type' => 1
        ];
        $res = Db::name('user_coupons')->strict(false)->insert($data);
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'领取成功','data'=>[]];
    }
    /**
     * 修改手机号
     */
    public function change_mobile($newMobile,$captcha,$userId)
    {
        if(!$newMobile){
            return ['code'=>0,'msg'=>'请输入新手机号','data'=>[]];
        }
        if(!$captcha){
            return ['code'=>0,'msg'=>'请输入验证码','data'=>[]];
        }
        $event = 'login';
        //校验手机验证码
        $smsAction = new SmsAction();
        $res = $smsAction->check($newMobile,$event,$captcha);
        if($res['code']==0){
            return $res;
        }
        $result = Db::name("index_users")->where('id',$userId)->update(['mobile'=>$newMobile]);
        if($result===0){
            return ['code'=>0,'msg'=>'请使用新的手机号','data'=>[]];
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
}
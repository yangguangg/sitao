<?php

namespace app\admin\model;

use app\admin\model\water\Users;
use think\Db;
use think\Exception;
use think\Model;


class YaBucket extends Model
{


    // 表名
    protected $name = 'ya_bucket';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'paytime_text',
        'tuitime_text',
        'confirmtime_text',
        'completetime_text',
        'canceltime_text'
    ];


    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5'), '6' => __('Status 6')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPaytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paytime']) ? $data['paytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTuitimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['tuitime']) ? $data['tuitime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getConfirmtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['confirmtime']) ? $data['confirmtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCompletetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['completetime']) ? $data['completetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCanceltimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['canceltime']) ? $data['canceltime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPaytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setTuitimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setConfirmtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCompletetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCanceltimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\IndexUsers', 'index_users_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function bucketcate()
    {
        return $this->belongsTo('BucketCate', 'bucket_cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function water()
    {
        return $this->belongsTo('app\admin\model\users\Water', 'water_id', 'id', [], 'LEFT')->where([''])->setEagerlyType(0);
    }


    public function creatordersn()
    {
        $order_sn = 'YB_' . date('YmdHis') . mt_rand(1000, 9999);
        $model = new self();
        $r = $model->where(['order_sn' => $order_sn])->count();
        if ($r > 0) {
            return $model->creatordersn();
        }
        return $order_sn;
    }

    /**
     * 水站创建押桶
     */
    public function create_ya($water_id, $mobile, $bucket_cate_id, $num, $price)
    {
        $model = new self();
        $order_sn = $model->creatordersn();
        Db::startTrans();
        try {
            $userModel = new Users();
            $where = [
                'mobile' => $mobile,
            ];
            $user = $userModel->where($where)->find();
            if (empty($user)) {
                throw new Exception('未查询到该用户');

            }
            $now = time();
            $data = [
                'order_sn' => $order_sn,
                'bucket_cate_id' => $bucket_cate_id,
                'num' => $num,
                'total_num' => $num,
                'mobile' => $mobile,
                'total_price' => $price,
                'price' => $price,
                'per_price' => round($price / $num, 2),
                'index_users_id' => $user['id'],
                'water_id' => $water_id,
                'createtime' => $now,
                'status' => 1,
            ];
            $r = $model->insert($data);

            if (!$r) {
                throw new Exception('添加押桶失败，请稍后失败');
            }

        } catch (Exception $exception) {
            Db::rollback();
            return [
                'code' => 404,
                'msg' => $exception->getMessage(),
            ];
        }
        Db::commit();
        return [
            'code' => 200,
            'msg' => '押桶成功',
        ];
    }

    /**
     * 水站取消押桶
     */
    public function cancel_ya($water_id, $order_sn)
    {
        $model = new self();
        $where = [
            'water_id' => $water_id,
            'order_sn' => $order_sn,
            'status' => 1,
        ];
        $info = $model->where($where)->find();
        if (empty($info)) {
            return [
                'code' => 404,
                'msg' => '未查询到该押桶信息或该押桶信息已不能取消',
            ];
        }
        $now = time();
        $data = [
            'status' => 6,
            'canceltime' => $now,
        ];
        $r = $info->save($data);
        if (!($r)) {
            return [
                'code' => 404,
                'msg' => '操作失败，请稍后重试',
            ];
        } else {
            return [
                'code' => 200,
                'msg' => '操作成功',
            ];
        }
    }

    /**
     * 用户支付押桶
     */
    public function pay_ya($order_sn)
    {
        $model = new self();
        $where = [
            'order_sn' => $order_sn,
            'status' => 1,
        ];
        $info = $model->where($where)->find();
        if (empty($info)) {
            return [
                'code' => 404,
                'msg' => '未查询到该押桶信息或该押桶信息已不能支付',
            ];
        }
//        if ($info['total_price'] != $money) {
//            return [
//                'code' => 404,
//                'msg' => '金额有误',
//            ];
//        }
        Db::startTrans();
        try {
            $now = time();
            $data = [
                'status' => 2,
                'paytime' => $now,
                'updatetime' => $now,
            ];
            $r = $info->save($data);
            if (!$r) {
                throw new Exception('操作失败，请稍后重试');
            }
            $userModel = new Users();
//            $where = [
//                'id' => $info['index_users_id'],
//            ];
//            $r = $userModel->where($where)->setInc('ya_num', $info['num']);
//            if (!$r) {
//                throw new Exception('操作失败，请稍后重试');
//            }
            $reportModel = new YaBucketReport();
            $allreportModel = new YaBucketAllreport();
            $bucket_cate_id = $info['bucket_cate_id'];
            $index_users_id = $info['index_users_id'];
            $water_id = $info['water_id'];
            $num = $info['num'];
            $mobile = $info['mobile'];

            $userModel = new Users();
            $where = [
                'mobile' => $mobile,
            ];
            $user = $userModel->where($where)->find();
            $where = [
                'bucket_cate_id' => $bucket_cate_id,
                'index_users_id' => $index_users_id,
                'water_id' => $water_id,
            ];
            $report = $reportModel->where($where)->find();
            $ndata = [
                'totalnum' => (empty($report) ? $num : (($report['num'] - $report['hai_num'] + $num))),
            ];

            $r = $info->save($ndata);
            if (!$r) {
                throw new Exception('操作失败，请稍后重试');
            }
            $where1 = [
                'index_users_id' => $index_users_id,
                'water_id' => $water_id,
            ];
            $allreport = $allreportModel->where($where1)->find();

            //增加
            if (empty($report)) {
                $new_data = [
                    'bucket_cate_id' => $bucket_cate_id,
                    'index_users_id' => $index_users_id,
                    'num' => $num,
                    'hai_num' => 0,
                    'left_num' => $num,
                    'mobile' => $mobile,
                    'water_id' => $water_id,
                    'createtime' => $now,
                    'updatetime' => $now,
                ];
                $r = $reportModel->insert($new_data);
                if(empty($allreport)){
                    $new_data1 = [
                        'index_users_id' => $index_users_id,
                        'num' => $num,
                        'hai_num' => 0,
                        'left_num' => $num,
                        'mobile' => $mobile,
                        'water_id' => $water_id,
                        'createtime' => $now,
                        'updatetime' => $now,
                    ];
                    $r1 = $allreportModel->insert($new_data1);
                    if (!$r || !$r1) {
                        throw new Exception('押桶失败，请稍后失败');
                    }
                }else{
                    $new_data1 = [
                        'num' => $allreport['num'] + $num,
                        'left_num' => $allreport['left_num'] + $num,
                        'updatetime' => $now,
                    ];
                    $r1 = $allreport->save($new_data1);
                    if (!$r || !$r1) {
                        throw new Exception('押桶失败，请稍后失败');
                    }
                }
            } else {
                $new_data = [
                    'num' => $report['num'] + $num,
                    'left_num' => $report['left_num'] + $num,
                    'updatetime' => $now,
                ];
                $r = $report->save($new_data);
                $new_data1 = [
                    'num' => $allreport['num'] + $num,
                    'left_num' => $allreport['left_num'] + $num,
                    'updatetime' => $now,
                ];
                $r1 = $allreport->save($new_data1);
                if (!$r || !$r1) {
                    throw new Exception('押桶失败，请稍后失败');
                }
            }
            //支付的时候再做此操作
            $r = $user->setInc('ya_num', $num);
            $r1 = $user->setInc('ya_price', $info['total_price']);
            if (!$r || !$r1) {
                throw new Exception('押桶失败，请稍后失败');
            }
        } catch (Exception $exception) {
            Db::rollback();
            return [
                'code' => 404,
                'msg' => '操作失败，请稍后重试',
            ];
        }
        Db::commit();
        return [
            'code' => 200,
            'msg' => '操作成功',
        ];

        if (!($r)) {
            return [
                'code' => 404,
                'msg' => '操作失败，请稍后重试',
            ];
        } else {
            return [
                'code' => 200,
                'msg' => '操作成功',
            ];
        }
    }

    /**
     * 用户申请退桶
     */
    public function back_ya($user_id, $order_sn, $aliname, $aliaccount, $num)
    {
        Db::startTrans();
        try {
            if (empty($aliname)) {
                throw new Exception('请填写退款支付宝姓名');
            }
            if (empty($aliaccount)) {
                throw new Exception('请填写退款支付宝账号');
            }
            $model = new self();
            $where = [
                'index_users_id' => $user_id,
                'order_sn' => $order_sn,
            ];
            $info = $model->where($where)->find();
            if (empty($info)) {
                throw new Exception('该笔押桶不存在，请核实后再操作');
            }
            if ($info['status'] != 2) {
                throw new Exception('该笔押桶不能进行退桶操作');
            }
            if ($info['num'] < $num) {
                throw new Exception('退桶数量不能大于剩余押桶数量');
            }
            $now = time();
            $new_data = [
                'num' => $info['num'] - $num,
                'price' => ($info['per_price'] * ($info['num'] - $num)),
                'tuitime' => $now,
                'updatetime' => $now,
                'status' => 3,
            ];
            $r = $info->save($new_data);
            if (!$r) {
                throw new Exception('退桶申请失败，请稍后重试');
            }
            //增加退桶申请记录
            $tuiModel = new YaBucketBack();
            $where = [
                'order_sn' => $order_sn,
                'bucket_cate_id' => $info['bucket_cate_id'],
                'status' => ['in', [1, 2]],
            ];
            $count = $tuiModel->where($where)->count();
            if ($count > 0) {
                throw new Exception('退桶申请失败，请稍后重试2');
            }
            $data = [
                'order_sn' => $order_sn,
                'bucket_cate_id' => $info['bucket_cate_id'],
                'num' => $num,
                'price' => ($num * $info['per_price']),
                'mobile' => $info['mobile'],
                'aliname' => $aliname,
                'aliaccount' => $aliaccount,
                'index_users_id' => $info['index_users_id'],
                'water_id' => $info['water_id'],
                'status' => 1,
                'createtime' => $now,
                'updatetime' => $now,
            ];
            $r = $tuiModel->insert($data);
            if (!$r) {
                throw new Exception('退桶申请失败，请稍后重试1');
            }
        } catch (Exception $exception) {
            Db::rollback();
            return [
                'code' => 404,
                'msg' => $exception->getMessage(),
            ];
        }
        Db::commit();
        return [
            'code' => 200,
            'msg' => '退桶申请成功',
        ];
    }

    /**
     * 水站同意退桶
     */
    public function agree_ya($water_id, $order_sn, $price = 0,$remark='')
    {

        Db::startTrans();
        try {
            $model = new self();
            $where = [
                'water_id' => $water_id,
                'order_sn' => $order_sn,
            ];
            $info = $model->where($where)->find();
            if (empty($info)) {
                throw new Exception('该笔押桶不存在，请核实后再操作');
            }
            if ($info['status'] != 3) {
                throw new Exception('该笔押桶不能进行退桶操作');
            }
            // if ($price > 0 && $info['price'] < $price) {
            //     throw new Exception('退桶金额不能大于剩余押桶金额');
            // }
            $tuiModel = new YaBucketBack();
            $tui = $tuiModel->where(['order_sn' => $order_sn])->order('id desc')->find();
            if (empty($tui) || $tui['status'] != 1) {
                throw new Exception('退桶申请不存在');
            }
            $now = time();
            $data = [
                'price' => ($price > 0 ? $price : $tui['price']),
                'status' => 2,
                'confirmtime' => $now,
                'remark' => $remark,
            ];
            $r = $tui->save($data);
            if (!$r) {
                throw new Exception('处理失败，请稍后重试');
            }
            $new_data = [
//                'num' => ($info['num'] - $tui['num']),
                'confirmtime' => $now,
                'status' => 4,
            ];
            $r = $info->save($new_data);
            if (!$r) {
                throw new Exception('处理失败，请稍后重试');
            }

            $userModel = new Users();
            $where = [
                'mobile' => $info['mobile'],
            ];
            $user = $userModel->where($where)->find();
            //支付的时候再做此操作
            $r = $user->setDec('ya_num', $tui['num']);
            $r1 = $user->setDec('ya_price', ($tui['num']*$info['per_price']));
            if (!$r || !$r1) {
                throw new Exception('处理失败，请稍后重试');
            }
        } catch (Exception $exception) {
            Db::rollback();
            return [
                'code' => 404,
                'msg' => $exception->getMessage(),
            ];
        }
        Db::commit();
        return [
            'code' => 200,
            'msg' => '处理成功',
        ];
    }

    /**
     * 后台确认打款完成
     * @param $water_id
     * @param $order_sn
     * @param int $price
     */
    public function dakuan_ya($order_sn)
    {

        Db::startTrans();
        try {
            $model = new self();
            $where = [
                'order_sn' => $order_sn,
            ];
            $info = $model->where($where)->find();
            if (empty($info)) {
                throw new Exception('该笔押桶不存在，请核实后再操作');
            }
            if ($info['status'] != 4) {
                throw new Exception('该笔押桶不能进行退桶操作');
            }

            $tuiModel = new YaBucketBack();
            $tui = $tuiModel->where(['order_sn' => $order_sn])->order('id desc')->find();
            if (empty($tui) || $tui['status'] != 2) {
                throw new Exception('退桶申请不存在');
            }
            $now = time();
            $data = [
                'status' => 3,
                'confirmtime' => $now,
            ];
            $r = $tui->save($data);
            if (!$r) {
                throw new Exception('处理失败，请稍后重试');
            }
            $new_data = [
                'confirmtime' => $now,
                'status' => ($info['num'] > 0 ? 2 : 5),
            ];
            $r = $info->save($new_data);
            if (!$r) {
                throw new Exception('处理失败，请稍后重试');
            }
        } catch (Exception $exception) {
            Db::rollback();
            return [
                'code' => 404,
                'msg' => $exception->getMessage(),
            ];
        }
        Db::commit();
        return [
            'code' => 200,
            'msg' => '确认成功',
        ];
    }
}

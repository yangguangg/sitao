<?php

namespace app\admin\model;

use app\admin\model\water\Users;
use think\Db;
use think\Exception;
use think\Model;


class QianBucket extends Model
{


    // 表名
    protected $name = 'qian_bucket';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'createtime_text'
    ];


    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getCreatetimeTextAttr($value, $data)
    {
       return date('Y-m-d H:i:s', $data['createtime']);
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\IndexUsers', 'index_users_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function water()
    {
        return $this->belongsTo('app\admin\model\users\Water', 'water_id', 'id', [], 'LEFT')->where([''])->setEagerlyType(0);
    }


    public function bucketcate()
    {
        return $this->belongsTo('BucketCate', 'bucket_cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    /**
     * 增加欠桶
     * @param $water_id
     * @param $mobile
     * @param $bucket_cate_id
     * @param $num
     * @param $type 1增加 2减少
     */
    public function handle_qian($water_id, $mobile, $bucket_cate_id, $num, $type = 1)
    {
        $str = '增加';
        if ($type == 2) {
            $str = '减少';
        }
        Db::startTrans();
        try {
            $bucketcateModel = new BucketCate();

            if($num <= 0){
                throw new Exception('桶数必须大于0');
            }
            $cate_map = [
                'id' => $bucket_cate_id,
                'status' => 1,
            ];
            $b_count = $bucketcateModel->where($cate_map)->count();
            if($b_count <= 0){
                throw new Exception('您所选的桶类型不存在');
            }
            $model = new self();
            $userModel = new Users();
            $where = [
                'mobile' => $mobile,
            ];
            $user = $userModel->where($where)->find();
            if (empty($user)) {
                throw new Exception('未查询到该用户');

            }
            $now = time();

            $reportModel = new QianBucketReport();
            $allreportModel = new QianBucketAllreport();
            $where = [
                'bucket_cate_id' => $bucket_cate_id,
                'index_users_id' => $user['id'],
                'water_id' => $water_id,
            ];
            $report = $reportModel->where($where)->find();
            $data = [
                'bucket_cate_id' => $bucket_cate_id,
                'num' => $num,
                'mobile' => $mobile,
                'index_users_id' => $user['id'],
                'water_id' => $water_id,
                'type' => $type,
//                'totalnum' => (empty($report) ? $num : (($report['num'] - $report['hai_num'] - $num))),
                'createtime' => $now
            ];
            if($type == 1){
                $data['totalnum'] = (empty($report) ? $num : (($report['num'] - $report['hai_num'] + $num)));
            }else{
                $data['totalnum'] = (empty($report) ? $num : (($report['num'] - $report['hai_num'] - $num)));
            }
            $r = $model->insert($data);

            if (!$r) {
                throw new Exception($str . '欠桶失败，请稍后失败');
            }
            $where1 = [
                'index_users_id' => $user['id'],
                'water_id' => $water_id,
            ];
            $allreport = $allreportModel->where($where1)->find();
            if ($type == 2) {
                if (empty($report) || $report['left_num'] < $num) {
                    throw new Exception('减桶超出剩余欠桶数');
                }
                //减少
                $new_data = [
                    'hai_num' => $report['hai_num'] + $num,
                    'left_num' => ($report['num'] - $report['hai_num'] - $num),
                    'updatetime' => $now,
                ];
                $r = $report->save($new_data);
                $new_data1 = [
                    'hai_num' => $allreport['hai_num'] + $num,
                    'left_num' => ($allreport['num'] - $allreport['hai_num'] - $num),
                    'updatetime' => $now,
                ];
                $r1 = $allreport->save($new_data1);
                if (!$r || !$r1) {
                    throw new Exception($str . '欠桶失败，请稍后失败');
                }
                $r = $user->setDec('qian_num',$num);
                if (!$r) {
                    throw new Exception($str . '欠桶失败，请稍后失败');
                }
            } else {
                //增加
                if (empty($report)) {
                    $new_data = [
                        'bucket_cate_id' => $bucket_cate_id,
                        'index_users_id' => $user['id'],
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
                            'index_users_id' => $user['id'],
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
                            throw new Exception($str . '欠桶失败，请稍后失败');
                        }
                    }else{
                        $new_data1 = [
                            'num' => $allreport['num'] + $num,
                            'left_num' => ($allreport['left_num'] + $num),
                            'updatetime' => $now,
                        ];
                        $r1 = $allreport->save($new_data1);
                        if (!$r || !$r1) {
                            throw new Exception($str . '欠桶失败，请稍后失败');
                        }
                    }
                } else {
                    $new_data = [
                        'num' => $report['num'] + $num,
                        'left_num' => ($report['left_num'] + $num),
                        'updatetime' => $now,
                    ];
                    $r = $report->save($new_data);
                    $new_data1 = [
                        'num' => $allreport['num'] + $num,
                        'left_num' => ($allreport['left_num'] + $num),
                        'updatetime' => $now,
                    ];
                    $r1 = $allreport->save($new_data1);
                    if (!$r || !$r1) {
                        throw new Exception($str . '欠桶失败，请稍后失败');
                    }
                }
                $r = $user->setInc('qian_num',$num);
                if (!$r) {
                    throw new Exception($str . '欠桶失败，请稍后失败'.$user->getLastSql());
                }

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
            'msg' => $str.'欠桶成功',
        ];

    }
}

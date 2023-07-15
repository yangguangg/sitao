<?php

namespace app\admin\model\goods;

use think\Db;
use think\Model;
use traits\model\SoftDelete;

class Tickets extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'goods_tickets';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }

    public function getIsHotList()
    {
        return ['0' =>"否", '1' =>"是"];
    }
    public function getGoodsList()
    {
        $where = [
            'deletetime' => null,
            'cate_id' => 1
        ];
        return Db::name("goods")->field('title,id')->where($where)->select();
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function goods()
    {
        return $this->belongsTo('app\admin\model\goods\Goods', 'goods_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * vip增票
     * @param $user_id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function vip_ticket($user_id){
        $where = [
            'status' => '3',
            'exp' => Db::raw('deletetime is null'),
        ];
        $list = Db::name('goods_tickets')->where($where)->select();
        foreach($list as $vo){
            $where = [
                'c_user_id' => $user_id,
                'goods_id' => $vo['goods_id']
            ];
            $record = Db::name('user_tickets')->where($where)->find();
            $gettingNums = $vo['give_nums'];
            if($record){
                Db::name('user_tickets')->where($where)->setInc('unused_tickets',$gettingNums);
                Db::name('user_tickets')->where($where)->update(['updatetime'=>date('Y-m-d H:i:s')]);
            }else{
                $data = [
                    'goods_id'=>$vo['goods_id'],
                    'unused_tickets' => $gettingNums,
                    'c_user_id' => $user_id,
                    'updatetime' => date('Y-m-d H:i:s')
                ];
                Db::name('user_tickets')->strict(false)->insert($data);
            }
        }

    }
}

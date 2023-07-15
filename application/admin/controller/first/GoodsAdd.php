<?php

namespace app\admin\controller\first;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;

/**
 * 商品库存申请管理
 *
 * @icon fa fa-circle-o
 */
class GoodsAdd extends Backend
{
    
    /**
     * GoodsAdd模型对象
     * @var \app\admin\model\first\GoodsAdd
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\first\GoodsAdd;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $addId = input('add_id');
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $bUserId = Db::name("b_goods_add")->where('id',$addId)->value('b_user_id');
            $list = $this->model
                    ->with(['goods'])
                    ->where($where)
                    ->where('goods_add.b_user_id',$bUserId)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','add_num','status','goods_id','b_user_id']);
                $row->visible(['goods']);
				$row->getRelation('goods')->visible(['title','sale_price','image']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 批量审核通过
     */
    public function sure($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $list = $this->model->where('id', 'in', $ids)->select();
            Db::startTrans();
                try {
                    foreach ($list as $k => $v) {
                if($v['status']=='0'){
                    $where = [
                        'b_user_id' => $v['b_user_id'],
                        'goods_id' => $v['goods_id']
                    ];
                    $record = Db::name("b_goods")
                        ->where($where)
                        ->find();
                    if($record){
                        Db::name("b_goods")
                            ->where($where)
                            ->setInc('goods_nums',$v['add_num']);
                        Db::name("b_goods")
                            ->where($where)
                            ->update(['updatetime'=>time()]);
                    }else{
                        $data = [
                            'b_user_id' => $v['b_user_id'],
                            'goods_id' => $v['goods_id'],
                            'goods_nums' => $v['add_num'],
                            'updatetime' => time()
                        ];
                        Db::name("b_goods")->strict(false)->insert($data);
                    }
                    //减少总库存
                    Db::name('goods')->where('id',$v['goods_id'])->setDec('goods_nums',$v['add_num']);
                    $v->status = '1';
                    $v->save();
                }
            }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
            
            $this->success('操作成功');
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}

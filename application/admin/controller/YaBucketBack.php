<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 押桶退桶记录
 *
 * @icon fa fa-circle-o
 */
class YaBucketBack extends Backend
{
    
    /**
     * YaBucketBack模型对象
     * @var \app\admin\model\YaBucketBack
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\YaBucketBack;
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
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['indexusers','bucketcate','yabucket','water'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                
                $row->getRelation('indexusers')->visible(['nickname']);
				$row->getRelation('bucketcate')->visible(['name']);
				$row->getRelation('yabucket')->visible(['order_sn']);
                $row->getRelation('water')->visible(['nickname','b_name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function dakuan(){
        $ids = input('ids');
        $info = $this->model->where(['id'=>$ids])->find();
        if(empty($info)){
            $this->error('未查询到信息');
        }
        $yaModel = new \app\admin\model\YaBucket();
        $r = $yaModel->dakuan_ya($info['order_sn']);
        if($r['code'] == 404){
            $this->error($r['msg']);
        }else{
            $this->success($r['msg']);
        }

    }

}

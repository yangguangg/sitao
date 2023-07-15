<?php

namespace app\admin\controller\goods;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use Exception;
use think\cache\driver\Redis;

/**
 * 秒杀商品管理
 *
 * @icon fa fa-circle-o
 */
class Kill extends Backend
{
    
    /**
     * Kill模型对象
     * @var \app\admin\model\goods\Kill
     */
    protected $model = null;
    protected $redisObj;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\goods\Kill;
        $this->view->assign("statusList", $this->model->getStatusList());
        $options = [
            'host'       => '127.0.0.1',
            'port'       => 6379
        ];
        $this->redisObj = new Redis($options);
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
                    ->with(['goods'])
                    ->where($where)
                    ->order("end_time","desc")
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','nums','start_time','end_time','kill_price','status']);
                $row->visible(['goods']);
				$row->getRelation('goods')->visible(['title','image']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $goodsId = $params['goods_id'];
                    //该商品活动结束前只能添加一次
                    $nowTime = date("Y-m-d H:i:s");
                    $where = [
                        'start_time' => ['<',$nowTime],
                        'end_time' => ['>',$nowTime],
                        'goods_id' => $goodsId
                    ];
                    $record = $this->model->get($where);
                    if($record){
                        $finalResult = ['code'=>0,'msg'=>'活动结束前，该商品只能添加一次哦'];
                    }else{
                        $id = Db::name('goods_seconds_kill')->strict(false)->insertGetId($params);
                        if($id){
                            $finalResult = ['code'=>1,'msg'=>'添加成功'];
                            //判断上架状态，存入redis队列
                            $endDate = $params['end_time'];
                            $endTime = strtotime($endDate);
                            $nowTime = time();
                            if($params['status']=='0' && $endTime > $nowTime){
                                $nums = $params['nums'];
                                $this->inc_list($nums,$id,$params['end_time']);
                            }
                        }else{
                            $finalResult = ['code'=>0,'msg'=>__('No rows were inserted')];
                        }
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($finalResult['code'] === 1) {
                    $this->success();
                } else {
                    $this->error($finalResult['msg']);
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * 追加队列
     */
    private function inc_list($nums,$killGoodsId,$endDate)
    {
        $name = 'goods_store_'.$killGoodsId;
        $data = $this->redisObj->get($name);
        if(!$data){
            $data = [];
        }
        for($i =0;$i<$nums;$i++){
            $data[] = 1;
        }
        $nowTime = time();
        $endTime = strtotime($endDate);
        $this->redisObj->set($name,$data,$endTime-$nowTime);
    }
    /**
     * 减少队列
     */
    private function dec_list($nums,$killGoodsId,$endDate)
    {
        $name = 'goods_store_'.$killGoodsId;
        $data = $this->redisObj->get($name);
        if(is_array($data)){
            for($i =0;$i<$nums;$i++){
                array_pop($data);
            }
            $nowTime = time();
            $endTime = strtotime($endDate);
            $this->redisObj->set($name,$data,$endTime-$nowTime);
        }
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $endDate = $params['end_time'];
                    $endTime = strtotime($endDate);
                    $nowTime = time();
                    if($endTime > $nowTime && $params['status']=='0'){
                        if($row['status'] == '0' && strtotime($row['end_time']) > $nowTime){
                            if($params['nums'] > $row['nums']){
                                $incNums = $params['nums'] - $row['nums'];
                                $this -> inc_list($incNums,$ids,$params['end_time']);
                            }elseif($params['nums'] < $row['nums']){
                                $decNums = $row['nums'] - $params['nums'];
                                $this -> dec_list($decNums,$ids,$params['end_time']);
                            }
                        }else{
                            $nums = $params['nums'];
                            $this->inc_list($nums,$ids,$params['end_time']);
                        }
                    }else{
                        $name = 'goods_store_'.$ids;
                        $this -> redisObj->set($name,null);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}

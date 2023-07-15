<?php
namespace app\user\model;

use think\Db;
use think\Model;

class GoodsModel extends Model
{
    protected $name = 'goods';
    protected $append = ['tag_arr'];
    public function getTagArrAttr($value,$data)
    {
        $tagArr = [];
        if(isset($data['tag_ids']) && $data['tag_ids']){
            $tagArr = Db::name('goods_tags')->where('id','in',$data['tag_ids'])->column('tag_name');
        }
        if(isset($data['is_avoid']) && $data['is_avoid']=='1'){
            $tagArr[] = '免押金';
        }
        if(isset($data['is_intro']) && $data['is_intro']=='1'){
            $tagArr[] = '热销';
        }
        if(count($tagArr)>4){
            $tagArr = [$tagArr[0],$tagArr[1],$tagArr[2],$tagArr[3]];
        }
        return $tagArr;
    }

    public function tickets()
    {
        return $this->hasMany('app\admin\model\goods\Tickets', 'goods_id', 'id');
    }
    public function getImageAttr($value,$data)
    {
        $imageUrl = '';
        if(isset($data['image']) && $data['image']){
            $imageUrl = cdnurl($data['image'],true);
        }
        return $imageUrl;
    }
    public function getImagesAttr($value,$data)
    {
        $imagesArr = [];
        if(isset($data['images']) && $data['images']){
            $imagesArr = explode(',',$data['images']);
            foreach($imagesArr as &$v){
                $v = cdnurl($v,true);
            }
        }
        return $imagesArr;
    }
}
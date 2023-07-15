<?php
namespace app\user\model;

use think\Model;

class CommentModel extends Model
{
    protected $name = 'user_comment';
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
    public function users()
    {
        return $this->belongsTo('UserModel','c_user_id','id')->field('id,head_img,nickname');
    }
}
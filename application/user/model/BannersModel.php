<?php
namespace app\user\model;

use think\Model;

class BannersModel extends Model
{
    protected $name = 'index_banners';
    /**
     * 轮播图路径转换
     */
    public function getBannerImageAttr($value,$data)
    {
        $url = '';
        if(isset($data['banner_image']) && $data['banner_image']){
            $url = cdnurl($data['banner_image'],true);
        }
        return $url;
    }
}
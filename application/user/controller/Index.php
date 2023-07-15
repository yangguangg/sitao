<?php
namespace app\user\controller;

use app\common\controller\Publics;
use app\user\action\IndexAction;
use app\common\library\Upload;
use app\common\exception\UploadException;
use OSS\OssClient;

class Index extends Publics
{
    private $indexAction;
    public function __construct()
    {
        parent::__construct();
        $this->indexAction = new IndexAction();
    }
    /**
     * 首页轮播图--已添加至接口文档
     */
    public function get_banners_list()
    {
        $list = $this->indexAction->get_banners_list();
        return commonReturnSuccess($list);
    }
    /**
     * 轮播图详情--已添加至接口文档
     */
    public function get_banner_detail()
    {
        $bannerId = input('banner_id');
        $res = $this->indexAction->get_banner_detail($bannerId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 获取商品分类--已添加至接口文档
     */
    public function get_goods_cates()
    {
        $list = $this->indexAction->get_goods_cates();
        return commonReturnSuccess($list);
    }
    /**
     * 获取商品列表--热销和所有--已添加至接口文档
     */
    public function get_goods_list()
    {
        $page = input('page',1);
        $limit = input('limit',10);
        $token = input('token','');
        $cateId = input('cate_id',0);
        $isIntro = input('is_intro',0);

        $list = $this->indexAction->get_goods_list($page,$limit,$token,$cateId,$isIntro,$this->vip);
        return commonReturnSuccess($list);
    }
    /**
     * 获取商品详情--已添加至接口文档
     */
    public function get_goods_detail()
    {
        $goodsId = input('goods_id');
        $token = input('token','');
        $res = $this->indexAction->get_goods_detail($goodsId,$token,$this->vip);//$this->vip
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    /**
     * 获取评论列表--已添加至接口文档
     */
    public function get_goods_comments()
    {
        $goodsId = input('goods_id');
        $page = input('page',1);
        $limit = input('limit',10);
        $res = $this->indexAction->get_goods_comments($goodsId,$page,$limit);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }

   /**
     * 获取水票 列表--已添加至接口文档
     * $isHot = 1 热销 ，= 0 所有
     */
    public function get_tickets_list()
    {
        $isHot = input('is_hot',0);
        $page = input('page',1);
        $limit = input('limit',10);
        
        $list = $this->indexAction->get_tickets_list($isHot,$page,$limit,$this->vip);
        return commonReturnSuccess($list,$this->vip);
    }
    /**
     * 水票列表--点击-购买--获取水票规格 --已添加至接口文档
     */
    public function get_ticket_detail()
    {
        $goodsId = input('goods_id',0);
        $isHot = input('is_hot',0);
        $res = $this->indexAction->get_ticket_detail($goodsId,$isHot,$this->vip);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    /**
     * 一键送水--返回品牌列表--已添加至接口文档
     */
    public function get_brand_list()
    {
        $list = $this->indexAction->get_brand_list();
        return commonReturnSuccess($list);
    }
    
    /**
     * 上传文件 --已添加至接口文档
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
        $config = get_addon_config('alioss');
        $oss = new OssClient($config['accessKeyId'], $config['accessKeySecret'], $config['endpoint']);
        //检测删除文件或附件
        $checkDeleteFile = function ($attachment, $upload, $force = false) use ($config) {
            //如果设定为不备份则删除文件和记录 或 强制删除
            if ((isset($config['serverbackup']) && !$config['serverbackup']) || $force) {
                if ($attachment && !empty($attachment['id'])) {
                    $attachment->delete();
                }
                if ($upload) {
                    //文件绝对路径
                    $filePath = $upload->getFile()->getRealPath() ?: $upload->getFile()->getPathname();
                    @unlink($filePath);
                }
            }
        };

        $attachment = null;
        //默认普通上传文件
        $file = $this->request->file('file');
        try {
            $upload = new Upload($file);
            $attachment = $upload->upload();
        } catch (UploadException $e) {
            return commonReturnError($e->getMessage());
        }

        //文件绝对路径
        $filePath = $upload->getFile()->getRealPath() ?: $upload->getFile()->getPathname();

        $url = $attachment->url;

        try {
            $ret = $oss->uploadFile($config['bucket'], ltrim($attachment->url, "/"), $filePath);
            //成功不做任何操作
        } catch (\Exception $e) {
            $checkDeleteFile($attachment, $upload, true);
            return commonReturnSuccess("上传失败");
        }
        $checkDeleteFile($attachment, $upload);
        return commonReturnSuccess(['fullurl' => cdnurl($url, true)],"上传成功");
    }
    /**
     * 限时秒杀--商品列表--已添加至接口文档
     */
    public function get_seconds_kill_list()
    {
        $page = input('page',1);
        $limit = input('limit',15);
        $list = $this->indexAction->get_seconds_kill_list($page,$limit);
        return commonReturnSuccess($list);
    }
     /**
     * 获取秒杀商品详情--已添加至接口文档
     */
    public function get_kill_goods_detail()
    {
        $killGoodsId = input('kill_goods_id');
        $res = $this->indexAction->get_kill_goods_detail($killGoodsId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data']);
    }
    public function test()
    {
        $data = cache("8dac256052137953e9fcbf736b882b78");
        halt($data);
    }


    /**
     * 首页弹窗 20220509
     */
    public function index_alert()
    {
        $data = [
            'img' => cdnurl(config('site.index_ad_img')),
            'url' => config('site.index_ad_url'),
            'content'=> config('site.index_alert_content'),
            // 'in_out' => config('site.index_ad_inout'),
            'text' => 'img:图片，url:跳转链接 ,in_out:1=内链，2=外链'
        ];
        return commonReturnSuccess($data);
    }
}
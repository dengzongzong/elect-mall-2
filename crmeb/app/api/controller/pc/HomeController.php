<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2026 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\api\controller\pc;


use app\Request;
use app\services\pc\HomeServices;
use app\services\other\QrcodeServices;

/**
 * Class HomeController
 * @package app\api\controller\pc
 */
class HomeController
{
    /**
     *
     * @var HomeServices
     */
    protected $services;

    /**
     * HomeController constructor.
     * @param HomeServices $services
     */
    public function __construct(HomeServices $services)
    {
        $this->services = $services;
    }

    /**
     * PC端首页轮播图
     * @return mixed
     */
    public function getBanner()
    {
        $list = sys_data('pc_home_banner');
        return app('json')->success(compact('list'));
    }

    /**
     * PC端首页右侧广告位
     * @return mixed
     */
    public function getHomeAd()
    {
        $list = sys_data('pc_home_ad');
        return app('json')->success(compact('list'));
    }

    /**
     * 首页分类尚品
     * @return mixed
     */
    public function getCategoryProduct(Request $request)
    {
        $data = $this->services->getCategoryProduct((int)$request->uid());
        return app('json')->success($data);
    }

    /**
     * 获取品牌列表
     * @return mixed
     */
    public function getBrandList()
    {
        $brandModel = app()->make(\app\model\product\product\StoreCategory::class);
        $db = app()->db->table('eb_brand');
        $list = $db->where('status', 1)
            ->where('is_del', 0)
            ->order('sort ASC')
            ->select()
            ->toArray();
        return app('json')->success(compact('list'));
    }

    /**
     * 获取手机购买跳转url配置
     * @return string
     */
    public function getProductPhoneBuy()
    {
        $phoneBuy = sys_config('product_phone_buy_url', 1);
        $siteUrl = sys_config('site_url');
        return app('json')->success(['phone_buy' => $phoneBuy, 'sit_url' => $siteUrl]);
    }

    /**
     * 付费会员购买二维码
     * @return mixed
     */
    public function getPayVipCode()
    {
        $type = sys_config('product_phone_buy_url', 1);
        $url = '/pages/annex/vip_paid/index';
        $name = "wechat_pay_vip_code.png";
        /** @var QrcodeServices $QrcodeService */
        $QrcodeService = app()->make(QrcodeServices::class);
        if ($type == 1) {
            $codeUrl = $QrcodeService->getWechatQrcodePath($name, $url, false, false);
        } else {
            //生成小程序地址
            $codeUrl = $QrcodeService->getRoutineQrcodePath(0, 0, 5, [], false);
        }
        return app('json')->success(['url' => $codeUrl ?: '']);
    }

    /**
     * BOM配单 - 处理粘贴文本
     * @param Request $request
     * @return mixed
     */
    public function bomProcessText(Request $request)
    {
        $data = $request->postMore([
            ['text', ''],
            ['only_self', 0]
        ]);
        if (!$data['text']) {
            return app('json')->fail('请输入物料清单');
        }
        // 解析BOM文本，按行拆分
        $lines = explode("\n", trim($data['text']));
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = preg_split('/[\s,\t]+/', $line);
            if (count($parts) >= 1) {
                $items[] = [
                    'part_no' => $parts[0],
                    'spec' => count($parts) > 1 ? $parts[1] : '',
                    'qty' => count($parts) > 2 ? $parts[2] : '1',
                ];
            }
        }
        return app('json')->success(compact('items'));
    }

    /**
     * BOM配单 - 上传文件
     * @param Request $request
     * @return mixed
     */
    public function bomUpload(Request $request)
    {
        $file = $request->file('file');
        $onlySelf = $request->post('only_self', 0);
        if (!$file) {
            return app('json')->fail('请上传BOM文件');
        }
        // 读取文件内容
        $contents = file_get_contents($file->getPathname());
        $lines = explode("\n", trim($contents));
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = preg_split('/[\s,\t,，]+/', $line);
            if (count($parts) >= 1) {
                $items[] = [
                    'part_no' => $parts[0],
                    'spec' => count($parts) > 1 ? $parts[1] : '',
                    'qty' => count($parts) > 2 ? $parts[2] : '1',
                ];
            }
        }
        return app('json')->success(compact('items'));
    }
}
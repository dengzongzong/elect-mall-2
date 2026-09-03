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
namespace app\services\agent;

use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\other\QrcodeServices;
use app\services\system\admin\SystemAdminServices;
use app\services\system\admin\SystemRoleServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use crmeb\services\FormBuilder as Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;

class DivisionServices extends BaseServices
{
    /**
     * 获取事业部/代理/员工列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDivisionList(array $where = [])
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $data = $userServices->getDivisionList($where + ['status' => 1], 'uid,nickname,avatar,division_name,division_percent,division_end_time,division_status,division_invite');
        foreach ($data['list'] as &$item) {
            $item['division_end_time'] = date('Y-m-d', $item['division_end_time']);
            $item['agent_count'] = $userServices->count([
                $where['division_type'] == 1 ? 'division_id' : 'agent_id' => $item['uid'],
                'division_type' => $where['division_type'] + 1,
                'status' => 1,
                'is_del' => 0
            ]);
            unset($item['label']);
        }
        return $data;
    }

    /**
     * 下级列表
     * @param $type
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function divisionDownList($type, $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $where = [
            $type == 2 ? 'division_id' : 'agent_id' => $uid,
            'division_type' => $type
        ];
        $where['status'] = 1;
        $where['is_del'] = 0;
        $data = $userServices->getDivisionList($where, 'uid,nickname,avatar,division_name,division_percent,division_end_time,division_status');
        foreach ($data['list'] as &$item) {
            $item['agent_count'] = $userServices->count([
                'agent_id' => $item['uid'],
                'division_type' => $type + 1,
                'status' => 1
            ]);
            unset($item['label']);
        }
        return $data;
    }

    /**
     * 添加编辑事业部表单
     * @param $uid
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function getDivisionForm($uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var SystemAdminServices $adminService */
        $adminService = app()->make(SystemAdminServices::class);
        $userInfo = $userServices->getUserInfo($uid);
        if ($uid && !$userInfo) throw new AdminException('参数错误');
        if ($uid) {
            $adminInfo = $adminService->getInfo(['division_id' => $uid])->toArray();
            if (isset($adminInfo['roles'])) {
                foreach ($adminInfo['roles'] as &$item) {
                    $item = intval($item);
                }
            }
        }
        $field = [];
        $title = '事业部';
        $field[] = Form::input('division_name', '事业部名称', $userInfo['division_name'] ?? '')->required('请输入事业部名称');
        if ($uid) {
            $field[] = Form::hidden('uid', $uid);
        } else {
            $field[] = Form::frameImage('image', '关联用户', $this->url(config('app.admin_prefix', 'admin') . '/system.user/list', ['fodder' => 'image'], true))->icon('el-icon-user')->width('950px')->height('560px')->Props(['srcKey' => 'image', 'footer' => false]);
        }
        $field[] = Form::hidden('aid', $adminInfo['id'] ?? 0);
        $field[] = Form::number('division_percent', '佣金比例', $userInfo['division_percent'] ?? '')->placeholder('区域代理佣金比例1-100')->info('填写1-100，如填写50代表返佣50%')->style(['width' => '173px'])->min(0)->max(100)->required();
        $field[] = Form::date('division_end_time', '到期时间', ($userInfo['division_end_time'] ?? '') != 0 ? date('Y-m-d H:i:s', $userInfo['division_end_time']) : '')->placeholder('区域代理到期时间');
        $field[] = Form::radio('division_status', '代理状态', $userInfo['division_status'] ?? 1)->options([['label' => '开通', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        $field[] = Form::input('account', '管理账号', $adminInfo['account'] ?? '')->required('请填写管理员账号');
        $field[] = Form::input('pwd', '管理密码')->type('password')->placeholder('请填写管理员密码');
        $field[] = Form::input('conf_pwd', '确认密码')->type('password')->placeholder('请输入确认密码');
        /** @var SystemRoleServices $service */
        $service = app()->make(SystemRoleServices::class);
        $options = $service->getRoleFormSelect(1);
        $field[] = Form::select('roles', '管理员身份', $adminInfo['roles'] ?? [])->setOptions(Form::setOptions($options))->multiple(true)->required('请选择管理员身份');
        return create_form($title, $field, Route::buildUrl('/agent/division/save'), 'POST');
    }

    /**
     * 保存事业部数据
     * @param $data
     * @return mixed
     */
    public function divisionSave($data)
    {
        if ((int)$data['uid'] == 0) $data['uid'] = $data['image']['uid'];
        if ((int)$data['uid'] == 0) throw new AdminException('请填写用户UID');
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ($data['aid'] == 0) {
            $userInfo = $userServices->getUserInfo($data['uid'], 'is_division,is_agent,is_staff');
            if (!$userInfo) throw new AdminException('用户不存在');
            if ($userInfo['is_division']) throw new AdminException('此用户是事业部，请勿重复添加');
            if ($userInfo['is_agent']) throw new AdminException('此用户是代理商，无法添加为事业部');
            if ($userInfo['is_staff']) throw new AdminException('此用户是下级员工，无法添加为事业部');
        }
        $uid = $data['uid'];
        $aid = $data['aid'];
        $agentData = [
            'division_percent' => $data['division_percent'],
            'division_end_time' => strtotime($data['division_end_time']),
            'division_change_time' => time(),
            'is_division' => 1,
            'is_agent' => 0,
            'is_staff' => 0,
            'division_id' => $uid,
            'agent_id' => 0,
            'staff_id' => 0,
            'division_type' => 1,
            'division_status' => $data['division_status'],
            'spread_uid' => 0,
            'spread_time' => 0,
            'division_name' => $data['division_name'],
            'is_promoter' => 1
        ];
        $adminData = [
            'account' => $data['account'],
            'pwd' => $data['pwd'],
            'conf_pwd' => $data['conf_pwd'],
            'real_name' => $data['division_name'],
            'roles' => $data['roles'],
            'status' => 1,
            'level' => 1,
            'division_id' => $uid
        ];
        return $this->transaction(function () use ($uid, $agentData, $adminData, $aid, $userServices) {
            $agentData['division_invite'] = $userServices->value(['uid' => $uid], 'division_invite') ?: rand(10000000, 99999999);
            $userServices->update($uid, $agentData);

            /** @var SystemAdminServices $adminService */
            $adminService = app()->make(SystemAdminServices::class);
            if (!$aid) {
                if ($adminData['pwd']) {
                    if (!$adminData['conf_pwd']) throw new AdminException('请输入确认密码');
                    if ($adminData['pwd'] != $adminData['conf_pwd']) throw new AdminException('两次输入的密码不一致');
                    $adminService->create($adminData);
                } else {
                    throw new AdminException('请输入确认密码');
                }
            } else {
                $adminInfo = $adminService->get($aid);
                if (!$adminInfo)
                    throw new AdminException('管理员信息未查到');
                if ($adminInfo->is_del) {
                    throw new AdminException('管理员已经删除');
                }
                if (!$adminData['real_name'])
                    throw new AdminException('管理员姓名不能为空');
                if ($adminData['pwd']) {
                    if (!$adminData['conf_pwd']) throw new AdminException('请输入确认密码');
                    if ($adminData['pwd'] != $adminData['conf_pwd']) throw new AdminException('两次输入的密码不一致');
                    $adminInfo->pwd = $this->passwordHash($adminData['pwd']);
                }
                $adminInfo->real_name = $adminData['real_name'];
                $adminInfo->account = $adminData['account'];
                $adminInfo->roles = implode(',', $adminData['roles']);
                if ($adminInfo->save())
                    return true;
                else
                    return false;
            }
            return true;
        });
    }

//    /**
//     * 生成邀请码
//     * @return false|string
//     */
//    public function getDivisionInvite()
//    {
//        /** @var UserServices $userServices */
//        $userServices = app()->make(UserServices::class);
//        list($msec, $sec) = explode(' ', microtime());
//        $num = time() + mt_rand(10, 999999) . '' . substr($msec, 2, 3);//生成随机数
//        if (strlen($num) < 12)
//            $num = str_pad((string)$num, 8, 0, STR_PAD_RIGHT);
//        else
//            $num = substr($num, 0, 8);
//        if ($userServices->count(['division_invite' => $num])) {
//            return $this->getDivisionInvite();
//        }
//        return $num;
//    }

    /**
     * 添加编辑代理商
     * @param $uid
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function getDivisionAgentForm($uid)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userInfo = $userService->get($uid);
        if ($uid && !$userInfo) throw new AdminException('用户不存在');
        $field = [];
        $options = [];
        $divisionList = $userService->getDivisionList(['status' => 1, 'division_type' => 1], 'uid,division_name');
        foreach ($divisionList['list'] as $item) {
            $options[] = ['value' => $item['uid'], 'label' => $item['division_name']];
        }
        $field[] = Form::input('division_name', '代理商名称', $userInfo['division_name'] ?? '')->required('请输入代理商名称');
        if ($uid) {
            $field[] = Form::hidden('uid', $uid);
            $field[] = Form::hidden('edit', 1);
            $field[] = Form::hidden('division_id', $userInfo['division_id']);
        } else {
            $field[] = Form::select('division_id', '上级事业部', '')->setOptions(Form::setOptions($options))->filterable(1);
            $field[] = Form::frameImage('image', '关联用户', $this->url(config('app.admin_prefix', 'admin') . '/system.user/list', ['fodder' => 'image'], true))->icon('el-icon-user')->width('950px')->height('560px')->Props(['srcKey' => 'image', 'footer' => false]);
            $field[] = Form::hidden('edit', 0);
        }
        $field[] = Form::number('division_percent', '佣金比例', $userInfo['division_percent'] ?? '')->placeholder('代理商佣金比例1-100')->info('填写1-100，如填写50代表返佣50%,但是不能高于上级事业部的比例')->style(['width' => '173px'])->min(0)->max(100)->required();
        $field[] = Form::date('division_end_time', '到期时间', ($userInfo['division_end_time'] ?? '') != 0 ? date('Y-m-d H:i:s', $userInfo['division_end_time']) : '')->placeholder('代理商代理到期时间');
        $field[] = Form::radio('division_status', '代理状态', $userInfo['division_status'] ?? 1)->options([['label' => '开通', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        return create_form('代理商', $field, Route::buildUrl('/agent/division/agent/save'), 'POST');
    }

    /**
     * 保存代理商
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function divisionAgentSave($data)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $uid = $data['uid'];
        $agentData = [
            'spread_uid' => $data['division_id'],
            'spread_time' => time(),
            'division_id' => $data['division_id'],
            'division_status' => $data['division_status'],
            'division_percent' => $data['division_percent'],
            'division_change_time' => time(),
            'division_end_time' => strtotime($data['division_end_time']),
            'division_type' => 2,
            'is_agent' => 1,
            'agent_id' => $uid,
            'is_staff' => 0,
            'staff_id' => 0,
            'division_name' => $data['division_name'],
            'is_promoter' => 1
        ];
        $division_info = $userServices->getUserInfo($data['division_id'], 'division_end_time,division_percent');
        if ($division_info) {
            if ($agentData['division_percent'] > $division_info['division_percent']) throw new AdminException('代理商佣金比例不能大于事业部佣金比例');
            if ($agentData['division_end_time'] > $division_info['division_end_time']) throw new AdminException('代理商到期时间不能大于事业部到期时间');
        }
        $res = $userServices->update($uid, $agentData);
        if ($res) return true;
        throw new AdminException('保存失败');
    }

    /**
     * 修改状态
     * @param $status
     * @param $uid
     * @return bool
     */
    public function setDivisionStatus($status, $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var SystemAdminServices $adminServices */
        $adminServices = app()->make(SystemAdminServices::class);
        $res = $userServices->update($uid, ['division_status' => $status]);
        $res = $res && $adminServices->update(['division_id' => $uid], ['status' => $status]);
        if ($res) {
            return true;
        } else {
            throw new AdminException('操作失败');
        }
    }

    /**
     * 删除事业部/代理商
     * @param $type
     * @param $uid
     * @return mixed
     */
    public function delDivision($type, $uid)
    {
        return $this->transaction(function () use ($type, $uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserInfo($uid);
            if (!$userInfo) throw new AdminException('用户不存在');
            $userInfo = $userInfo->toArray();
            $data = [
                'division_name' => '',
                'division_type' => 0,
                'division_status' => 0,
                'is_division' => 0,
                'is_agent' => 0,
                'is_staff' => 0,
                'division_id' => 0,
                'agent_id' => 0,
                'staff_id' => 0,
                'division_percent' => 0,
                'division_end_time' => 0,
                'division_change_time' => time(),
                'division_invite' => 0
            ];
            $userServices->update(['uid' => $uid], $data);
            if ($userInfo['division_type'] == 1) {
                app()->make(SystemAdminServices::class)->delete(['division_id' => $uid]);
                $userServices->update(['division_id' => $uid], $data);
            } elseif ($userInfo['division_type'] == 2) {
                app()->make(DivisionAgentApplyServices::class)->delete(['uid' => $uid]);
                $userServices->update(['agent_id' => $uid], $data);
            } elseif ($userInfo['division_type'] == 3) {
                $userServices->update(['staff_id' => $uid], $data);
            }
        });
    }

    /**
     * 后台添加员工
     * @param $uid
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2024/1/22
     */
    public function getDivisionStaffForm($uid)
    {
        $field = [];
        $field[] = Form::frameImage('image', '员工', $this->url(config('app.admin_prefix', 'admin') . '/system.user/list', ['fodder' => 'image'], true))->icon('el-icon-user')->width('950px')->height('560px')->Props(['srcKey' => 'image', 'footer' => false]);
        $field[] = Form::number('division_percent', '佣金比例', '')->placeholder('员工佣金比例1-100')->info('填写1-100，如填写50代表返佣50%,但是不能高于上级代理商的比例')->style(['width' => '173px'])->min(0)->max(100)->required();
        $field[] = Form::hidden('agent_id', $uid);
        return create_form('员工', $field, Route::buildUrl('/agent/division/staff/save'), 'POST');
    }

    /**
     * 保存员工
     * @param $data
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2024/1/22
     */
    public function divisionStaffSave($data)
    {
        $data['uid'] = $data['image']['uid'];
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo($data['uid'], 'is_division,is_agent,is_staff,division_id,agent_id,staff_id,division_end_time,division_percent');
        if (!$userInfo) throw new AdminException('用户不存在');
        if ($userInfo['is_division']) throw new AdminException('此用户是事业部，无法绑定为员工');
        if ($userInfo['is_agent']) throw new AdminException('此用户是代理商，无法绑定为员工');
        if ($userInfo['is_staff'] && $userInfo['agent_id'] == $data['agent_id']) throw new AdminException('此用户是您的员工，请勿重复添加');
        $agentInfo = $userServices->getUserInfo($data['agent_id'], 'division_id,agent_id,division_end_time,division_percent');
        $staffData = [
            'spread_uid' => $data['agent_id'],
            'spread_time' => time(),
            'division_type' => 3,
            'division_status' => 1,
            'is_staff' => 1,
            'division_id' => $agentInfo['division_id'],
            'agent_id' => $agentInfo['agent_id'],
            'staff_id' => $data['uid'],
            'division_percent' => $data['division_percent'],
            'division_change_time' => time(),
            'division_end_time' => $agentInfo['division_end_time'],
            'is_promoter' => 1
        ];
        if ($staffData['division_percent'] > $agentInfo['division_percent']) throw new AdminException('代理商佣金比例不能大于事业部佣金比例');
        if ($userInfo['agent_id'] != 0 && $userInfo['agent_id'] != $agentInfo['agent_id']) {
            $userServices->update(['staff_id' => $userInfo['uid'], 'spread_uid' => $userInfo['uid']], ['spread_uid' => $agentInfo['agent_id'], 'staff_id' => 0]);
            $userServices->getSearch(['staff_id' => $userInfo['uid'], 'not_spread_uid' => $userInfo['uid']])->update(['staff_id' => 0]);
        }
        $res = $userServices->update($data['uid'], $staffData);
        if ($res) return true;
        throw new AdminException('保存失败');
    }

    /**
     * 扫码绑定员工
     * @param $uid
     * @param int $agentId
     * @param int $agentCode
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2024/2/2
     */
    public function agentSpreadStaff($uid, int $agentId = 0, int $agentCode = 0)
    {
        if ($agentCode && !$agentId) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            if ($info = $qrCode->getOne(['id' => $agentCode, 'third_type' => 'agent', 'status' => 1])) {
                $agentId = $info['third_id'];
            }
        }
        if (!$agentId) return false;
        if ($uid == $agentId) return '自己不能推荐自己';
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $agentInfo = $userServices->getUserInfo($agentId, 'division_id,agent_id,division_end_time,division_percent');
        if (!$agentInfo) return '上级用户不存在';
        $userInfo = $userServices->getUserInfo($uid, 'is_division,is_agent,is_staff,division_id,agent_id,staff_id,division_end_time,division_percent');
        if (!$userInfo) return '用户不存在';
        if ($userInfo['is_division']) return '您是事业部,不能绑定成为别人的员工';
        if ($userInfo['is_agent']) return '您是代理商,不能绑定成为别人的员工';
        $staffData = [
            'spread_uid' => $agentId,
            'spread_time' => time(),
            'division_type' => 3,
            'division_status' => 1,
            'is_staff' => 1,
            'division_id' => $agentInfo['division_id'],
            'agent_id' => $agentInfo['agent_id'],
            'staff_id' => $uid,
            'division_change_time' => time(),
            'is_promoter' => 1
        ];
        if ($userInfo['agent_id'] != 0 && $userInfo['agent_id'] != $agentInfo['agent_id']) {
            $userServices->update(['staff_id' => $userInfo['uid'], 'spread_uid' => $userInfo['uid']], ['spread_uid' => $agentInfo['agent_id'], 'staff_id' => 0]);
            $userServices->update(['staff_id' => $userInfo['uid'], 'not_spread_uid' => $userInfo['uid']], ['staff_id' => 0]);
        }
        $res = $userServices->update($uid, $staffData);
        if ($res) return '绑定员工成功';
        return '绑定员工失败';
    }

    /**
     * 计算各层级的实际佣金分配比例
     *
     * 核心逻辑：总佣金池 = 事业部设定的 division_percent，各下级按层级递减瓜分。
     * 优先级（从高到低）：普通返佣(一/二级) → 员工 → 代理商 → 事业部
     * 即：下级拿走多少，上级就从自己的额度中扣减对应比例。
     *
     * @param int   $uid                 购买者用户ID
     * @param float $storeBrokerageRatio    商品配置的一级返佣比例（%）
     * @param float $storeBrokerageRatioTwo 商品配置的二级返佣比例（%）
     * @param bool  $isSelfBrokerage     是否允许自购返佣
     * @return array [$storeBrokerageOne, $storeBrokerageTwo, $staffPercent, $agentPercent, $divisionPercent]
     *               依次为：一级返佣比例、二级返佣比例、员工佣金比例、代理商佣金比例、事业部佣金比例
     */
    public function getDivisionPercent($uid, $storeBrokerageRatio, $storeBrokerageRatioTwo, $isSelfBrokerage)
    {
        // 读取系统配置，判断代理商（事业部）功能是否开启；强转 int 防止字符串 '0' 被误判为 true
        $division_open = (int)sys_config('division_status', 1);

        if (!$division_open) {
            // ── 代理商功能关闭 ──────────────────────────────────────────
            // 直接使用商品本身配置的返佣比例，事业部/代理商/员工层级全部清零
            $storeBrokerageOne = $storeBrokerageRatio;    // 一级返佣 = 商品配置值
            $storeBrokerageTwo = $storeBrokerageRatioTwo; // 二级返佣 = 商品配置值
            $staffPercent      = 0;                       // 员工佣金比例 = 0
            $agentPercent      = 0;                       // 代理商佣金比例 = 0
            $divisionPercent   = 0;                       // 事业部佣金比例 = 0
        } else {
            // ── 代理商功能开启，按购买者身份分支计算 ──────────────────────

            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class); // 通过容器获取用户服务实例
            $userInfo     = $userServices->get($uid);         // 获取购买者用户信息

            // ====== 分支一：购买者自身就是事业部 ======
            if ($userInfo['is_division'] == 1) {
                // 事业部身份购买，无需走普通返佣链路，一/二级及下级层级均清零
                $storeBrokerageOne = 0; // 一级返佣清零（事业部不走普通返佣）
                $storeBrokerageTwo = 0; // 二级返佣清零
                $staffPercent      = 0; // 员工佣金清零
                $agentPercent      = 0; // 代理商佣金清零

                // 判断该事业部资格是否有效：状态=1 且未过期
                if ($userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time()) {
                    // 允许自购返佣时，事业部自己获得其 division_percent；否则为 0
                    $divisionPercent = $isSelfBrokerage ? $userInfo['division_percent'] : 0;
                } else {
                    // 资格无效，事业部佣金清零
                    $divisionPercent = 0;
                }

            // ====== 分支二：购买者自身是代理商 ======
            } elseif ($userInfo['is_agent'] == 1) {
                // 获取该代理商上级事业部的信息
                $divisionInfo      = $userServices->get($userInfo['division_id']);
                $storeBrokerageOne = 0; // 代理商身份购买，普通一级返佣清零
                $storeBrokerageTwo = 0; // 普通二级返佣清零
                $staffPercent      = 0; // 代理商无员工层级，清零

                // 计算代理商自己的佣金比例
                if ($userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time()) {
                    // 代理商资格有效：允许自购时取其 division_percent，否则为 0
                    $agentPercent = $isSelfBrokerage ? $userInfo['division_percent'] : 0;
                } else {
                    $agentPercent = 0; // 代理商资格无效
                }

                // 计算上级事业部的佣金比例 = 事业部额度 - 代理商已分走的额度
                if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                    $divisionPercent = bcsub($divisionInfo['division_percent'], $agentPercent, 2); // 事业部额度扣除代理商部分
                    $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;               // 防止出现负值
                } else {
                    $divisionPercent = 0; // 事业部资格无效
                }

            // ====== 分支三：购买者自身是员工 ======
            } elseif ($userInfo['is_staff'] == 1) {
                // 获取该员工的上级代理商和事业部信息
                $agentInfo         = $userServices->get($userInfo['agent_id']);
                $divisionInfo      = $userServices->get($userInfo['division_id']);
                $storeBrokerageOne = 0; // 员工身份购买，普通一级返佣清零
                $storeBrokerageTwo = 0; // 普通二级返佣清零

                // 计算员工自己的佣金比例
                if ($userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time()) {
                    // 员工资格有效：允许自购时取其 division_percent，否则为 0
                    $staffPercent = $isSelfBrokerage ? $userInfo['division_percent'] : 0;
                } else {
                    $staffPercent = 0; // 员工资格无效
                }

                // 计算上级代理商的佣金比例 = 代理商额度 - 员工已分走的额度
                if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                    $agentPercent = bcsub($agentInfo['division_percent'], $staffPercent, 2); // 代理商额度扣除员工部分
                    $agentPercent = $agentPercent < 0 ? 0 : $agentPercent;                  // 防止负值
                } else {
                    $agentPercent = 0; // 代理商资格无效
                }

                // 计算上级事业部的佣金比例 = 事业部额度 - (员工 + 代理商)已分走的总额度
                if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                    $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd($staffPercent, $agentPercent, 2), 2); // 事业部额度扣除员工+代理商部分
                    $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;                                        // 防止负值
                } else {
                    $divisionPercent = 0; // 事业部资格无效
                }

            // ====== 分支四：购买者是普通用户，走正常返佣链路 ======
            } else {
                // 获取该普通用户绑定的员工、代理商、事业部信息
                $staffInfo    = $userServices->get($userInfo['staff_id']);    // 绑定员工
                $agentInfo    = $userServices->get($userInfo['agent_id']);    // 绑定代理商
                $divisionInfo = $userServices->get($userInfo['division_id']); // 绑定事业部

                // ── 子分支4-1：该用户属于「员工」体系（staff_id 有值）──
                if ($userInfo['staff_id']) {

                    if ($userInfo['staff_id'] == $userInfo['spread_uid']) {
                        // ---- 情况A：员工就是直接推荐人（员工直接下级）----
                        // 允许自购时一级返佣 = 商品配置值，否则为 0（自购不给推荐人返佣）
                        $storeBrokerageOne = $isSelfBrokerage ? $storeBrokerageRatio : 0;
                        $storeBrokerageTwo = 0; // 直接下级没有二级返佣

                        // 员工佣金 = 员工额度 - 一级返佣（员工既是推荐人又是上级，要扣掉已给的返佣）
                        if ($staffInfo['division_status'] == 1 && $staffInfo['division_end_time'] > time()) {
                            $staffPercent = bcsub($staffInfo['division_percent'], $storeBrokerageOne, 2);
                            $staffPercent = $staffPercent < 0 ? 0 : $staffPercent; // 防止负值
                        } else {
                            $staffPercent = 0;
                        }

                        // 代理商佣金 = 代理商额度 - (一级返佣 + 员工佣金)
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], bcadd($storeBrokerageOne, $staffPercent, 2), 2);
                            $agentPercent = $agentPercent < 0 ? 0 : $agentPercent;
                        } else {
                            $agentPercent = 0;
                        }

                        // 事业部佣金 = 事业部额度 - (一级返佣 + 员工佣金 + 代理商佣金)
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd(bcadd($storeBrokerageOne, $staffPercent, 2), $agentPercent, 2), 2);
                            $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;
                        } else {
                            $divisionPercent = 0;
                        }
                    } else {
                        // ---- 情况B：员工不是直接推荐人（间接下级，存在二级返佣）----
                        $storeBrokerageOne = $storeBrokerageRatio; // 一级返佣 = 商品配置值（给直接推荐人）

                        // 判断二级返佣：推荐人的推荐人是否就是该员工，且为自购场景
                        // 若是（自购且推荐链=员工→用户），则二级不重复给；否则按商品配置给二级返佣
                        $storeBrokerageTwo = $userServices->value(['uid' => $userInfo['spread_uid']], 'spread_uid') == $userInfo['staff_id'] && !$isSelfBrokerage ? 0 : $storeBrokerageRatioTwo;

                        $brokerageOneTwo = bcadd($storeBrokerageOne, $storeBrokerageTwo, 2); // 一+二级返佣合计

                        // 员工佣金 = 员工额度 - 一二级返佣合计
                        if ($staffInfo['division_status'] == 1 && $staffInfo['division_end_time'] > time()) {
                            $staffPercent = bcsub($staffInfo['division_percent'], $brokerageOneTwo, 2);
                            $staffPercent = $staffPercent < 0 ? 0 : $staffPercent;
                        } else {
                            $staffPercent = 0;
                        }

                        // 代理商佣金 = 代理商额度 - (一二级合计 + 员工佣金)
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], bcadd($brokerageOneTwo, $staffPercent, 2), 2);
                            $agentPercent = $agentPercent < 0 ? 0 : $agentPercent;
                        } else {
                            $agentPercent = 0;
                        }

                        // 事业部佣金 = 事业部额度 - (一二级合计 + 员工佣金 + 代理商佣金)
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd(bcadd($brokerageOneTwo, $staffPercent, 2), $agentPercent, 2), 2);
                            $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;
                        } else {
                            $divisionPercent = 0;
                        }
                    }

                // ── 子分支4-2：该用户属于「代理商」体系（无员工绑定，有 agent_id）──
                } elseif ($userInfo['agent_id']) {

                    if ($userInfo['agent_id'] == $userInfo['spread_uid']) {
                        // ---- 情况A：代理商就是直接推荐人 ----
                        $storeBrokerageOne = $isSelfBrokerage ? $storeBrokerageRatio : 0; // 自购时给一级返佣，否则为 0
                        $storeBrokerageTwo = 0; // 直接下级无二级返佣
                        $staffPercent      = 0; // 代理商体系无员工层级

                        // 代理商佣金 = 代理商额度 - 一级返佣
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], $storeBrokerageOne, 2);
                            $agentPercent = $agentPercent < 0 ? 0 : $agentPercent;
                        } else {
                            $agentPercent = 0;
                        }

                        // 事业部佣金 = 事业部额度 - (一级返佣 + 代理商佣金)
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd($storeBrokerageOne, $agentPercent, 2), 2);
                            $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;
                        } else {
                            $divisionPercent = 0;
                        }
                    } else {
                        // ---- 情况B：代理商不是直接推荐人（间接下级）----
                        $storeBrokerageOne = $storeBrokerageRatio; // 一级返佣 = 商品配置值

                        // 判断二级返佣（同员工分支的情况B逻辑，防止自购时重复给佣）
                        $storeBrokerageTwo = $userServices->value(['uid' => $userInfo['spread_uid']], 'spread_uid') == $userInfo['agent_id'] && !$isSelfBrokerage ? 0 : $storeBrokerageRatioTwo;

                        $brokerageOneTwo = bcadd($storeBrokerageOne, $storeBrokerageTwo, 2); // 一+二级合计
                        $staffPercent    = 0; // 代理商体系无员工层级

                        // 代理商佣金 = 代理商额度 - 一二级合计
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], $brokerageOneTwo, 2);
                            $agentPercent = $agentPercent < 0 ? 0 : $agentPercent;
                        } else {
                            $agentPercent = 0;
                        }

                        // 事业部佣金 = 事业部额度 - (一二级合计 + 代理商佣金)
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd($brokerageOneTwo, $agentPercent, 2), 2);
                            $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;
                        } else {
                            $divisionPercent = 0;
                        }
                    }

                // ── 子分支4-3：该用户属于「事业部」体系（无员工/代理商绑定，有 division_id）──
                } elseif ($userInfo['division_id']) {

                    if ($userInfo['division_id'] == $userInfo['spread_uid']) {
                        // ---- 情况A：事业部就是直接推荐人 ----
                        $storeBrokerageOne = $isSelfBrokerage ? $storeBrokerageRatio : 0; // 自购时给一级返佣
                        $storeBrokerageTwo = 0; // 直接下级无二级返佣
                        $staffPercent      = 0; // 事业部体系无员工层级
                        $agentPercent      = 0; // 事业部体系无代理商层级

                        // 事业部佣金 = 事业部额度 - 一级返佣
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], $storeBrokerageOne, 2);
                            $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;
                        } else {
                            $divisionPercent = 0;
                        }
                    } else {
                        // ---- 情况B：事业部不是直接推荐人（间接下级）----
                        $storeBrokerageOne = $storeBrokerageRatio; // 一级返佣 = 商品配置值

                        // 判断二级返佣（防止自购重复给佣）
                        $storeBrokerageTwo = $userServices->value(['uid' => $userInfo['spread_uid']], 'spread_uid') == $userInfo['division_id'] && !$isSelfBrokerage ? 0 : $storeBrokerageRatioTwo;

                        $brokerageOneTwo = bcadd($storeBrokerageOne, $storeBrokerageTwo, 2); // 一+二级合计
                        $staffPercent    = 0; // 无员工层级
                        $agentPercent    = 0; // 无代理商层级

                        // 事业部佣金 = 事业部额度 - 一二级合计
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], $brokerageOneTwo, 2);
                            $divisionPercent = $divisionPercent < 0 ? 0 : $divisionPercent;
                        } else {
                            $divisionPercent = 0;
                        }
                    }

                // ── 子分支4-4：该用户没有任何代理商关系，走纯普通返佣 ──
                } else {
                    // 没有任何代理商绑定，直接使用商品配置的返佣比例，代理商各层级清零
                    $storeBrokerageOne = $storeBrokerageRatio;    // 一级返佣 = 商品配置值
                    $storeBrokerageTwo = $storeBrokerageRatioTwo; // 二级返佣 = 商品配置值
                    $staffPercent      = 0;                       // 无员工
                    $agentPercent      = 0;                       // 无代理商
                    $divisionPercent   = 0;                       // 无事业部
                }
            }
        }

        // 返回五个比例值，max(..., 0) 确保不出现负数（防御性兜底）
        // 顺序：[一级返佣, 二级返佣, 员工佣金, 代理商佣金, 事业部佣金]
        return [max($storeBrokerageOne, 0), max($storeBrokerageTwo, 0), max($staffPercent, 0), max($agentPercent, 0), max($divisionPercent, 0)];
    }

    /**
     * 事业部统计
     * @param $type
     * @param $time
     * @param $page
     * @param $limit
     * @param $sort
     * @param $order
     * @return mixed
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/4/8
     */
    public function divisionStatistics($type, $time, $page, $limit, $sort, $order)
    {
        switch ($type) {
            case 1:
                $field = 'division_id';
                break;
            case 2:
                $field = 'agent_id';
                break;
            case 3:
                $field = 'staff_id';
                break;
            default:
                $field = 'division_id';
        }
        $data = app()->make(StoreOrderServices::class)->divisionStatistics($field, $time, $page, $limit, $sort, $order);
        $uids = array_column($data['list'], $field);
        $userInfos = app()->make(UserServices::class)->getColumn(['uid' => $uids], 'uid,nickname,avatar', 'uid');
        foreach ($data['list'] as $key => &$val) {
            $val['uid'] = $val[$field];
            $val['name'] = $userInfos[$val[$field]]['nickname'];
            $val['avatar'] = set_file_url($userInfos[$val[$field]]['avatar']);
        }
        return $data;
    }
}

<?php

namespace app\admin\controller;



use app\common\model\Admin;
use app\common\model\AdminRole;
use app\common\service\AuthorityService;

/**
 * 后台角色
 * @menuId 1
 * Class AdminRoleController
 * @package app\admin\controller
 */
class AdminRoleController extends AdminBaseController
{

    /**
     * @authName (角色列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (5)
     * @return \think\response\Json|\think\response\View
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function index()
    {
        $input = $this->request->only(['key']);

        $w = AdminRole::makeWhere($input,[
            ['id|name','like','key'],
        ]);
        $data = AdminRole::where($w)
            ->field('id,name,status,auth_list')
            ->order('id desc')
            ->select();
        return self::success($data);
    }


    /**
     * @authName (刷新后台权限)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (6)
     * @return \think\response\Json
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function flashAuthList()
    {
        AuthorityService::refresh();
        return $this->success('');
    }


    /**
     * @authName (获取权限列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (7)
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function getAuthList()
    {
        // 获取全部权限列表
        $list = AuthorityService::getAuthList();
        foreach ($list as &$v) {
            $v['item'] = array_values($v['item']);
        }
        return self::success($list);
    }


    /**
     * @authName (添加修改角色)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (8)
     * @return \think\response\Json
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save()
    {
        // 接收参数
        $input = $this->postValidate([
            'id'        => 'number',
            'name'      => 'require',
            'auth_list' => 'require|array',
        ]);

        // 如果是新增，检查角色名是否重复
        if(empty($input['id'])) {
            if(AdminRole::where('name',$input['name'])->count('*'))
                return $this->error('该角色已经被创建');
        }else{
            if($input['id'] == 1001) return $this->error('超级管理员不可修改');
        }
        // 权限列表字符串转整型
        $input['auth_list'] = array_map(function ($v) {return (int)$v;},$input['auth_list']);
        // 保存
        return $this->success(AdminRole::saveData($input));
    }


    /**
     * @authName (修改角色状态)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (10)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setStatus()
    {
        $id = $this->request->post('id',0,'intval');
        if(empty($id)) return $this->error('角色ID为空');
        if($id == 1001) {
            return $this->error('超级管理员不可修改状态');
        }
        if(AdminRole::where('id',$id)->value('status') == 1) {
            if(Admin::where('role_id','like',"%{$id}%")->count('*'))
                return $this->error('该角色下拥有用户，不可禁用');
        }
        return $this->success(AdminRole::saveStatus($id));
    }


    /**
     * @authName (删除角色)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (11)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete()
    {
        $id = $this->request->post('id');
        if(empty($id)) return $this->error('请选择需要删除的角色');
        if($id == 1001) {
            return $this->error('超级管理员禁止删除');
        }
        if(is_array($id)) {
            foreach ($id as $i) {
                if(Admin::where('role_id','like',"%{$i}%")->count('*'))
                    return $this->error('ID为：<b>'.$i.'</b>的角色下拥有用户，禁止删除');
            }
        }else{
            if(Admin::where('role_id','like',"%{$id}%")->count('*'))
                return $this->error('该角色下拥有用户，禁止删除');
        }

        return $this->success(AdminRole::deleteData($id));
    }
}

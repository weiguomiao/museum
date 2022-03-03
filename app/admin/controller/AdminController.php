<?php

namespace app\admin\controller;

use app\common\model\Admin;
use app\common\model\AdminRole;
use app\common\service\AdminAndRoleService;
use app\common\service\AuthorityService;
use mytools\lib\ToolBag;

/**
 * 后台用户
 * @menuId 1
 * Class AdminController
 * @package app\admin\controller
 */
class AdminController extends AdminBaseController
{

    /**
     * 查询admin
     * @throws \ReflectionException
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function initialize()
    {
        parent::initialize();
        $this->request->admin = Admin::find($this->request->admin_id);
    }

    /**
     * @authName (管理员列表)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (1)
     * @return \think\response\Json|\think\response\View
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function index()
    {
        // 验证参数
        $input = $this->postValidate([
            'role|角色' => 'number',
            'key|关键字' => ''
        ]);
        $w = Admin::makeWhere($input,[
            ['login_name|mobile','like','key'],
            ['role_id','like','role'],
        ]);

        $query = [
            'page' => $this->request->post('page',1,'intval'),
        ];

        // 查询所有角色
        $roles = AdminRole::column('name','id');

        $data = Admin::where($w)
            ->withAttr('role_id_val',function ($v, $data) use ($roles) {
                return AdminAndRoleService::roleId2Str($roles,$data['role_id']);
            })
            ->field('id,role_id,login_name,status,mobile,create_time')
            ->append(['role_id_val'])
            ->paginate($this->request->post('limit',$this->default_limit,'intval'),false,$query)
            ->toArray();
        $total = $data['total'];
        return self::success(compact('roles','data','total'));

    }

    /**
     * @authName (添加修改管理员)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (2)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save()
    {
        $input = $this->postValidate([
            'id|管理员ID'          => 'number',
            'login_name|登录名'    => 'require',
            'role_id|角色'         => 'require|array',
            'pwd|密码'             => 'length:6,25',
            'mobile|手机号'        => 'require|mobile',
        ]);

        // 去处重复的不存在的角色ID
        $role_all = AdminRole::where('status',1)->column('id');
        $input['role_id'] = array_unique($input['role_id']);
        foreach ($input['role_id'] as &$v) {
            if(!in_array($v,$role_all)) unset($v);
            $v = (int)$v;
        }

        // 判断密码
        if(empty($input['id'])) {
            if(empty($input['pwd'])) return $this->error('请输入密码');
        }else{
            if(empty($input['pwd'])) unset($input['pwd']);
        }

        // 处理密码
        if(!empty($input['pwd'])) {
            $input['pwd'] = ToolBag::password($input['pwd']);
        }
        // 保存
        return $this->success(Admin::saveData($input));
    }

    /**
     * @authName (修改管理员状态)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (3)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setStatus()
    {
        $id = $this->request->post('id',0,'intval');
        if($id == $this->request->admin->id) return $this->error('别想不开啊，小老弟');
        return $this->success(Admin::saveStatus($id));
    }

    
    /**
     * @authName (删除管理员)
     * @isCheck (true)
     * @menuID (0)
     * @authIndex (4)
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete()
    {
        $id = $this->request->post('id');
        if(is_array($id) && in_array($this->request->admin->id,$id)) {
            return $this->error('别想不开啊，小老弟');
        }else{
            if($id == $this->request->admin->id) return $this->error('别想不开啊，小老弟');
        }
        return $this->success(Admin::deleteData($id));
    }


    /**
     * 修改密码
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function savePwd()
    {
        $input = $this->postValidate([
            'oldPwd|原密码'       => 'require|length:6,25',
            'newPwd|新密码'      => 'require',
        ]);
        $admin = $this->request->admin;
        if(ToolBag::password($input['oldPwd']) != $admin['pwd']) return $this->error('原密码输入错误');
        if(ToolBag::password($input['newPwd']) == $admin['pwd']) return $this->error('新密码不能和原密码相同');
        if(!Admin::update([
            'id' => $admin['id'],
            'pwd' => ToolBag::password($input['newPwd'])
        ])) {
            return $this->error('修改密码失败');
        }
        return $this->success('');
    }


    /**
     * 修改个人信息
     * @return \think\response\Json
     * @throws \app\common\exception\AppRuntimeException
     * @throws \think\db\exception\DbException
     */
    public function saveAdminInfo()
    {
        $input = $this->postValidate([
            'mobile' => 'require|mobile',
        ]);
        // 保存信息
        $admin = $this->request->admin;
        if(!Admin::update([
            'id' => $admin['id'],
            'mobile' => $input['mobile'],
        ])){
            return $this->error('修改信息失败');
        }
        return $this->success('');
    }

    /**
     * 获取管理员所有权限
     * @return \think\response\Json
     */
    public function getAdminAuthList()
    {
        if(in_array(1001,$this->request->admin->role_id)) {
            $list = array_keys(AuthorityService::getAuthIndex());
        }else{
            $w = [
                ['id','in',$this->request->admin->role_id]
            ];
            $auth = AdminRole::where($w)->column('auth_list');
            $list = [];
            foreach ($auth as $v) {
                $list = array_merge($list, json_decode($v,true));
            }
        }
        return self::success(array_unique($list));
    }

}

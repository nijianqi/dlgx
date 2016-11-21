<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\NodeModel;

class Base extends Controller
{
    public function _initialize()
    {
        if(!session('username')){
            $this->redirect('login/index');
        }
        //检测权限
        $controller = request()->controller();
        $action = request()->action();
        //跳过登录以及主页的权限检测
        if(!in_array(strtolower($controller), ['login', 'index'])){
            if(!in_array(strtolower($controller . '/' . $action), session('action'))){
                $this->error('没有权限');
            }
        }
        //获取权限菜单
        $node = new NodeModel();
        $this->assign([
            'username' => session('username'),
            'menu' => $node->getMenu(session('permission_node')),
            'role_name' => session('role_name')
        ]);
    }
}

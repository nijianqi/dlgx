<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\model\RoleModel;
use org\Verify;

class Login extends Controller
{
    //登录页面
    public function index()
    {
        return $this->fetch();
    }
    //登录操作
    public function doLogin()
    {
        $username = input("param.username");
        $password = input("param.password");
        $code = input("param.code");

        $result = $this->validate(compact('username', 'password', "code"), 'LoginValidate');
        if(TRUE !== $result){
            return json(['code' => -5, 'data' => '', 'msg' => $result]);
        }

        $verify = new Verify();
        if (!$verify->check($code)) {
            return json(['code' => -4, 'data' => '', 'msg' => '验证码错误']);
        }

        $user = db('user')->where('username', $username)->find();
        if(empty($user)){
            return json(['code' => -3, 'data' => '', 'msg' => '该管理员不存在']);
        }

        if(md5($password) != $user['password']){
            return json(['code' => -2, 'data' => '', 'msg' => '密码错误']);
        }

        if(1 != $user['status']){
            return json(['code' => -1, 'data' => '', 'msg' => '该管理员被禁用']);
        }

        //获取该管理员的角色信息
        $roleModel = new RoleModel();
        $info = $roleModel->getRoleInfo($user['role_id']);

        session('username', $username);
        session('id', $user['id']);
        session('role_name', $info['role_name']);  //角色名
        session('permission_node', $info['permission_node']);  //权限节点
        session('action', $info['action']);  //角色权限

        //更新管理员状态
        $param = [
            'login_times' => $user['login_times'] + 1,
            'last_login_ip' => request()->ip(),
            'last_login_time' => time()
        ];

        db('user')->where('id', $user['id'])->update($param);

        return json(['code' => 1, 'data' => url('index/index'), 'msg' => '登录成功']);
    }
    //验证码
    public function checkVerify()
    {
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->length = 4;
        $verify->useNoise = false;
        $verify->fontSize = 14;

        return $verify->entry();
    }
    //退出
    public function loginOut()
    {
        session('username', null);
        session('id', null);
        session('role_name', null);  //角色名
        session('permission_node', null);  //权限节点
        session('action', null);  //角色权限

        $this->redirect('index/index');
    }
}
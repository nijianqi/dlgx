<?php
namespace app\admin\controller;

use app\admin\model\UserModel;
use app\admin\model\RoleModel;

class User extends Base
{
    //用户列表
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['username'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $user = new UserModel();
            $selectResult = $user->getUserByWhere($where,'*', $offset, $limit);
            $status = config('user_status');
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['status'] = $status[$vo['status']];
                $operate = [
                    '编辑' => url('user/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('".$vo['id']."')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $user->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加用户
    public function add()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['password'] = md5($param['password']);
            $user = new UserModel();
            $flag = $user->insert($param,'UserValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $role = new RoleModel();
        $roleList = $role->getListByWhere();
        $this->assign([
            'roleList' => $roleList,
            'status' => config('user_status')
        ]);
        return $this->fetch();
    }
    //编辑用户
    public function edit()
    {
        $user = new UserModel();
        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5($param['password']);
            }
            $flag = $user->edit($param,'UserValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $role = new RoleModel();
        $roleList = $role->getListByWhere();
        $this->assign([
            'user' => $user->getInfoById($id),
            'roleList' => $roleList,
            'status' => config('user_status'),
        ]);
        return $this->fetch();
    }
    //删除用户
    public function del()
    {
        $id = input('param.id');
        $user = new UserModel();
        $flag = $user->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
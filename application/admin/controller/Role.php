<?php
namespace app\admin\controller;

use app\admin\model\RoleModel;
use app\admin\model\NodeModel;

class Role extends Base
{
    //角色列表
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['role_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $user = new RoleModel();
            $selectResult = $user->getListByWhere($where,'*', $offset, $limit);
            foreach($selectResult as $key=>$vo){
                if(1 == $vo['id']){
                    $selectResult[$key]['operate'] = '';
                    continue;
                }
                $operate = [
                    '编辑' => url('role/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('".$vo['id']."')",
                    '分配权限' => "javascript:givePermission('".$vo['id']."')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $user->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加角色
    public function add()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $role = new RoleModel();
            $flag = $role->insert($param,'RoleValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }

    //编辑角色
    public function edit()
    {
        $role = new RoleModel();
        if(request()->isAjax()){
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $role->edit($param,'RoleValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'role' => $role->getRoleInfo($id)
        ]);
        return $this->fetch();
    }
    //删除角色
    public function del()
    {
        $id = input('param.id');
        $role = new RoleModel();
        $flag = $role->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    //分配权限
    public function givePermission()
    {
        $param = input('param.');
        $node = new NodeModel();
        //获取现在的权限
        if('get' == $param['type']){
            $nodeStr = $node->getNodeInfo($param['id']);
            return json(['code' => 1, 'data' => $nodeStr, 'msg' => 'success']);
        }
        //分配新权限
        if('give' == $param['type']){
            $doparam = [
                'id' => $param['id'],
                'permission_node' => $param['permission_node']
            ];
            $role = new RoleModel();
            $flag = $role->editAccess($doparam);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
    }
}
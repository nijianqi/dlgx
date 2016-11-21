<?php
namespace app\admin\model;

class RoleModel extends BaseModel
{
    protected  $table = 'dlgx_role';
    //分配权限
    public function editAccess($param)
    {
        try{
            $this->save($param, ['id' => $param['id']]);
            return ['code' => 1, 'data' => '', 'msg' => '权限分配成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    //获取角色信息
    public function getRoleInfo($id){
        $result = db('role')->where('id', $id)->find();
        if(empty($result['permission_node'])){
            $where = '';
        }else{
            $where = 'id in('.$result['permission_node'].')';
        }
        $res = db('node')->field('controller_name,action_name')->where($where)->select();
        foreach($res as $key=>$vo){
            if('#' != $vo['action_name']){
                $result['action'][] = $vo['controller_name'] . '/' . $vo['action_name'];
            }
        }
        return $result;
    }
}
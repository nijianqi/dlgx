<?php
namespace app\admin\model;

class UserModel extends BaseModel
{
    protected $table = 'dlgx_user';

    public function getUserByWhere($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_user.*,role_name')->join('dlgx_role', 'dlgx_user.role_id = dlgx_role.id')->where($where)->limit($offset, $limit)->order('id desc')->select();
    }
}
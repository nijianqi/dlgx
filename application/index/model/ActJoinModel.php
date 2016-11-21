<?php
namespace app\index\model;

class ActJoinModel extends BaseModel
{
    protected $table = "dlgx_act_join";

    public function getJoinMember($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_act_join.*,member_icon,member_name')
            ->join('dlgx_member', 'dlgx_act_join.member_id = dlgx_member.id')
            ->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    public function getJoinAct($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_act_join.*,act_name,act_list_img,act_release_time,act_start_time,act_end_time')
            ->join('dlgx_activity', 'dlgx_act_join.act_id = dlgx_activity.id')
            ->where($where)->limit($offset, $limit)->order('id desc')->select();
    }
}

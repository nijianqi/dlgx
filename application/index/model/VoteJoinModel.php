<?php
namespace app\index\model;

class VoteJoinModel extends BaseModel
{
    protected $table = "dlgx_vote_join";

    public function getJoinMember($where = array(), $offset = 0, $limit = 0,$order = 'id desc')
    {
        return $this->field('dlgx_vote_join.*,member_icon,member_name,member_school,real_name,member_sex,member_department,member_class,member_tel')
            ->join('dlgx_member', 'dlgx_vote_join.member_id = dlgx_member.id')
            ->where($where)->limit($offset, $limit)->order($order)->select();
    }
}

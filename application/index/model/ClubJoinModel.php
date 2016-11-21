<?php
namespace app\index\model;

class ClubJoinModel extends BaseModel
{
    protected $table = "dlgx_club_join";

    public function getJoinClub($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_club_join.*,club_name,club_school,club_intro')
            ->join('dlgx_club', 'dlgx_club_join.club_id = dlgx_club.id')
            ->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    public function getJoinMember($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_club_join.*,member_name,member_icon')
            ->join('dlgx_member', 'dlgx_club_join.member_id = dlgx_member.id')
            ->where($where)->limit($offset, $limit)->order('id desc')->select();
    }
}

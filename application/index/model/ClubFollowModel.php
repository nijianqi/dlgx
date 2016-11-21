<?php
namespace app\index\model;

class ClubFollowModel extends BaseModel
{
    protected $table = "dlgx_club_follow";
    public function insertFollow($member_id,$clubId,$is_follow) //插入社团关注
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['club_id'] = $clubId;
        $param['is_follow'] = $is_follow;
        $param['apply_time'] = time();
        $return['flag'] = $this->insert($param);
        if($return['flag']['code'] = 1){
            if($is_follow == 2){
                $message_content = '关注了你的社团';
            }
            $messageModel = new MessageModel();
            $ClubModel = new ClubModel();
            $club = $ClubModel->getInfoById($clubId);
            $toMemberId = $club['club_owner_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content);
        }
        return $return;
    }
    public function updateFollow($member_id,$clubId,$is_follow) //修改社团关注
    {
        $param = [];
        $where = [];
        $where['club_id'] = $clubId;
        $where['member_id'] = $member_id;
        $param['is_follow'] = $is_follow;
        $param['apply_time'] = time();
        $return['flag'] = $this->updateByWhere($param,'',$where);
        if($return['flag']['code'] = 1){
            if($is_follow == 2){
                $message_content = '关注了你的社团';
            }else{
                $message_content = '取消了对你社团的关注';
            }
            $messageModel = new MessageModel();
            $ClubModel = new ClubModel();
            $club = $ClubModel->getInfoById($clubId);
            $toMemberId = $club['club_owner_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content);
        }
        return $return;
    }
    public function getFollowMember($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_club_follow.*,member_icon,member_name')
            ->join('dlgx_member', 'dlgx_club_follow.member_id = dlgx_member.id')
            ->where($where)->limit($offset, $limit)->order('id desc')->select();
    }
}

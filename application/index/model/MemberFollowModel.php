<?php
namespace app\index\model;

class MemberFollowModel extends BaseModel
{
    protected $table = "dlgx_member_follow";

    public function insertFollow($member_id,$toMemberId,$is_follow) //插入会员关注
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['to_member_id'] = $toMemberId;
        $param['is_follow'] = $is_follow;
        $param['apply_time'] = time();
        $return['flag'] = $this->insert($param);
        if($return['flag']['code'] = 1){
            $message_content = '关注了你';
            $messageModel = new MessageModel();
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content);
        }
        return $return;
    }
    public function updateFollow($member_id,$toMemberId,$is_follow) //修改会员关注
    {
        $param = [];
        $where = [];
        $where['to_member_id'] = $toMemberId;
        $where['member_id'] = $member_id;
        $param['is_follow'] = $is_follow;
        $param['apply_time'] = time();
        $return['flag'] = $this->updateByWhere($param,'',$where);
        if($return['flag']['code'] = 1){
            if($is_follow == 2){
                $message_content = '关注了你';
            }else{
                $message_content = '取消了对你的关注';
            }
            $messageModel = new MessageModel();
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content);
        }
        return $return;
    }
}

<?php
namespace app\index\model;

class VoteComLikeModel extends BaseModel
{
    protected $table = "dlgx_vote_comlike";

    public function insertLike($member_id,$commentId,$is_like) //赞了话题
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['comment_id'] = $commentId;
        $voteCommentModel = new VoteCommentModel();
        $memberModel = new MemberModel();
        $comment_info = $voteCommentModel->getInfoById($commentId);
        $member_info = $memberModel->getInfoById($comment_info['member_id']);
        $comment_info['member_name'] =$member_info['member_name'];
        $param['is_like'] = $is_like;
        $param['apply_time'] = time();
        $return['flag'] = $this->insert($param);
        if($return['flag']['code'] = 1){
            $message_content = $comment_info['member_name'].": ".$comment_info['comment_content'];
            $comment_content = '赞了你的评论';
            $messageModel = new MessageModel();
            $toMemberId = $comment_info['member_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'1',$comment_content,'0','0');
        }
        return $return;
    }
 }

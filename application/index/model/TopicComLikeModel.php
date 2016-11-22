<?php
namespace app\index\model;

class TopicComLikeModel extends BaseModel
{
    protected $table = "dlgx_topic_comlike";

    public function insertLike($member_id,$commentId,$is_like) //赞了话题
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['comment_id'] = $commentId;
        $topicCommentModel = new TopicCommentModel();
        $memberModel = new MemberModel();
        $comment_info = $topicCommentModel->getInfoById($commentId);
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
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'1',$comment_content,'0',$comment_info['topic_id']);
        }
        return $return;
    }
    public function updateLike($member_id,$commentId,$is_like) //取消了对话题的赞
    {
        $param = [];
        $where = [];
        $where['member_id'] = $member_id;
        $where['comment_id'] = $commentId;
        $topicCommentModel = new TopicCommentModel();
        $memberModel = new MemberModel();
        $comment_info = $topicCommentModel->getInfoById($commentId);
        $member_info = $memberModel->getInfoById($comment_info['member_id']);
        $comment_info['member_name'] =$member_info['member_name'];
        $param['is_like'] = $is_like;
        $param['apply_time'] = time();
        print_r($param);
        $return['flag'] = $this->updateByWhere($param,'',$where);
        if($return['flag']['code'] = 1){
            if($is_like == 2){
                $message_content = $comment_info['member_name'].": ".$comment_info['comment_content'];
                $comment_content = '赞了你的评论';
            }else{
                $message_content = $comment_info['member_name'].": ".$comment_info['comment_content'];
                $comment_content = '取消了对你评论的赞';
            }
            $messageModel = new MessageModel();
            $toMemberId = $comment_info['member_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'1',$comment_content,'0',$comment_info['topic_id']);
        }
        return $return;
    }
}

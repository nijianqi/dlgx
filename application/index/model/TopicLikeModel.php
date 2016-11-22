<?php
namespace app\index\model;

class TopicLikeModel extends BaseModel
{
    protected $table = "dlgx_topic_like";

    public function insertLike($member_id,$topicId,$is_like) //赞了话题
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['topic_id'] = $topicId;
        $topicModel = new TopicModel();
        $memberModel = new MemberModel();
        $topic_info = $topicModel->getInfoById($topicId);
        $member_info = $memberModel->getInfoByWhere(array('id'=>$topic_info['topic_owner_id']));
        $topic_info['member_name'] =$member_info['member_name'];
        $param['is_like'] = $is_like;
        $param['apply_time'] = time();
        $return['flag'] = $this->insert($param);
        if($return['flag']['code'] = 1){
            $message_content = $topic_info['member_name'].": ".$topic_info['topic_name'];
            $comment_content = '赞了你的话题';
            $messageModel = new MessageModel();
            $toMemberId = $topic_info['topic_owner_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'1',$comment_content,'0',$topicId);
        }
        return $return;
    }
    public function updateLike($member_id,$topicId,$is_like) //取消了对话题的赞
    {
        $param = [];
        $where = [];
        $where['topic_id'] = $topicId;
        $where['member_id'] = $member_id;
        $topicModel = new TopicModel();
        $memberModel = new MemberModel();
        $topic_info = $topicModel->getInfoById($topicId);
        $member_info = $memberModel->getInfoByWhere(array('id'=>$topic_info['topic_owner_id']));
        $topic_info['member_name'] =$member_info['member_name'];
        $param['is_like'] = $is_like;
        $param['apply_time'] = time();
        $return['flag'] = $this->updateByWhere($param,'',$where);
        if($return['flag']['code'] = 1){
            if($is_like == 2){
                $message_content = $topic_info['member_name'].": ".$topic_info['topic_name'];
                $comment_content = '赞了你的话题';
            }else{
                $message_content = $topic_info['member_name'].": ".$topic_info['topic_name'];
                $comment_content = '取消了对你话题的赞';
            }
            $messageModel = new MessageModel();
            $toMemberId = $topic_info['topic_owner_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'1',$comment_content,'0',$topicId);
        }
        return $return;
    }
}

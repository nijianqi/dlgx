<?php
namespace app\index\model;

class TopicCollectModel extends BaseModel
{
    protected $table = "dlgx_topic_collect";
    public function insertCollect($member_id,$topicId,$is_collect) //收藏了话题
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['topic_id'] = $topicId;
        $topicModel = new TopicModel();
        $memberModel = new MemberModel();
        $topic_info = $topicModel->getInfoById($topicId);
        $member_info = $memberModel->getInfoByWhere(array('id'=>$topic_info['topic_owner_id']));
        $topic_info['member_name'] =$member_info['member_name'];
        $param['is_collect'] = $is_collect;
        $param['apply_time'] = time();
        $return['flag'] = $this->insert($param);
        if($return['flag']['code'] = 1){
            if($is_collect == 2){
                $message_content = $topic_info['member_name'].": ".$topic_info['topic_name'];
                $comment_content = "收藏了你的话题: ";
            }
            $messageModel = new MessageModel();
            $toMemberId = $topic_info['topic_owner_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'0',$comment_content,'1',$topicId);
        }
        return $return;
    }
    public function updateCollect($member_id,$topicId,$is_collect) //取消了对话题的收藏
    {
        $param = [];
        $where = [];
        $where['topic_id'] = $topicId;
        $where['member_id'] = $member_id;
        $topicModel = new TopicModel();
        $topic_info = $topicModel->getInfoById($topicId);
        $memberModel = new MemberModel();
        $member_info = $memberModel->getInfoByWhere(array('id'=>$topic_info['topic_owner_id']));
        $topic_info['member_name'] =$member_info['member_name'];
        $param['is_collect'] = $is_collect;
        $param['apply_time'] = time();
        $return['flag'] = $this->updateByWhere($param,'',$where);
        if($return['flag']['code'] = 1){
            if($is_collect == 2){
                $message_content = $topic_info['member_name'].": ".$topic_info['topic_name'];
                $comment_content = "收藏了你的话题: ";
            }else{
                $message_content = $topic_info['member_name'].": ".$topic_info['topic_name'];
                $comment_content = "取消收藏了你的话题: ";
            }
            $messageModel = new MessageModel();
            $toMemberId = $topic_info['topic_owner_id'];
            $messageModel->insertMessage(session('memberId'),$toMemberId,$message_content,'0',$comment_content,'1',$topicId);
        }
        return $return;
    }
}

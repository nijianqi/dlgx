<?php
namespace app\index\model;

class TopicCommentModel extends BaseModel
{
    protected $table = "dlgx_topic_comment";

    public function insertComment($topic_id,$comment,$commentId = 0) //评论了话题
    {
        $params = [];
        $params['topic_id'] = $topic_id;
        $TopicModel = new TopicModel();
        $topic = $TopicModel->getInfoById($topic_id);
        $params['member_id'] = session('memberId');
        if($commentId > 0){
            $topicComment = $this->getInfoById($commentId);
            $params['to_member_id'] = $topicComment['member_id'];
        }
        $params['comment_create_time'] = time();
        $params['comment_content'] = $comment;
        $return = $this->insertGetId($params);
        if($return){
            if($commentId > 0 ){
                $message_content = '回复了你的评论'.": ".$topicComment['comment_content'];
                $comment_content = $comment;
                $message_type = 2;
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'),$topicComment['member_id'],$message_content,$message_type,$comment_content,'0',$topic_id,$return);
            }else{
                $message_content = '评论了你的话题'.": ".$topic['topic_name'];
                $comment_content = $comment;
                $message_type = 2;
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'),$topic['topic_owner_id'],$message_content,$message_type,$comment_content,'0',$topic_id,$return);
            }
        }
        return $return;
    }
}

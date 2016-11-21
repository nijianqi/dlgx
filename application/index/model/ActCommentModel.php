<?php
namespace app\index\model;

class ActCommentModel extends BaseModel
{
    protected $table = "dlgx_activity_comment";
    public function insertComment($actId,$comment,$commentId = 0) //评论了活动
    {
        $params = [];
        $params['act_id'] = $actId;
        $ActivityModel = new ActivityModel();
        $activity = $ActivityModel->getInfoById($actId);
        $params['member_id'] = session('memberId');
        if($commentId > 0 ) {
            $actComment = $this->getInfoById($commentId);
            $params['to_member_id'] = $actComment['member_id'];
        }
        $params['comment_create_time'] = time();
        $params['comment_content'] = $comment;
        $return= $this->insertGetId($params);
        if($return){
            if($commentId > 0 ){
                $message_content = '回复了你的评论'.": ".$actComment['comment_content'];
                $comment_content = $comment;
                $message_type = 2;
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'),$actComment['member_id'],$message_content,$message_type,$comment_content,$actId,'0',$return);
            }else{
                $message_content = '评论了你的活动'.": ".$activity['act_name'];
                $comment_content = $comment;
                $message_type = 2;
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'),$activity['act_from_id'],$message_content,$message_type,$comment_content,$actId,'0',$return);
            }
        }
        return $return;
    }
}

<?php
namespace app\index\model;

class VoteCommentModel extends BaseModel
{
    protected $table = "dlgx_vote_comment";
    public function insertComment($voteId,$comment,$commentId = 0,$JoinId = 0) //评论了投票活动
    {
        $params = [];
        $params['vote_id'] = $voteId;
        $params['member_id'] = session('memberId');
        if($commentId > 0 ) {
            $voteComment = $this->getInfoById($commentId);
            $params['to_member_id'] = $voteComment['member_id'];
        }else{
            $params['to_member_id'] = $JoinId;
        }
        $params['comment_create_time'] = time();
        $params['join_id'] = $JoinId;
        $params['comment_content'] = $comment;
        $return= $this->insertGetId($params);
        if($return){
            if($commentId > 0 ){
                $message_content = '回复了你在投票活动中的评论'.": ".$voteComment['comment_content'];
                $comment_content = $comment;
                $message_type = 2;
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'),$voteComment['member_id'],$message_content,$message_type,$comment_content,'0','0',$return);
            }else{
                $message_content = '评论了你的投票活动中的信息';
                $comment_content = $comment;
                $message_type = 2;
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'),$JoinId,$message_content,$message_type,$comment_content,'0','0',$return);
            }
            $array['code'] = 1;
            $array['msg'] = '评论成功';
        }
        return $return;
    }
}

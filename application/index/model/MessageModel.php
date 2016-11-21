<?php
namespace app\index\model;

class MessageModel extends BaseModel
{
    protected $table = "dlgx_message";

    public function insertMessage($member_id,$toMemberId,$message_content,$message_type="1",$comment_content="",$act_id = '0',$topic_id = '0',$comment_id = '0') //插入消息
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['to_member_id'] = $toMemberId;
        $param['comment_content'] = $comment_content;
        $param['message_content'] = $message_content;
        $param['message_type'] = $message_type;
        $param['act_id'] = $act_id;
        $param['topic_id'] = $topic_id;
		$param['comment_id'] = $comment_id;
        $param['create_time'] = time();
        $this->insertGetId($param);
    }
}

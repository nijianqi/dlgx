<?php
namespace app\admin\model;

class MessageModel extends BaseModel
{
    protected $table = 'dlgx_message';

    public function insertMessage($member_id,$toMemberId,$message_content,$message_type="1",$comment_content="") //插入消息
    {
        $param = [];
        $param['member_id'] = $member_id;
        $param['to_member_id'] = $toMemberId;
        $param['comment_content'] = $comment_content;
        $param['message_content'] = $message_content;
        $param['message_type'] = $message_type;
        $param['create_time'] = time();
        $this->insertGetId($param);
    }
}
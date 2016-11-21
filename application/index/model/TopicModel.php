<?php
namespace app\index\model;

class TopicModel extends BaseModel
{
    protected $table = 'dlgx_topic';
    public function getTopicMember($where = array(), $offset = 0, $limit = 0, $order = 'id desc')
    {
        return $this->field('dlgx_topic.*,member_icon,member_name')
            ->join('dlgx_member', 'dlgx_topic.topic_owner_id = dlgx_member.id')
            ->where($where)->limit($offset, $limit)->order($order)->select();
    }
}
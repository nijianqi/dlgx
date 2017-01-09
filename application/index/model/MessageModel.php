<?php
namespace app\index\model;

use app\index\controller\Member;

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
        $flag = $this->insertGetId($param);
        if(!empty($flag)){
            $memberModel = new Member();
            $memberInfo = $memberModel->getInfoById($toMemberId);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=ACCESS_TOKEN';
            $post_data['touser']       = $memberInfo['member_openid'];
            $post_data['template_id']      = 'B0TldCRq2ljtRcaPMIsQlJJQAmjhscoUOtwPxmFfrec';
            $post_data['url'] = 'https://www.dlgx888.com/index/member/index.html';
            $data['first'] = array('value'=>'您有一条消息，请查收','color'=>'#173177');
            if(!empty($comment_id)){
                $data['keyword1'] = array('value'=>'评论消息','color'=>'#173177');
            }else{
                $data['keyword1'] = array('value'=>'普通消息','color'=>'#173177');
            }
            $data['keyword2'] = array('value'=> time(),'color'=>'#173177');
            $data['remark'] = array('value'=> $message_content ,'color'=>'#173177');
            $post_data['data']    = $data;
            $o = "";
            foreach ( $post_data as $k => $v )
            {
                $o.= "$k=" . urlencode( $v ). "&" ;
            }
            $post_data = substr($o,0,-1);

            $this->request_post($url, $post_data);
        }

    }
    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     */
    function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}

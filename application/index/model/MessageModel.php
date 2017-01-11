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
        $flag = $this->insertGetId($param);
        if(!empty($flag)){
            $memberModel = new MemberModel();
            $memberInfo = $memberModel->getInfoById($toMemberId);
            $appId = 'wxd53d2b1ef188dca7';//大乐个学
            $secret = 'aafdb067ff2aef548c50541392cf44b8';
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appId . '&secret=' . $secret . '';
            $result = $this->get_url_contents($url);
            $params = json_decode($result);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$params->access_token;
            $data = array(
                'touser' => $memberInfo['member_openid'], // openid是发送消息的基础
                'template_id' => 'B0TldCRq2ljtRcaPMIsQlJJQAmjhscoUOtwPxmFfrec', // 模板id
                'url' => 'https://www.dlgx888.com/index/member/index.html', // 点击跳转地址
                'data' => array(
                    'first' => array('value' => '您有一条消息，请查收'),
                    'keyword1' => array('value' => '新的消息'),
                    'keyword2' => array('value' => date('Y年m月d日 H:i', time())),
                    'remark' => array('value' => $message_content),
                )
            );
            $this->http_curl($url,'post','json',json_encode($data));
        }
    }
    function get_url_contents($url)
    {
        if (ini_get('allow_url_fopen') == 1) return file_get_contents($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    function http_curl($url,$type='get',$res='json',$arr=""){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        if($type=='post'){
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$arr);
            $output = curl_exec($ch);
            curl_close($ch);
            if($res=='json'){
                return json_decode($output,true);
            }
        }
    }
}




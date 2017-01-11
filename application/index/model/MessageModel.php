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
		$likestr = strstr($comment_content,"赞了你的话题");
		$comlikestr = strstr($comment_content,"赞了你的评论");
        if(!empty($flag)&&empty($likestr)&&empty($comlikestr)){
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
                'url' => 'https://www.dlgx888.com/index/member/message.html', // 点击跳转地址
                'data' => array(
                    'first' => array('value' => '您有一条消息，请查收'),
                    'keyword1' => array('value' => '新的消息'),
                    'keyword2' => array('value' => date('Y年m月d日 H:i', time())),
                    'remark' => array('value' => $message_content),
                )
            );
           // $this->http_curl($url,'post','json',json_encode($data));
		   $this->curlPost($url,json_encode($data),'POST');
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
	function curlPost($url,$data,$method){  
    $ch = curl_init();   //1.初始化  
    curl_setopt($ch, CURLOPT_URL, $url); //2.请求地址  
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);//3.请求方式  
    //4.参数如下  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//https  
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');//模拟浏览器  
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);  
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept-Encoding: gzip, deflate'));//gzip解压内容  
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');  
      
    if($method=="POST"){//5.post方式的时候添加数据  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
    }  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    $tmpInfo = curl_exec($ch);//6.执行  
  
    if (curl_errno($ch)) {//7.如果出错  
        return curl_error($ch);  
    }  
    curl_close($ch);//8.关闭  
    return $tmpInfo;  
} 
}




<?php
namespace Org\Api\Wechat;
use think\Session;

//返回消息类型：文本
define('RESP_MESSAGE_TYPE_TEXT','text');
//返回消息类型：音乐
define('RESP_MESSAGE_TYPE_MUSIC','music');
//返回消息类型：图文
define('RESP_MESSAGE_TYPE_NEWS','news');
//请求消息类型：文本
define('REQ_MESSAGE_TYPE_TEXT','text');
//请求消息类型：图片
define('REQ_MESSAGE_TYPE_IMAGE','image');
//请求消息类型：链接
define('REQ_MESSAGE_TYPE_LINK','link');
//请求消息类型：地理位置
define('REQ_MESSAGE_TYPE_LOCATION','location');
//请求消息类型：音频
define('REQ_MESSAGE_TYPE_VOICE','voice');
//请求消息类型：推送
define('REQ_MESSAGE_TYPE_EVENT','event');
//事件类型：subscribe(订阅)
define('EVENT_TYPE_SUBSCRIBE','subscribe');
//事件类型：unsubscribe(取消订阅)
define('EVENT_TYPE_UNSUBSCRIBE','unsubscribe');
//事件类型：CLICK(自定义菜单点击事件)
define('EVENT_TYPE_CLICK','CLICK');
//请求消息类型：音频
define('EVENT_TYPE_SCAN','SCAN');

class MessageUtil {

    use \traits\controller\Jump;

    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    public function toXml($data){
        if(!is_array($data) || count($data) <= 0){
            throw new WxPayException('数组数据异常！');
        }
        $xml = '<xml>';
        foreach ($data as $key=>$val){
            if (is_numeric($val)){
                $xml.='<'.$key.'>'.$val.'</'.$key.'>';
            }else{
                $xml.='<'.$key.'><![CDATA['.$val.']]></'.$key.'>';
            }
        }
        $xml.='</xml>';
        return $xml;
    }
    /**
     * 将xml转为array
     * @param  $$xml xml字符串
     * @return array       转换得到的数组
     */
    public function toArray($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }
    /**
     * 回复文本信息
     * @param ToUserName
     * @param FromUserName
     * @param CreateTime
     * @param MsgType
     * @param Content
     * @return
     */
     public function textTp($ToUserName,$FromUserName,$CreateTime,$Content){
        $str='<xml>' .
            '<ToUserName><![CDATA['.$ToUserName.']]></ToUserName>'.
            '<FromUserName><![CDATA['.$FromUserName.']]></FromUserName> ' .
            '<CreateTime>'.$CreateTime.'</CreateTime>' .
            '<MsgType><![CDATA[text]]></MsgType>' .
            '<Content><![CDATA['.$Content.']]></Content>' .
            '<MsgId>1234567890123456</MsgId>'.
            '</xml>';
         return json_decode(json_encode($this->toArray($str)));
     }
    /**
     * 回复图片信息
     * @param ToUserName
     * @param FromUserName
     * @param CreateTime
     * @param MsgType
     * @param media_id
     * @return
     */
    public function ImagTp($ToUserName,$FromUserName,$CreateTime,$media_id){
        $str='<xml>' .
            '<ToUserName><![CDATA['.$ToUserName.']]></ToUserName>' .
            '<FromUserName><![CDATA['.$FromUserName.']]></FromUserName> ' .
            '<CreateTime>'.$CreateTime.'</CreateTime>' .
            '<MsgType><![CDATA[image]]></MsgType>' .
            '<Image>'.
            '<MediaId><![CDATA['.$media_id.']]></MediaId>' .
            '</Image>'.
            '</xml>';
		return $str;
	}

	/**
     * 回复单图文
     * @param ToUserName
     * @param FromUserName
     * @param CreateTime
     * @param title
     * @param description
     * @param picurl
     * @param url
     * @return
     */
	public function SingleImageText($ToUserName,$FromUserName,$CreateTime,$title,$description,$picurl, $url){
        $str='<xml>' . 
            '<ToUserName><![CDATA['.$ToUserName.']]></ToUserName>' . 
            '<FromUserName><![CDATA['.$FromUserName.']]></FromUserName> ' . 
            '<CreateTime>'.$CreateTime.'</CreateTime>' . 
            '<MsgType><![CDATA[news]]></MsgType>' . 
            '<ArticleCount>1</ArticleCount>'. 
            '<Articles><item>'. 
            '<Title><![CDATA['.$title.']]></Title> '. 
            '<Description><![CDATA['.$description.']]></Description>'. 
            '<PicUrl><![CDATA['.$picurl.']]></PicUrl>'. 
            '<Url><![CDATA['.$url.']]></Url>'. 
            '</item></Articles>'. 
            '</xml>';
		return $str;
	}

	/**
     * 多图文
     * @param ToUserName
     * @param FromUserName
     * @param CreateTime
     * @param map
     * @return
     */
	public function MuImageText($ToUserName,$FromUserName,$CreateTime,$map){

        $str='<xml>' .
             '<ToUserName><![CDATA['.$ToUserName.']]></ToUserName>' .
             '<FromUserName><![CDATA['.$FromUserName.']]></FromUserName> ' .
             '<CreateTime>'.$CreateTime.'</CreateTime>' .
             '<MsgType><![CDATA[news]]></MsgType>' .
             '<ArticleCount>'.$map.count().'</ArticleCount>'.
             '<Articles>';
        foreach($map as $v=>$a){
            $news=$a['news'];
                $str=$str.
                    '<item>'.
                    '<Title><![CDATA['.$news['title'].']]></Title> '.
                    '<Description><![CDATA['.$news['description'].']]></Description>'.
                    '<PicUrl><![CDATA['.$news['picurl'].']]></PicUrl>'.
                    '<Url><![CDATA['.$news['url'].']]></Url>'.
                    '</item>';
        }
        $str=$str.'</Articles>'.
            '</xml>';
		return $str;
	}


	public function MusicText($ToUserName, $FromUserName, $CreateTime, $title, $description, $hqmusicurl, $musicurl){

        $str='<xml>' .
            '<ToUserName><![CDATA['.$ToUserName.']]></ToUserName>' .
            '<FromUserName><![CDATA['.$FromUserName.']]></FromUserName> ' .
            '<CreateTime>'.$CreateTime.'</CreateTime>' .
            '<MsgType><![CDATA[music]]></MsgType>' .
            '<ArticleCount>1</ArticleCount>'.
            '<Music>'.
            '<Title><![CDATA['.$title.']]></Title> '.
            '<Description><![CDATA['.$description.']]></Description>'.
            '<MusicUrl><![CDATA['.$hqmusicurl.']]></MusicUrl>'.
            '<HQMusicUrl><![CDATA['.$musicurl.']]></HQMusicUrl>'.
            '</Music>'.
            '</xml>';
		return $str;
	}

    /**
     * 发送文字
     * @param $openId
     * @param $content
     * @return json
     */
    public function sendText($openId,$content){
        $data=[
            'touser'=>$openId,
            'msgtype'=>"text",
            'text'=>[
                'content'=>$content
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }
    /**
     * 发送图片消息
     * @param $openId
     * @param $mediaId
     * @return json
     */
    public function sendImage($openId,$mediaId){
        $data=[
            'touser'=>$openId,
            'msgtype'=>"image",
            'image'=>[
                'media_id'=>$mediaId
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }
    /**
     * 发送语音消息
     * @param $openId
     * @param $mediaId
     * @return json
     */
    public function sendVoice($openId,$mediaId){
        $data=[
            'touser'=>$openId,
            'msgtype'=>"voice",
            'voice'=>[
                'media_id'=>$mediaId
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }
    /**
     * 发送视频消息
     * @param $openId
     * @param $mediaId
     * @param $title
     * @param $description
     * @return json
     */
    public function sendVideo($openId,$mediaId,$title,$description){
        $data=[
            'touser'=>$openId,
            'msgtype'=>"video",
            'video'=>[
                'media_id'=>$mediaId,
                'thumb_media_id'=>$mediaId,
                'title'=>$title,
                'description'=>$description
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }

    /**
     * 发送音乐消息
     * @param $openId
     * @param $title
     * @param $description
     * @param $musicurl
     * @param $hqmusicurl
     * @param $mediaId
     * @return json
     */
    public function sendMusic($openId,$title,$description,$musicurl,$hqmusicurl,$mediaId){
        $data=[
            'touser'=>$openId,
            'msgtype'=>"music",
            'music'=>[
                'title'=>$title,
                'description'=>$description,
                'musicurl'=>$musicurl,
                'hqmusicurl'=>$hqmusicurl,
                'thumb_media_id'=>$mediaId,
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }

    /**
     * 发送图文消息
     * @param $openId
     * @param $articles
     * @return json
     */
    public function sendNews($openId,$articles){
        $data=[
            'touser'=>$openId,
            'msgtype'=>"news",
            'news'=>$articles,
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);

    }
}

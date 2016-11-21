<?php
namespace Org\Api\Wechat;
use think\Session;
use app\admin\model\MemberModel;
use app\admin\model\OrderModel;
use app\admin\model\QuestionModel;


class WechatUtil {

    use \traits\controller\Jump;
    // 定义配置项
    private $config=[
        // 微信支付APPID
        'appid'   => 'wxafea1eaaaf3b18ac',
        // 微信支付MCHID 商户收款账号
        'MCHID'   => '1379389602',
        // 微信支付KEY
        'KEY' => '37d88d047c4343e2515aacdbeff7044a',
        //公众帐号secert
        'APPSECRET' => '370a57f55cf6d23bcfcae76034b0fa97',
        // 接收登陆状态的连接
        'REDIRECT_URL'=> 'http://jianwen.hztuen.com/api/Wechat/index',
        // 接收支付状态的连接
        'NOTIFY_URL'=> 'http://jianwen.hztuen.com/api/Wechat/notify',
        // 手机端主页
        'MOBILE_URL'=> 'http://jianwen.hztuen.com/mobile/Index/choice',
    ];

    private $startTime = null;
    private $jsApi_ticket = null;
    private $access_token = null;

    /**
     * 架构方法 设置参数
     * @access public
     * @param  array $config 配置参数
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->startTime=session('startTime');
        $this->access_token=session('access_token');
        $this->jsApi_ticket=session('jsapi_ticket');
    }
    /**
     * 使用 $this->name 获取配置
     * @access public
     * @param  string $name 配置名称
     * @return multitype    配置值
     */
    public function __get($name)
    {
        return $this->config[$name];
    }
    /**
     * 设置验证码配置
     * @access public
     * @param  string $name 配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }
    /**
     * 检查配置
     * @access public
     * @param  string $name 配置名称
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 获取apiTicket
     * @return array 返回apiTicket
     */
    public function getJsApiTicket(){
        $refreshFlag = false;
		if($this->startTime == null){
            Session::set('startTime',time());
            $refreshFlag = true;
        }else{
            $now=time();
			$timestamp = $now - $this->startTime;
			if($timestamp > 7000){
                Session::set('startTime',$now);
                $refreshFlag = true;
            }
		}
		if($refreshFlag == true){
			$jsApi_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->getToken()."&type=jsapi";
            $result=curl_get_contents($jsApi_url);
            $result=json_decode($result,true);
            Session::set('jsapi_ticket',$result['ticket']);
			return session('jsapi_ticket');
		}else{
            return session('jsapi_ticket');
        }
    }
    /**
     * 获取Token
     * @return array 返回Token
     */
    public function getToken(){
        $refreshFlag = false;
        if($this->startTime == null){
            Session::set('startTime',time());
            $refreshFlag = true;
        }else{
            $now=time();
            $timestamp = $now - $this->startTime;
            if($timestamp > 7000){
                Session::set('startTime',$now);
            }
            $refreshFlag = true;
        }
        if($refreshFlag == true){
            $access_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->config['appid']."&secret=".$this->config['APPSECRET'];
            $result=curl_get_contents($access_token_url);
            $result=str_replace('access_token{','{',$result);
            $result=json_decode($result,true);
            Session::set('access_token',$result['access_token']);
            return session('access_token');
        }else{
            return session('access_token');
        }
    }

    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function getJsConfig(){

        // 获取当前时间戳
        $time=time();
        // 组合jssdk需要用到的数据
        $nonceStr=createRandomStr(16);
        $data=array(
            'jsapi_ticket'=>$this->getJsApiTicket(), //jsapi_ticket
            'nonceStr'=>$nonceStr,// 随机字符串
            'timeStamp'=>strval($time), //时间戳
            'signType'=>'MD5'//加密方式
        );
        // 生成签名
        $data['signature']=$this->makeSign($data);
        return $data;
    }
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($data){
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        $config=$this->config;
        $string_sign_temp=$string_a."&key=".$config['KEY'];
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }
    /**
     * 用户微信端打开,进行用户登陆操作,获取openid并保存信息到数据库
     * @return openid
     */
    public function getOpenid(){
        if (!isset($_GET['code'])) {
            $redirect_uri=$this->config['REDIRECT_URL'];
            $redirect_uri=urlencode($redirect_uri);
            $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->config['appid']."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
            $this->redirect($url);
        }else{
            // 如果有code参数；则表示获取到openid
            $code=input('code');
            $state=input('state');
           // 组合获取prepay_id的url
            $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->config['appid'].'&secret='.$this->config['APPSECRET'].'&code='.$code.'&grant_type=authorization_code';
            // curl获取prepay_id
            $result=curl_get_contents($url);
            $result=json_decode($result,true);
            $openid=$result['openid'];
            $memberService=new MemberModel();
            $member=$memberService->getMemberByOpenId($openid);
            if($member==null){
                $url='https://api.weixin.qq.com/sns/userinfo?access_token='.$result['access_token'].'&openid='.$openid.'&lang=zh_CN';
                // curl获取prepay_id
                $result=curl_get_contents($url);
                $result=json_decode($result,true);
                $add=[
                    'create_date'=>date('y-m-d h:i:s',time()),
                    'modify_date'=>date('y-m-d h:i:s',time()),
                    'nickname'=>$result['nickname'],
                    'name'=>$result['nickname'],
                    'introduction'=>'',
                    'prestige'=>'',
                    'fans'=>'',
                    'balance'=>'0',
                    'birth'=>'',
                    'email'=>'',
                    'is_enabled'=>'0',
                    'mobile'=>'',
                    'is_locked'=>'0',
                    'locked_date'=>'',
                    'login_date'=>date('y-m-d h:i:s',time()),
                    'login_failure_count'=>'',
                    'login_count'=>'',
                    'login_ip'=>'',
                    'password'=>'',
                    'openid'=>$result['openid'],
                    'city'=>$result['city'],
                    'country'=>$result['country'],
                    'head_img_url'=>$result['headimgurl'],
                    'province'=>$result['province'],
                    'sex'=>$result['sex'],
                   // 'unionid'=>$result['unionid'],
                    'type'=>'1'
                ];
                $id=$memberService->save($add);
                $member=$memberService->getMemberByOpenId($openid);
            }
            Session::set('member',$member);
            Session::set('MemberLoginOpenId',$member['openid']);
            Session::set('MemberLoginId',$member['id']);
            Session::set('MemberLoginNickname',$member['nickname']);
            $this->redirect($this->config['MOBILE_URL']);
        }
    }
    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    public function toXml($data){
        if(!is_array($data) || count($data) <= 0){
            throw new WxPayException("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($data as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array       转换得到的数组
     */
    public function toArray($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }
    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function getParameters($openId,$orderId){
        $memberService=new MemberModel();
        $orderService=new OrderModel();
        $member=$memberService->getMemberByOpenId($openId);
        $order=$orderService->info($orderId);
        $question= $orderService->getQuestion();
        $WxOrder=array(
            'body'=>$question['title'],// 商品描述（需要根据自己的业务修改）
            'total_fee'=>(int)$order['amount'],// 订单金额  以(分)为单位（需要根据自己的业务修改）
            'out_trade_no'=>$orderId,// 订单号（需要根据自己的业务修改）
            'product_id'=>$question['sn'],// 商品id（需要根据自己的业务修改）
            'trade_type'=>'JSAPI',// JSAPI公众号支付
            'openid'=>$openId// 获取到的openid
        );
        // 统一下单 获取prepay_id
        $unified_order=$this->unifiedOrder($WxOrder);
        // 组合jssdk需要用到的数据
        $data=array(
            'appId'=>$this->config['appid'], //appid
            'timeStamp'=>strval(time()), //时间戳
            'nonceStr'=>$unified_order['nonce_str'],// 随机字符串
            'package'=>'prepay_id='.$unified_order['prepay_id'],// 预支付交易会话标识
            'signType'=>'MD5'//加密方式
        );
        // 生成签名
        $data['paySign']=$this->makeSign($data);
        return $data;
    }
    /**
     * 统一下单
     * @param  array $order 订单 必须包含支付所需要的参数 body(产品描述)、total_fee(订单金额)、out_trade_no(订单号)、product_id(产品id)、trade_type(类型：JSAPI，NATIVE，APP)
     */
    public function unifiedOrder($order){
        // 获取配置项
        $config=$this->config;
        $data=array(
            'appid'=>$config['appid'],
            'body'=>$order['body'],
            'mch_id'=>$config['MCHID'],
            'nonce_str'=>createRandomStr(16),
            'notify_url'=>$config['NOTIFY_URL'],
            'out_trade_no'=>$order['out_trade_no'].strval(time()),
            'spbill_create_ip'=>'221.181.73.58',
            'total_fee'=>$order['total_fee'],
            'trade_type'=>'JSAPI',
            'openid'=>$order['openid']
        );
        // 生成签名
        $sign=$this->makeSign($data);
        $data['sign']=$sign;
        $xml=$this->toXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            // 显示报错信息；终止继续执行
            die(curl_error($ch));
        }
        curl_close($ch);
        $result=$this->toArray($response);
        // 显示错误信息
        if ($result['return_code']=='FAIL') {
            die('appid参数长度有误');
        }
        $result['sign']=$sign;
        return $result;
    }
    /**
     * param $json
     * @return array 返回apiTicket
     */
    public function sendCustomMessage($json){
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$this->getToken();//接收xml数据的文件
        $header[] = "Content-type: text/xml; charset=utf-8";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            // 显示报错信息；终止继续执行
            die(curl_error($ch));
        }
        curl_close($ch);
        $result=$this->toArray($response);
        return $result;
    }
    /**
     * 生成支付二维码
     * @param  array $order 订单 必须包含支付所需要的参数 body(产品描述)、total_fee(订单金额)、out_trade_no(订单号)、product_id(产品id)、trade_type(类型：JSAPI，NATIVE，APP)
     */
    public function pay($order){
        $result=$this->unifiedOrder($order);
        $decodeurl=urldecode($result['code_url']);
        qrcode($decodeurl);
    }




}

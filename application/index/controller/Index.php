<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\MemberModel;
use app\index\model\BannerModel;
use app\index\model\ActivityModel;
use app\index\model\VideoModel;
use app\index\model\ClubModel;
use app\index\model\ClubFollowModel;

class Index extends Controller
{
    public function index()
    {
        //第一步：请求code
        $appId = 'wxd53d2b1ef188dca7';//大乐个学
        $redirectUri = 'http://www.dlgx888.com/index.php/index/index/callback';
        $state = md5(uniqid(rand(), TRUE));
        session('state', $state);
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId
            . '&redirect_uri=' . $redirectUri . '&response_type=code&scope=snsapi_userinfo&state=' . $state . '#wechat_redirect';
        $this->redirect($url);
    }

    public function callback()
    {
        if (input('param.state') == session('state')) {
            $code = input('param.code');
            if (!isset($code)) {
                //用户没有同意授权
                $this->error('授权失败！');
                exit;
            }
            $appId = 'wxd53d2b1ef188dca7';//大乐个学
            $secret = 'aafdb067ff2aef548c50541392cf44b8';
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
            $result = $this->get_url_contents($url);
            if (strpos($result, 'errcode') != FALSE) {
                $msg = json_decode($result);
                if (isset($msg->errcode)) {
                    $this->error('error:' . $msg->errcode);
                    $this->error('msg:' . $msg->errmsg);
                    exit;
                }
            }
            $params = json_decode($result);
            session('accessToken', $params->access_token);
            session('openId', $params->openid);

            $userInfo = $this->get_user_info();
            $memberModel = new MemberModel();
            $hasMember = $memberModel->getListByWhere(array('member_openid' => $userInfo['openid']));
            if (empty($hasMember)) {
                $member = [];
                $member['member_openid'] = $userInfo['openid'];
                $member['member_icon'] = $userInfo['headimgurl'];
                $member['member_name'] = $userInfo['nickname'];
                $member['member_sex'] = $userInfo['sex'];
                $member['last_login_ip'] = request()->ip();
                $member['login_times'] = 1;
                $member['member_create_time'] = time();
                $member['last_login_time'] = $member['member_create_time'];
                $memberId = db('member')->insertGetId($member);
                session('memberId', $memberId);
            } else {
                $member = [];
                $member['id'] = $hasMember[0]['id'];
                $member['last_login_time'] = time();
                $member['last_login_ip'] = request()->ip();
                $member['login_times'] = $hasMember[0]['login_times'] + 1;
                db('member')->update($member);
                session('memberId', $hasMember[0]['id']);
            }
            session('memberIcon', $userInfo['headimgurl']);
            session('memberName', $userInfo['nickname']);

            $membermodel = new Membermodel();
			$memberInfo = $membermodel->getInfoById(session('memberId'));
			if($memberInfo['member_status'] == 2){
			   $this->redirect('member/index');
			}else{
			   $this->redirect('index/show');
		    } 
        } else {
            $this->error('The state does not match. You may be a victim of CSRF.');
            exit;
        }
    }

    public function show() //首页活动展示
    {
        if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        $bannerWhere = [];  //轮播图-滑动显示img
        $bannerWhere['banner_release_time'] = ['>', 0];
        $bannerModel = new BannerModel();
        $bannerList = $bannerModel->getListByWhere($bannerWhere, '*', 0, 3, 'banner_release_time');
        $this->assign([
            'bannerList' => $bannerList
        ]);

        $activityOnlineWhere = [];  //线上活动
        $activityOnlineWhere['act_release_time'] = ['>', 0];
        $activityOnlineWhere['act_type'] = 1; //线上
        $activityField = 'id,act_name,act_detail_img,act_release_time,act_start_time,act_end_time,act_from_id,act_type';
        $activityModel = new ActivityModel();
        $activityOnlineList = $activityModel->getListByWhere($activityOnlineWhere, $activityField, 0, 4, 'act_end_time desc');
        $this->assign([
            'activityOnlineList' => $activityOnlineList
        ]);

        $activityLineWhere = [];   //线下活动
        $activityLineWhere['act_release_time'] = ['>', 0];
        $activityLineWhere['act_type'] = 2; //线下
        $activityField = 'id,act_name,act_detail_img,act_release_time,act_start_time,act_end_time,act_from_id,act_type';
        $activityLineList = $activityModel->getListByWhere($activityLineWhere, $activityField, 0, 3, 'act_end_time desc');
        foreach ($activityLineList as $key => $vo) {
            $sTime = $vo['act_start_time'];
            $eTime = $vo['act_end_time'];
            if (time() < $sTime) {
                $activityLineList[$key]['act_status'] = 1;
            } elseif (time() > $eTime) {
                $activityLineList[$key]['act_status'] = 3;
            } else {
                $activityLineList[$key]['act_status'] = 2;
            }
            $activityLineList[$key]['act_start_time'] = date('Y.m.d', $sTime);
            $activityLineList[$key]['act_end_time'] = date('m.d', $eTime);
            $activityLineList[$key]['act_release_time'] = date('Y年m月d日', $vo['act_release_time']);
            $days = floor((time() - $activityLineList[$key]['act_release_time']) / 86400);
            if ($days == 0) {
                $activityLineList[$key]['act_days'] = '今天';
            } else {
                $activityLineList[$key]['act_days'] = $days . '天前';
            }
            if ($activityLineList[$key]['act_from_id'] == 0) {
                $activityLineList[$key]['act_from_name'] = '官方';
                $activityLineList[$key]['act_from_icon'] = '';
            } else {
                $memberModel = new MemberModel();
                $member_info = $memberModel->getInfoById($activityLineList[$key]['act_from_id']);
                $activityLineList[$key]['act_from_name'] = $member_info['member_name'];
                $activityLineList[$key]['act_from_icon'] = $member_info['member_icon'];
            }
        }
        $this->assign([
            'activityLineList' => $activityLineList
        ]);

        $videoWhere = [];
        $videoWhere['video_release_time'] = ['>', 0];
        $videoField = 'id,video_name,video_img,video_url,video_release_time';
        $videoModel = new VideoModel();
        $videoList = $videoModel->getListByWhere($videoWhere, $videoField);
        foreach ($videoList as $key => $vo) {
            $videoList[$key]['video_release_time'] = date('m月d日', $vo['video_release_time']);
        }
        $this->assign([
            'videoList' => $videoList
        ]);

        return $this->fetch('/index');
    }

    public function showClub()  //首页社团显示
    {
        if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        $memberId = session('memberId');
        $clubWhere = [];
        $clubWhere['club_create_time'] = ['>', 0];
        $clubWhere['club_status'] = 1;
        $clubField = 'id,club_name,club_school,club_icon,club_intro,club_owner_id';
        $clubModel = new ClubModel();
        $clubList = $clubModel->getListByWhere($clubWhere, $clubField, 0);

        $clubFollowModel = new ClubFollowModel();
        $clubFollowList = $clubFollowModel->getListByWhere(array('member_id' => $memberId));
        foreach ($clubList as $key => $vo) {
            foreach ($clubFollowList as $keyword => $volist) {
                if ($vo['id'] == $volist['club_id']) {
                    $clubList[$key]['is_follow'] = $volist['is_follow'];
                }

            }
        }
        $this->assign([
            'clubList' => $clubList,
            'clubFollowList' => $clubFollowList,
            'memberId' => $memberId
        ]);

        return $this->fetch('/index-club');
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

    function get_user_info()
    {
        $accessToken = session('accessToken');
        $openId = session('openId');
        $url = 'https://api.weixin.qq.com/sns/userinfo?' . 'access_token=' . $accessToken . '&openid=' . $openId;
        $info = $this->get_url_contents($url);
        $info = json_decode($info, true);
        return $info;
    }

}
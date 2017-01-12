<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\ClubModel;
use app\index\model\ClubJoinModel;
use app\index\model\ClubAlbumModel;
use app\index\model\ClubFollowModel;
use app\index\model\SendCodeModel;
use app\index\model\MemberModel;
use app\index\model\MemberFollowModel;
use app\index\model\MessageModel;
use app\index\model\ActivityModel;
use app\index\model\ActJoinModel;
use app\index\model\ActCommentModel;
use app\index\model\ActComAlbumModel;
use app\index\model\TopicModel;
use app\index\model\TopicCommentModel;
use app\index\model\TopicCollectModel;
use app\index\model\TopicLikeModel;
use app\index\model\TopicAlbumModel;
use app\index\model\TopComAlbumModel;
use org\Api\Alidayu\TopClient;
use org\Api\Alidayu\Request\AlibabaAliqinFcSmsNumSendRequest;

class Member extends Controller
{

    protected $beforeActionList = [
        'checkMember' => ['only' => 'index,sendCode,infoModify,follow,message,fansList,officialMessage,aboutMe,comment,societyDynamics,mineClubWatch,mineActivity,mineTopic,mineTopicCollect,mineWatch,personHomepage,userClub,userClubWatch,userActivity']
    ];

    public function checkMember()
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        }
        if($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
    }

    public function index() //我的主页
    {
        if (empty(session('memberId'))) {
            session('url', 'member/index');
            $this->redirect('index/index');
        }
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getInfoBywhere(array('id'=>session('memberId'),'member_status'=>1));
        $memberFollowModel = new MemberFollowModel();
        $memberFollowCounts = $memberFollowModel->getCounts(array('member_id' => session('memberId'), 'is_follow' => 2));//会员关注TA的数量
        $memberIsFollowCounts = $memberFollowModel->getCounts(array('to_member_id' => session('memberId'), 'is_follow' => 2));//会员粉丝的数量
        $message = new MessageModel();
        $messageCounts = $message->getCounts(array('member_id' => array('neq', session('memberId')), 'to_member_id' => session('memberId'), 'message_status' => 1));//指定会员消息的总数量
        $this->assign([
            'memberInfo' => $memberInfo,
            'memberFollowCounts' => $memberFollowCounts,
            'memberIsFollowCounts' => $memberIsFollowCounts,
            'messageCounts' => $messageCounts
        ]);
        return $this->fetch('/me');
    }

    public function sendCode()//发送短信验证码
    {
        if (request()->isAjax()) {
            $memberId = session('memberId');
            $code = rand(1000, 9999);
            $memberTel = input('param.memberTel');
            $sendCodeModel = new SendCodeModel();
            $codeList = $sendCodeModel->getListByWhere(array('member_id' => $memberId), '', 0, 0, '');
            if (empty($codeList)) {
                $params = [];
                $params['member_id'] = $memberId;
                $params['msg_code'] = $code;
                $params['send_time'] = time();
                $params['today'] = strtotime(date('Y-m-d', time()));
                $params['send_times'] = 1;
                $params['check_times'] = 0;
                $sendCodeModel->insert($params);
            } else {
                $codeInfo = $codeList[0];
                if (time() - $codeInfo['send_time'] < 60) {
                    return FALSE;
                }
                if (strtotime(date('Y-m-d', time())) == $codeInfo['today']) {
                    if ($codeInfo['send_times'] > 15) {
                        return json(['code' => -1, 'msg' => '您今天已获取超过15条验证码，请明天再试！']);
                    } else {
                        $params = [];
                        $params['member_id'] = $memberId;
                        $params['msg_code'] = $code;
                        $params['send_time'] = time();
                        $params['send_times'] = $codeInfo['send_times'] + 1;
                        $params['check_times'] = 0;
                        $sendCodeModel->updateByWhere($params, '', array('member_id' => session('memberId')));
                    }
                } else {
                    $params = [];
                    $params['member_id'] = $memberId;
                    $params['msg_code'] = $code;
                    $params['send_time'] = time();
                    $params['today'] = strtotime(date('Y-m-d', time()));
                    $params['send_times'] = 1;
                    $params['check_times'] = 0;
                    $sendCodeModel->updateByWhere($params, '', array('member_id' => session('memberId')));
                }
            }

            require_once APP_PATH . "../extend/org/Api/Alidayu/TopSdk.php";

            $c = new TopClient;
            $c->appkey = '23462399';
            $c->secretKey = '5df2c9bff2a3f0f858b9c0c4af001dd3';

            $req = new AlibabaAliqinFcSmsNumSendRequest;
            $req->setExtend($memberId);
            $req->setSmsType("normal");
            $req->setSmsFreeSignName("身份验证");
            $req->setSmsParam("{\"code\":\"$code\",\"product\":\"【大乐个学】\"}");
            $req->setRecNum($memberTel);
            $req->setSmsTemplateCode("SMS_15540418");

            $resp = $c->execute($req);

            if ($resp->result->success) {
                return json(['code' => 1, 'msg' => '验证码发送成功，请注意查收！']);
            } else {
                return json(['code' => 0, 'msg' => '验证码发送失败！']);
            }
        }
    }

    public function edit() //完善个人信息
    {
        if (request()->isAjax()) {
            $params = input('param.');
            $params = parseParams($params['data']);

            $sendCodeModel = new SendCodeModel();
            $codeList = $sendCodeModel->getListByWhere(array('member_id' => session('memberId')), '', 0, 0, '');
            if (!empty($codeList)) {
                $codeInfo = $codeList[0];
                if ($codeInfo['check_times'] > 5) {
                    return json(['code' => -3, 'msg' => '验证次数过多，请重新获取验证码！']);
                } else {
                    $sendCodeModel->updateByWhere(array('check_times' => $codeInfo['check_times'] + 1), '', array('member_id' => session('memberId')));
                    if ($codeInfo['msg_code'] == $params['code']) {
                        unset($params['code']);
                        $memberModel = new MemberModel();
                        $flag = $memberModel->edit($params, 'MemberValidate');
                        return json(['code' => $flag['code'], 'msg' => $flag['msg']]);
                    } else {
                        return json(['code' => -2, 'msg' => '验证码错误！']);
                    }
                }
            } else {
                return json(['code' => -1, 'msg' => '验证码错误！']);
            }
        } else {
            if (!empty(input('param.memberForm'))) {
                $memberInfo = parseParams(input('param.memberForm'));
                $memberInfo['member_school'] = input('param.schoolName');
            } else {
                $memberModel = new MemberModel();
                $memberInfo = $memberModel->getInfoById(session('memberId'));
            }
            $this->assign([
                'memberInfo' => $memberInfo
            ]);

            return $this->fetch('/info');
        }
    }

    public function infoModify() //修改个人信息
    {
        $clubAlbumModel = new ClubAlbumModel();
        if (request()->isPost()) {
            $params = input('param.');
            $logo = request()->file('portrait');
            if($logo){
                $logo = $clubAlbumModel->insertAlbum($logo);
                $params['member_icon'] = $logo[0];
            }
            $memberModel = new MemberModel();
            $memberModel->edit($params, 'MemberInfoValidate');
            $this->redirect('Member/index');
        } else {
            if (!empty(input('param.memberForm'))) {
                $memberInfo = parseParams(input('param.memberForm'));
                $memberInfo['member_icon'] = session('memberIcon');
                $memberInfo['member_school'] = input('param.schoolName');
            } else {
                $memberModel = new MemberModel();
                $memberInfo = $memberModel->getInfoById(session('memberId'));
            }
            $this->assign([
                'memberInfo' => $memberInfo
            ]);
            return $this->fetch('/info-modify');
        }
    }

    public function follow() //关注会员
    {
        $memberModel = new MemberModel();
        $memberFollowModel = new MemberFollowModel();
        $memberInfo = $memberModel->getInfoById(session('memberId'));
        if (empty($memberInfo['member_tel'])) {
            $return['flag'] = -1;
        } else {
            $toMemberId = input('param.id');
            $is_follow = input('param.is_follow');
            $memberFollowList = $memberFollowModel->getListByWhere(array('to_member_id' => $toMemberId, 'member_id' => session('memberId')));
            if (!empty($memberFollowList)) {
                    $return['flag'] = $memberFollowModel->updateFollow(session('memberId'), $toMemberId, $is_follow);
            } else {
                    $return['flag'] = $memberFollowModel->insertFollow(session('memberId'), $toMemberId, $is_follow);
            }

        }
        return json($return);
    }

    public function message() //我的消息
    {
		 if (empty(session('memberId'))) {
            session('url', 'member/message');
            $this->redirect('index/index');
        }
        $memberId = session('memberId');
        $message = new MessageModel();
        $messageOfficialCounts = $message->getCounts(array('member_id' => 0, 'to_member_id' => $memberId, 'message_type' => 3, 'message_status' => 1));//官方消息的数量
        $messageRelateCounts = $message->getCounts(array('member_id' => array('neq', session('memberId')), 'to_member_id' => session('memberId'), 'message_type' => array('lt', 3), 'message_status' => 1));//与我相关的数量
        $messageClubCounts = $message->getCounts(array('member_id' => array('neq', session('memberId')), 'to_member_id' => $memberId, 'message_type' => 4, 'message_status' => 1));//社团动态的数量
        $this->assign([
            'messageOfficialCounts' => $messageOfficialCounts,
            'messageRelateCounts' => $messageRelateCounts,
            'messageClubCounts' => $messageClubCounts,
            'memberId' => $memberId
        ]);
        return $this->fetch('/message');
    }

    public function fansList() //我的粉丝
    {
        $memberFollowModel = new MemberFollowModel();
        $memberFollowList = $memberFollowModel->getListByWhere(array('to_member_id' => session('memberId'), 'is_follow' => '2'));
        if (!empty($memberFollowList)) {
            foreach ($memberFollowList as $key => $vo) {
                $memberModel = new MemberModel();
                $member_info = $memberModel->getInfoBywhere(array('id'=>$vo['member_id'],'member_status'=>'1'));
                $memberFollowList[$key]['member_id'] = $member_info['id'];
                $memberFollowList[$key]['member_name'] = $member_info['member_name'];
                $memberFollowList[$key]['member_icon'] = $member_info['member_icon'];
                $memberFollowList[$key]['member_intro'] = $member_info['member_intro'];
                $memberFollowList[$key]['member_school'] = $member_info['member_school'];
                $memberIsFollowInfo = $memberFollowModel->getInfoByWhere(array('to_member_id' => $vo['member_id']));
                if (empty($memberIsFollowInfo)) {
                    $memberFollowList[$key]['is_follow'] = 1;
                } else {
                    $memberFollowList[$key]['is_follow'] = $memberIsFollowInfo['is_follow'];
                }
            }
        }
        $this->assign([
            'memberFollowList' => $memberFollowList
        ]);
        return $this->fetch('/fans-list');
    }

    public function followList() //我的关注的会员
    {
        $memberFollowModel = new MemberFollowModel();
        $memberFollowList = $memberFollowModel->getListByWhere(array('member_id' => session('memberId'), 'is_follow' => '2'));
        if (!empty($memberFollowList)) {
            foreach ($memberFollowList as $key => $vo) {
                $memberModel = new MemberModel();
                $member_info = $memberModel->getInfoBywhere(array('id'=>$vo['to_member_id'],'member_status'=>'1'));
                $memberFollowList[$key]['member_id'] = $member_info['id'];
                $memberFollowList[$key]['member_name'] = $member_info['member_name'];
                $memberFollowList[$key]['member_icon'] = $member_info['member_icon'];
                $memberFollowList[$key]['member_intro'] = $member_info['member_intro'];
                $memberFollowList[$key]['member_school'] = $member_info['member_school'];
            }
        }
        $this->assign([
            'memberFollowList' => $memberFollowList
        ]);
        return $this->fetch('/follow-list');
    }

    public function officialMessage() //官方消息
    {
        $message = new MessageModel();
        $messageOfficialList = $message->getListByWhere(array('member_id' => 0, 'to_member_id' => session('memberId'), 'message_type' => 3), '', '', '', 'create_time desc');//官方消息
        $message->updateByWhere(array('message_status' => 2), '', array('to_member_id' => session('memberId'), 'message_type' => 3, 'message_status' => 1));//所有改为已读
        foreach ($messageOfficialList as $key => $val) {
            $days = floor((time() - $messageOfficialList[$key]['create_time']) / 86400);
            if ($days == 0) {
                $messageOfficialList[$key]['act_days'] = '今天';
            } else {
                $messageOfficialList[$key]['act_days'] = $days . '天前';
            }
            $messageOfficialList[$key]['create_time'] = date('G:H', $messageOfficialList[$key]['create_time']);
        }
        $this->assign([
            'messageOfficialList' => $messageOfficialList
        ]);
        return $this->fetch('/official-message');
    }

    public function aboutMe() //与我相关
    {
        $message = new MessageModel();
        $messageList = $message->getListByWhere(array('member_id' => array('neq', session('memberId')), 'to_member_id' => session('memberId'), 'message_type' => array('lt', 3)), '', '', '', 'create_time desc');//与我有关消息
        $message->updateByWhere(array('message_status' => 2), '', array('to_member_id' => session('memberId'), 'message_type' => array('lt', 3), 'message_status' => 1));//所有改为已读
        foreach ($messageList as $key => $vo) {
            $memberModel = new MemberModel();
            $memberInfo = $memberModel->getInfoById($messageList[$key]['member_id']);
            $messageList[$key]['create_time'] = date('Y年m月d日 G:H', $messageList[$key]['create_time']);
            $messageList[$key]['member_name'] = $memberInfo['member_name'];
            $messageList[$key]['member_icon'] = $memberInfo['member_icon'];
            $messageList[$key]['member_id'] = $memberInfo['id'];
        }
        $this->assign([
            'messageList' => $messageList
        ]);
        return $this->fetch('/about-me');
    }

    public function comment() //与我相关评论
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $return['flag'] = -1;
        } else {
            $actId = input('param.act_id');
            $topicId = input('param.topic_id');
            $comment = input('param.comment');
            $commentId = input('param.comment_id');
            if(empty($commentId)){
                $commentId = 0;
            }
            if(!empty($actId)){
                $actCommentModel = new ActCommentModel();
                $return = $actCommentModel->insertComment($actId,$comment,$commentId);
                if(request()->file()){
                    $album = request()->file('file');
                    $actComAlbumModel = new ActComAlbumModel();
                    foreach($album as $key=>$val){
                        $album_img = $actComAlbumModel->insertAlbum($val);
                        $actComAlbumModel->insertGetId(array('comment_id'=>$return,'album_img'=>$album_img,'create_time'=>time()));
                    }
                }
            }elseif(!empty($topicId)){
                $topicCommentModel = new TopicCommentModel();
                $TopicModel = new TopicModel();
                $topic = $TopicModel->getInfoById($topicId);
                $TopicModel->update(array('topic_num'=>$topic['topic_num']+1),array('id'=>$topicId));
                if(empty($commentId)){
                    $commentId = 0;
                }
                $return = $topicCommentModel->insertComment($topicId,$comment,$commentId);
                if(request()->file()){
                    $album = request()->file('file');
                    $topComAlbumModel = new TopComAlbumModel();
                    foreach($album as $key=>$val){
                        $album_img = $topComAlbumModel->insertAlbum($val);
                        $topComAlbumModel->insertGetId(array('comment_id'=>$return,'album_img'=>$album_img,'create_time'=>time()));
                    }
                }
            }else{
                echo "<script>alert('活动或话题不存在');</script>";
            }
        }

    }

    public function societyDynamics() //社团动态
    {
        $memberId = session('memberId');
        $message = new MessageModel();
        $clubJoinModel = new ClubJoinModel();
        $clubJoinList = $clubJoinModel->getListBywhere(array('member_id' => session('memberId')));//会员加入的社团列表
        if (!empty($clubJoinList)) {
            foreach ($clubJoinList as $key => $val) {
                $clubModel = new ClubModel();
                $clubInfo = $clubModel->getInfoByWhere(array('id' => $val['club_id'], 'club_status' => 1));
                $messageList = $message->getListByWhere(array('member_id' => array('neq', session('memberId')), 'to_member_id' => $memberId, 'message_type' => 4));//社团动态的消息
                $message->updateByWhere(array('message_status' => 2), '', array('to_member_id' => session('memberId'), 'message_type' => 4, 'message_status' => 1));//所有改为已读
                foreach ($messageList as $keyword => $vo) {
                    $messageList[$keyword]['create_time'] = date('Y年m月d日 H:i', $vo['create_time']);
                    $messageList[$keyword]['club_name'] = $clubInfo['club_name'];
                    $messageList[$keyword]['club_icon'] = $clubInfo['club_icon'];
                    $messageList[$keyword]['club_id'] = $clubInfo['id'];
                }
                $clubJoinList[$key]['messageList'] = $messageList;
            }
        }
        $this->assign([
            'clubJoinList' => $clubJoinList
        ]);
        return $this->fetch('/society-dynamics');
    }

    public function mineClub() //我加入的社团
    {
        $clubJoinModel = new ClubJoinModel();
        $clubJoinList = $clubJoinModel->getListByWhere(array('member_id' => session('memberId')));
        if (!empty($clubJoinList)) {
            foreach ($clubJoinList as $key => $vo) {
                $clubModel = new ClubModel();
                $clubInfo = $clubModel->getInfoByWhere(array('id' => $vo['club_id'], 'club_status' => 1));
				if($clubInfo){
				$clubFollowModel = new ClubFollowModel();
                $clubFollowInfo = $clubFollowModel->getInfoByWhere(array('member_id' => session('memberId'), 'club_id' => $clubInfo['id']));
                if($clubInfo['club_owner_id'] == session('memberId')){
                    $clubInfo['is_follow'] = 3;
                }else{
                    $clubInfo['is_follow'] = $clubFollowInfo['is_follow'];
                }
                $clubJoinList[$key]['clubInfo'] = $clubInfo;
				}
            }
            $this->assign([
                'clubJoinList' => $clubJoinList
            ]);
        }
        return $this->fetch('/mine-club');
    }

    public function mineClubWatch() //我关注的社团
    {
        $clubFollowModel = new ClubFollowModel();
        $clubFollowList = $clubFollowModel->getListByWhere(array('member_id' => session('memberId'), 'is_follow' => 2));
        if (!empty($clubFollowList)) {
            foreach ($clubFollowList as $key => $vo) {
                $clubModel = new ClubModel();
                $clubInfo = $clubModel->getInfoByWhere(array('id' => $vo['club_id'], 'club_status' => 1));
                if($clubInfo['club_owner_id'] == session('memberId')){
                    $clubInfo['is_follow'] = 3;
                }
                $clubFollowList[$key]['clubInfo'] = $clubInfo;
            }
            $this->assign([
                'clubFollowList' => $clubFollowList
            ]);
        }
        return $this->fetch('/mine-club-watch');
    }

    public function mineActivity() //我的活动
    {
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getInfoById(session('memberId'));
        $actType = input('param.act_type');
        $actJoinModel = new ActJoinModel();
        $actJoinList = $actJoinModel->getListByWhere(array('member_id' => session('memberId')));
        if ($actJoinList) {
            foreach ($actJoinList as $key => $value) {
                $activityModel = new ActivityModel();
                $activityInfo = $activityModel->getInfoByWhere(array('id' => $value['act_id'], 'act_type' => $actType,'act_release_time'=>array("neq",'0')));
                if ($activityInfo) {
                    $stime = $activityInfo['act_start_time'];
                    $etime = $activityInfo['act_end_time'];
                    if (time() < $stime) {
                        $activityInfo['act_status'] = 1;
                    } elseif (time() > $etime) {
                        $activityInfo['act_status'] = 3;
                    } else {
                        $activityInfo['act_status'] = 2;
                    }
                    $activityInfo['act_start_time'] = date('Y.m.d', $stime);
                    $activityInfo['act_end_time'] = date('m.d', $etime);
                    $activityInfo['act_release_time'] = date('Y年m月d日', $activityInfo['act_release_time']);
                    $days = floor((time() - $activityInfo['act_release_time']) / 86400);
                    if ($days == 0) {
                        $activityInfo['act_days'] = '今天';
                    } else {
                        $activityInfo['act_days'] = $days . '天前';
                    }
                    if ($activityInfo['act_from_id'] == 0) {
                        $activityInfo['act_from_name'] = '官方';
                        $activityInfo['act_from_icon'] = '';
                    } else {
                        $memberModel = new MemberModel();
                        $member_info = $memberModel->getInfoById($activityInfo['act_from_id']);
                        $activityInfo['act_from_name'] = $member_info['member_name'];
                        $activityInfo['act_from_icon'] = $member_info['member_icon'];
                    }
                    $actJoinList[$key]['activityInfo'] = $activityInfo;
                    $this->assign([
                        'actJoinList' => $actJoinList
                    ]);
                }
            }
        }
        $this->assign([
            'memberInfo' => $memberInfo,
        ]);
        if ($actType == 1) {
            return $this->fetch('/mine-onactivity');//线上活动
        } else {
            return $this->fetch('/mine-offactivity');//线下活动
        }
    }

    public function mineTopic() //我发表的话题
    {
        $topicModel = new TopicModel();
        $topicList = $topicModel->getListByWhere(array('topic_owner_id' => session('memberId'),'topic_status'=>'1'));
        foreach ($topicList as $key => $val) {
            $topicAlbumModel = new TopicAlbumModel();
            $topicAlbumList = $topicAlbumModel->getListByWhere(array('topic_id' => $val['id']),'','','3');
            $topicList[$key]['topicAlbumList'] = $topicAlbumList;
            $topicAlbumCounts = $topicAlbumModel->getCounts(array('topic_id' => $val['id']));
            $topicList[$key]['topicAlbumCounts'] = $topicAlbumCounts;
            $memberModel = new MemberModel();
            $memberInfo = $memberModel->getInfoById(session('memberId'));
            $topicList[$key]['memberInfo'] = $memberInfo;
            $topicList[$key]['topic_create_time'] = date('m月d日 h:m ', $val['topic_create_time']);
            $topicCommentModel = new TopicCommentModel();
            $topicList[$key]['topic_comment_count'] = $topicCommentModel->getCounts(array('topic_id' => $val['id']));
            $topicLikeModel = new TopicLikeModel();
            $topicList[$key]['topic_like_count'] = $topicLikeModel->getCounts(array('topic_id' => $val['id'], 'is_like' => 2));
            $topicCollectModel = new TopicCollectModel();
            $topicList[$key]['topic_collect_count'] = $topicCollectModel->getCounts(array('topic_id' => $val['id'], 'is_collect' => 2));
        }
        $this->assign([
            'topicList' => $topicList,
        ]);
        return $this->fetch('/mine-topic');
    }

    public function mineTopicCollect() //我收藏的话题
    {
        $topicCollectModel = new TopicCollectModel();
        $topicCollectList = $topicCollectModel->getListByWhere(array('member_id' => session('memberId'), 'is_collect' => 2));
        foreach ($topicCollectList as $key => $val) {
            $topicModel = new TopicModel();
            $topicInfo = $topicModel->getInfoByWhere(array('id'=>$val['topic_id'],'topic_status'=>'1'));
            $topicAlbumModel = new TopicAlbumModel();
            $topicAlbumList = $topicAlbumModel->getListByWhere(array('topic_id' => $val['topic_id']),'','','3');
            $topicInfo['topicAlbumList'] = $topicAlbumList;
            $topicAlbumCounts = $topicAlbumModel->getCounts(array('topic_id' => $val['topic_id']));
            $topicInfo['topicAlbumCounts'] = $topicAlbumCounts;
            $memberModel = new MemberModel();
            $memberInfo = $memberModel->getInfoById($topicInfo['topic_owner_id']);
            $topicInfo['memberInfo'] = $memberInfo;
            $memberFollowModel = new MemberFollowModel();
            $memberFollowInfo = $memberFollowModel->getInfoByWhere(array('member_id' => session('memberId'), 'to_member_id' => $memberInfo['id']));
            $topicInfo['member_is_follow'] = $memberFollowInfo['is_follow'];
            $topicInfo['topic_create_time'] = date('m月d日 h:m ', $topicInfo['topic_create_time']);
            $topicCommentModel = new TopicCommentModel();
            $topicInfo['topic_comment_count'] = $topicCommentModel->getCounts(array('topic_id' => $val['topic_id']));
            $topicLikeModel = new TopicLikeModel();
            $topicInfo['topic_like_count'] = $topicLikeModel->getCounts(array('topic_id' => $val['topic_id'], 'is_like' => 2));
            $topicCollectModel = new TopicCollectModel();
            $topicInfo['topic_collect_count'] = $topicCollectModel->getCounts(array('topic_id' => $val['topic_id'], 'is_collect' => 2));
            $topicCollectList[$key]['topicInfo'] = $topicInfo;
        }
        $this->assign([
            'topicCollectList' => $topicCollectList,
        ]);
        return $this->fetch('/mine-topic-collect');
    }

    public function mineWatch() //我的关注
    {
        $memberFollowModel = new MemberFollowModel();
        $memberFollowList = $memberFollowModel->getInfoByWhere(array('member_id' => session('memberId'), 'is_follow' => '2'));
        if (!empty($memberFollowList)) {
            foreach ($memberFollowList as $key => $vo) {
                $memberModel = new MemberModel();
                $member_info = $memberModel->getInfoBywhere(array('id'=>$vo['member_id'],'member_status'=>'1'));
                $memberFollowList[$key]['member_name'] = $member_info['member_name'];
                $memberFollowList[$key]['member_icon'] = $member_info['member_icon'];
                $memberFollowList[$key]['member_intro'] = $member_info['member_intro'];
                $memberFollowList[$key]['member_school'] = $member_info['member_school'];
                $memberFollowList[$key]['is_follow'] = 2;
            }
        }
        $this->assign([
            'memberFollowList' => $memberFollowList
        ]);
        return $this->fetch('/mine-watch');
    }

    public function personHomepage() //TA的主页
    {
        $memberId = input('param.member_id');
        if ($memberId == session('memberId')) {
            $this->redirect('Member/index');
        }
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getInfoBywhere(array('id'=>$memberId,'member_status'=>'1'));
        $memberFollowModel = new MemberFollowModel();
        $memberFollowCounts = $memberFollowModel->getCounts(array('member_id' => $memberId, 'is_follow' => 2));//TA会员关注TA的数量
        $memberFollowInfo = $memberFollowModel->getInfoByWhere(array('to_member_id' => $memberId, 'member_id' => session('memberId')));
        $memberIsFollowCounts = $memberFollowModel->getCounts(array('to_member_id' => $memberId, 'is_follow' => 2));//TA会员粉丝的数量
        $clubJoinModel = new ClubJoinModel();
        $clubCounts = $clubJoinModel->getCounts(array('member_id' => $memberId,'verify_status'=>1));
        $actJoinModel = new ActJoinModel();
        $actJoinList = $actJoinModel->getListByWhere(array('member_id' => $memberId));
        if (!empty($actJoinList)) {
            foreach ($actJoinList as $key => $val) {
                $activityModel = new ActivityModel();
                $where = [];
                $where['id'] = $val['act_id'];
                $activityCounts = $activityModel->getCounts($where);
            }
        } else {
            $activityCounts = 0;
        }
        $topicModel = new TopicModel();
        $topicCounts = $topicModel->getCounts(array('topic_owner_id' => $memberId, 'topic_status' => 1));
        $topicList = $topicModel->getTopicMember(array('topic_owner_id' => $memberId, 'topic_status' => 1), '', '', '', 'topic_create_time desc');
        $topicAlbumModel = new TopicAlbumModel();
        if (!empty($topicList)) {
            foreach ($topicList as $key => $val) {
                $topicCommentModel = new TopicCommentModel();
                $topicList[$key]['topic_comment_counts'] = $topicCommentModel->getCounts(array('topic_id' => $val['id']));
                $topicLikeModel = new TopicLikeModel();
                $topicList[$key]['topic_like_counts'] = $topicLikeModel->getCounts(array('topic_id' => $val['id'], 'is_like' => 2));
                $topicCollectModel = new TopicCollectModel();
                $topicList[$key]['topic_collect_counts'] = $topicCollectModel->getCounts(array('topic_id' => $val['id'], 'is_collect' => 2));
                $topicAlbumList = $topicAlbumModel->getListByWhere(array('topic_id' => $val['id']), '', '', '3');
                $topicList[$key]['topicAlbumList'] = $topicAlbumList;
                $topicList[$key]['topic_create_time'] = date('m月d日 H:i:s', $val['topic_create_time']);
                $topicList[$key]['topicAlbumCounts'] = $topicAlbumModel->getCounts(array('topic_id' => $val['id']));
            }
        }
        $this->assign([
            'memberInfo' => $memberInfo,
            'memberFollowCounts' => $memberFollowCounts,
            'memberIsFollowCounts' => $memberIsFollowCounts,
            'clubCounts' => $clubCounts,
            'activityCounts' => $activityCounts,
            'topicCounts' => $topicCounts,
            'topicList' => $topicList,
            'memberFollowInfo' => $memberFollowInfo
        ]);
        return $this->fetch('/person-homepage');
    }

    public function userClub() //TA加入的社团
    {
        $memberId = input('param.member_id');
        if ($memberId == session('memberId')) {
            $this->redirect('Member/index');
        }
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getInfoBywhere(array('id'=>$memberId,'member_status'=>'1'));;
        $clubJoinModel = new ClubJoinModel();
        $clubJoinList = $clubJoinModel->getListByWhere(array('member_id' => $memberId));
        if (!empty($clubJoinList)) {
            foreach ($clubJoinList as $key => $vo) {
                $clubModel = new ClubModel();
                $clubInfo = $clubModel->getInfoByWhere(array('id' => $vo['club_id'], 'club_status' => 1));
                $clubFollowModel = new ClubFollowModel();
                $clubFollowInfo = $clubFollowModel->getInfoByWhere(array('member_id' => session('memberId'), 'club_id' => $clubInfo['id']));
                $clubInfo['is_follow'] = $clubFollowInfo['is_follow'];
                $clubJoinList[$key]['clubInfo'] = $clubInfo;
            }
        }
			$this->assign([
                'memberInfo' => $memberInfo,
                'clubJoinList' => $clubJoinList
            ]);
        return $this->fetch('/user-club');
    }

    public function userClubWatch() //TA关注的社团
    {
        $memberId = input('param.member_id');
        if ($memberId == session('memberId')) {
            $this->redirect('Member/index');
        }
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getInfoBywhere(array('id'=>$memberId,'member_status'=>'1'));;
        $clubFollowModel = new ClubFollowModel();
        $clubFollowList = $clubFollowModel->getListByWhere(array('member_id' => $memberId, 'is_follow' => 2));
        if (!empty($clubFollowList)) {
            foreach ($clubFollowList as $key => $vo) {
                $clubModel = new ClubModel();
                $clubInfo = $clubModel->getInfoByWhere(array('id' => $vo['club_id'], 'club_status' => 1));
                $clubFollowList[$key]['clubInfo'] = $clubInfo;
				$clubFollowInfo = $clubFollowModel->getInfoByWhere(array('member_id' => session('memberId'), 'club_id' => $vo['club_id']));
				$clubFollowList[$key]['is_follow'] = $clubFollowInfo['is_follow'];
            }
        }
		 $this->assign([
                'memberInfo' => $memberInfo,
                'clubFollowList' => $clubFollowList
            ]);
        return $this->fetch('/user-club-watch');
    }

    public function userActivity() //TA的活动
    {
        $memberId = input('param.member_id');
        if ($memberId == session('memberId')) {
            $this->redirect('Member/index');
        }
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getInfoBywhere(array('id'=>$memberId,'member_status'=>'1'));;
        $actType = input('param.act_type');
        $actJoinModel = new ActJoinModel();
        $actJoinList = $actJoinModel->getListByWhere(array('member_id' => $memberId));
        if ($actJoinList) {
            foreach ($actJoinList as $key => $value) {
                $activityModel = new ActivityModel();
                $activityInfo = $activityModel->getInfoByWhere(array('id' => $value['act_id'], 'act_type' => $actType));
                if ($activityInfo) {
                    $stime = $activityInfo['act_start_time'];
                    $etime = $activityInfo['act_end_time'];
                    if (time() < $stime) {
                        $activityInfo['act_status'] = 1;
                    } elseif (time() > $etime) {
                        $activityInfo['act_status'] = 3;
                    } else {
                        $activityInfo['act_status'] = 2;
                    }
                    $activityInfo['act_start_time'] = date('Y.m.d', $stime);
                    $activityInfo['act_end_time'] = date('m.d', $etime);
                    $activityInfo['act_release_time'] = date('Y年m月d日', $activityInfo['act_release_time']);
                    $days = floor((time() - $activityInfo['act_release_time']) / 86400);
                    if ($days == 0) {
                        $activityInfo['act_days'] = '今天';
                    } else {
                        $activityInfo['act_days'] = $days . '天前';
                    }
                    if ($activityInfo['act_from_id'] == 0) {
                        $activityInfo['act_from_name'] = '官方';
                        $activityInfo['act_from_icon'] = '';
                    } else {
                        $memberModel = new MemberModel();
                        $member_info = $memberModel->getInfoById($activityInfo['act_from_id']);
                        $activityInfo['act_from_name'] = $member_info['member_name'];
                        $activityInfo['act_from_icon'] = $member_info['member_icon'];
                    }
                    $actJoinList[$key]['activityInfo'] = $activityInfo;
                    $this->assign([
                        'actJoinList' => $actJoinList
                    ]);
                }
            }
        }
        $this->assign([
            'memberInfo' => $memberInfo,
        ]);
        if ($actType == 1) {
            return $this->fetch('/user-onactivity');//线上活动
        } else {
            return $this->fetch('/user-offactivity');//线下活动
        }
    }
}

<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\MemberModel;
use app\index\model\ClubModel;
use app\index\model\ClubJoinModel;
use app\index\model\ClubApplyModel;
use app\index\model\ActivityModel;
use app\index\model\ClubFollowModel;
use app\index\model\ClubTypeModel;
use app\index\model\ClubAlbumModel;
use app\index\model\MessageModel;
use app\admin\model\ClubRuleModel;
use app\index\model\ClubExperienceModel;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;

class Club extends Controller
{
    protected $beforeActionList = [
        'checkMember' => ['except' => 'index,clubAlbum,AlbumManager,delAlbum,clubHome,clubJoined,create,join,cancel,follow,notice,member_list,del_member,clubList']
    ];

    public function checkMember()
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if (empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if (empty($member['member_tel'])) {
            $this->redirect('member/edit');
        }
        if ($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
    }

    public function index() //社团主页
    {
        $clubId = input('param.clubId');
        $clubModel = new ClubModel();
        $club = $clubModel->getInfoById($clubId);
        if (empty($club)) {
            $this->error('该社团不存在或被禁用!', url('index/show'));
        }
        $memberId = session('memberId');
        if ($club['club_owner_id'] == $memberId) {
            $this->redirect('club/clubHome', array('clubId' => $clubId), 3, '页面跳转中~');
        }
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById($club['club_owner_id']);

        $clubJoinModel = new ClubJoinModel();
        $memberList = $clubJoinModel->getJoinMember(array('club_id' => $clubId), '0', '5');
        $memberListAll = $clubJoinModel->getJoinMember(array('club_id' => $clubId));
        foreach ($memberListAll as $key => $vo) {
            if ($vo['member_id'] == session('memberId')) {
                $isJoin = 1;
            }
        }
        if (empty($isJoin)) {
            $isJoin = '';
        }
        $counts = $clubJoinModel->getCounts(array('club_id' => $clubId));

        $activityModel = new ActivityModel();
        $activityList = $activityModel->getListByWhere(array('act_from_id' => $club['club_owner_id']));
        foreach ($activityList as $key => $vo) {

            $stime = $vo['act_start_time'];
            $etime = $vo['act_end_time'];
            if (time() < $stime) {
                $activityList[$key]['act_status'] = 1;
            } elseif (time() > $etime) {
                $activityList[$key]['act_status'] = 3;
            } else {
                $activityList[$key]['act_status'] = 2;
            }
            $activityList[$key]['act_name'] = mb_substr($vo['act_name'], 0, 32);
            $activityList[$key]['act_start_time'] = date('Y.m.d', $stime);
            $activityList[$key]['act_end_time'] = date('m.d', $etime);
            $activityList[$key]['act_release_time'] = date('Y年m月d日', $vo['act_release_time']);
            $days = floor((time() - $activityList[$key]['act_release_time']) / 86400);
            if ($days == 0) {
                $activityList[$key]['act_days'] = '今天';
            } else {
                $activityList[$key]['act_days'] = $days . '天前';
            }
        }

        $clubFollowModel = new ClubFollowModel();
        $clubFollowInfo = $clubFollowModel->getInfoByWhere(array('club_id' => $clubId, 'member_id' => session('memberId')));
        $followCounts = $clubFollowModel->getCounts(array('club_id' => $clubId, 'is_follow' => '2'));
        $clubExperienceModel = new ClubExperienceModel();
        $Counts = $clubExperienceModel->getCounts(array('member_id' => session('memberId'), 'content' => ['like', '%社团签到%'], 'create_time' => array(array('gt', strtotime(date('Y-m-d'))), array('lt', strtotime(date('Y-m-d', strtotime('+1 day')))))));

        $clubAlbumModel = new ClubAlbumModel();
        $clubAlbumList = $clubAlbumModel->getListByWhere(array('club_id' => $clubId), '', '', '3');
        $this->assign([
            'club' => $club,
            'member' => $member,
            'memberList' => $memberList,
            'isJoin' => $isJoin,
            'counts' => $counts,
            'activityList' => $activityList,
            'clubFollowInfo' => $clubFollowInfo,
            'followCounts' => $followCounts,
            'clubAlbumList' => $clubAlbumList,
            'Counts' => $Counts
        ]);

        return $this->fetch('/club-homepage');
    }

    public function clubAlbum() //社团相册展示
    {
        $clubId = input('param.club_id');
        $clubAlbumModel = new ClubAlbumModel();
        $clubAlbumList = $clubAlbumModel->getListByWhere(array('club_id' => $clubId));
        $this->assign([
            'clubAlbumList' => $clubAlbumList,
        ]);
        return $this->fetch('/club-album');
    }

    public function AlbumManager() //社团相册上传
    {
        $memberId = session('memberId');
        $clubModel = new ClubModel();
        $clubInfo = $clubModel->getInfoByWhere(array('club_owner_id' => $memberId));
        $clubAlbumModel = new ClubAlbumModel();
        $clubAlbumList = $clubAlbumModel->getListByWhere(array('club_id' => $clubInfo['id']));
        $this->assign([
            'clubAlbumList' => $clubAlbumList,
        ]);
        if (request()->ispost()) {
            $album = request()->file('album');
            $clubAlbumModel = new ClubAlbumModel();
            if ($album) {
                $album_img = $clubAlbumModel->insertAlbum($album);
                $param = [];
                $param['club_id'] = $clubInfo['id'];
                $param['club_name'] = $clubInfo['club_name'];
                $param['image_name'] = $album_img[1];
                $param['album_img'] = $album_img[0] . '?imageMogr2/size-limit/300k';
                $param['create_time'] = time();
                $clubAlbumModel->insert($param);
                $this->redirect('club/AlbumManager', '', 3, '页面跳转中~');
            }
        }
        return $this->fetch('/club-album-manager');
    }

    public function delAlbum() //社团相册删除
    {
        $id = input('param.img_id');
        require APP_PATH . '../vendor/qiniu/autoload.php';
        //用于签名的公钥和私钥
        $accessKey = config('ACCESSKEY');
        $secretKey = config('SECRETKEY');
        //初始化Auth状态
        $auth = new Auth($accessKey, $secretKey);
        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);
        //你要测试的空间， 并且这个key在你空间中存在
        $bucket = config('BUCKET');
        $clubAlbumModel = new ClubAlbumModel();
        $info = $clubAlbumModel->getInfoById($id);
        $key = $info['image_name'];
        //删除$bucket 中的文件 $key
        $err = $bucketMgr->delete($bucket, $key);
        if ($err != null) {
            return FALSE;
        } else {
            $return['flag'] = $clubAlbumModel->del($id);
            return json($return);
        }

    }

    public function clubHome() //我的社团主页
    {
        $clubId = input('param.clubId');
        $clubModel = new ClubModel();
        $club = $clubModel->getInfoById($clubId);
        $self = $clubModel->getInfoByWhere(array('id' => $clubId, 'club_owner_id' => session('memberId'), 'club_status' => '1'));
        if (!empty($self)) {
            $status = 1;
        } else {
            $status = 0;
        }
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById($club['club_owner_id']);

        $clubJoinModel = new ClubJoinModel();
        $memberList = $clubJoinModel->getJoinMember(array('club_id' => $clubId), '0', '5');
        $counts = $clubJoinModel->getCounts(array('club_id' => $clubId));

        $activityModel = new ActivityModel();
        $activityList = $activityModel->getListByWhere(array('act_from_id' => session('memberId')));
        foreach ($activityList as $key => $vo) {

            $stime = $vo['act_start_time'];
            $etime = $vo['act_end_time'];
            if (time() < $stime) {
                $activityList[$key]['act_status'] = 1;
            } elseif (time() > $etime) {
                $activityList[$key]['act_status'] = 3;
            } else {
                $activityList[$key]['act_status'] = 2;
            }
            $activityList[$key]['act_name'] = mb_substr($vo['act_name'], 0, 32);
            $activityList[$key]['act_start_time'] = date('Y.m.d', $stime);
            $activityList[$key]['act_end_time'] = date('m.d', $etime);
            $activityList[$key]['act_release_time'] = date('Y年m月d日', $vo['act_release_time']);
            $days = floor((time() - $activityList[$key]['act_release_time']) / 86400);
            if ($days == 0) {
                $activityList[$key]['act_days'] = '今天';
            } else {
                $activityList[$key]['act_days'] = $days . '天前';
            }
        }

        $clubFollowModel = new ClubFollowModel();
        $clubFollowInfo = $clubFollowModel->getInfoByWhere(array('club_id' => $clubId, 'member_id' => session('memberId')));
        $followCounts = $clubFollowModel->getCounts(array('club_id' => $clubId, 'is_follow' => '2'));
        $clubExperienceModel = new ClubExperienceModel();
        $Counts = $clubExperienceModel->getCounts(array('member_id' => session('memberId'), 'content' => ['like', '%社团签到%'], 'create_time' => array(array('gt', strtotime(date('Y-m-d'))), array('lt', strtotime(date('Y-m-d', strtotime('+1 day')))))));

        $clubAlbumModel = new ClubAlbumModel();
        $clubAlbumList = $clubAlbumModel->getListByWhere(array('club_id' => $clubId), '', '', '3');
        $this->assign([
            'club' => $club,
            'status' => $status,
            'member' => $member,
            'memberList' => $memberList,
            'counts' => $counts,
            'activityList' => $activityList,
            'clubFollowInfo' => $clubFollowInfo,
            'followCounts' => $followCounts,
            'clubAlbumList' => $clubAlbumList,
            'Counts' => $Counts
        ]);

        return $this->fetch('/club-homepage-join');
    }

    public function clubJoined()
    {
        $clubJoinModel = new ClubJoinModel();
        $clubList = $clubJoinModel->getJoinClub(array('member_id' => session('memberId'), 'verify_status' => 2));
        $this->assign([
            'clubList' => $clubList
        ]);
        return $this->fetch('/club-joined');
    }

    /**
     * @return mixed
     */
    public function create() //创建社团
    {
        if (empty(session('memberId'))) {
            session('url', 'club/create');
            $this->redirect('index/index');
        } else {
            $memberModel = new MemberModel();
            $member = $memberModel->getInfoById(session('memberId'));
            if (empty($member['member_school']) || empty($member['member_tel'])) {
                $this->redirect('member/edit');
            }
        }
        if (request()->isPost()) {
            $clubModel = new ClubModel();
            $club = $clubModel->getListByWhere(array('club_owner_id' => session('memberId')));
            if (!empty($club)) {
                $return['code'] = -1;
                $return['msg'] = '您已创建过社团，可别分身乏术哦！';
                $this->assign([
                    'return' => $return
                ]);
            } else {
                $clubApplyModel = new ClubApplyModel();
                $clubApplying = $clubApplyModel->getListByWhere(array('club_owner_id' => session('memberId'), 'verify_status' => 1));
                if (!empty($clubApplying)) {
                    $return['code'] = -1;
                    $return['msg'] = '您有社团申请正在处理！';
                    $this->assign([
                        'return' => $return
                    ]);
                } else {
                    $memberModel = new MemberModel();
                    $member = $memberModel->getInfoById(session('memberId'));
                    $params = input('param.');
                    $clubTypeModel = new ClubTypeModel();
                    $clubTypeInfo = $clubTypeModel->getInfoByWhere(array('type_name' => $params['club_type'], 'type_status' => 1));
                    if (empty($clubTypeInfo)) {
                        $return['code'] = -2;
                        $return['msg'] = '您的申请的社团类型已被禁用！';
                        $this->assign([
                            'return' => $return
                        ]);
                    }
                    if (request()->file()) {
                        $album = request()->file('album');
                        $clubAlbumModel = new ClubAlbumModel();
                        foreach ($album as $key => $val) {
                            $album_img = $clubAlbumModel->insertAlbum($val);
                            $clubAlbumModel->insertGetId(array('club_id' => 0, 'club_name' => $params['club_name'], 'album_img' => $album_img[0] . '?imageMogr2/size-limit/300k', 'image_name' => $album_img[1], 'create_time' => time()));
                        }
                        $logo = request()->file('logo');
                        if ($logo) {
                            $logo = $clubAlbumModel->insertAlbum($logo);
                            $params['club_icon'] = $logo[0];
                        }
                    }
                    $params['club_type'] = $clubTypeInfo['id'];
                    $params['club_school'] = $member['member_school'];
                    $params['club_owner_id'] = session('memberId');
                    $params['apply_time'] = time();
                    $flag = $clubApplyModel->insert($params, 'ClubValidate');
                    if ($flag['code'] == 1) {
                        $messageModel = new MessageModel();
                        $message_content = '您申请的' . $params['club_name'] . '已经成功提交，请等待审核，审核时间为1小时内！';
                        $messageModel->insertMessage(0, session('memberId'), $message_content, 3);
                        $return['code'] = 1;
                        $return['msg'] = '您的社团申请已提交！';
                        $this->assign([
                            'return' => $return
                        ]);
                    } else {
                        $return['code'] = -1;
                        $return['msg'] = $flag['msg'];
                        $this->assign([
                            'return' => $return
                        ]);
                    }
                }
            }
        }
        $clubType = new ClubTypeModel();
        $where = [];
        $where['type_status'] = 1;
        $clubTypeList = $clubType->getListByWhere($where);
        if (empty($return)) {
            $return['code'] = 0;
            $return['msg'] = '';
        }
        $this->assign([
            'clubTypeList' => $clubTypeList,
            'return' => $return
        ]);
        return $this->fetch('/club-create');
    }

    public function join() //加入社团
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if (empty($member['member_tel'])) {
            $return['flag'] = -1;
        } else {
            $clubId = input('param.id');
            $clubPassword = input('param.club_password');
            $clubModel = new ClubModel();
            $clubInfo = $clubModel->getInfoById($clubId);
            if ($clubPassword != $clubInfo['club_password']) {
                return json(['flag' => -2]);
            }
            $clubJoinModel = new ClubJoinModel();
            $clubJoinInfo = $clubJoinModel->getInfoByWhere(array('member_id' => session('memberId')));
            if (empty($clubJoinInfo)) {
                $clubRuleModel = new ClubRuleModel();
                $clubExperienceModel = new ClubExperienceModel();
                $where = [];
                $where['rule_name'] = ['like', '%加入社团%'];
                $where['rule_status'] = 1;
                $clubRuleInfo = $clubRuleModel->getInfoByWhere($where);
                if (!empty($clubRuleInfo)) {
                    $Counts = $clubExperienceModel->getCounts(array('member_id' => session('memberId'), 'content' => ['like', '%加入社团%']));
                    if ($Counts < 1) {
                        $params = [];
                        $params['club_id'] = $clubId;
                        $params['member_id'] = session('memberId');
                        $params['apply_time'] = time();
                        $return['flag'] = db('club_join')->insertGetId($params);
                        $arr = [];
                        $arr['member_id'] = session('memberId');
                        $arr['club_id'] = $clubId;
                        $arr['content'] = '加入社团+' . $clubRuleInfo['rule_experience'] . '经验值';
                        $arr['create_time'] = time();
                        $clubExperienceModel->insert($arr);
                        $clubModel->updateByWhere(array('club_experience' => $clubInfo['club_experience'] + $clubRuleInfo['rule_experience']), '', array('id' => $clubId));
                    }
                }
                $messageModel = new MessageModel();
                $messageModel->insertMessage(session('memberId'), $clubInfo['club_owner_id'], $member['member_name'] . '成功加入你的社团', 4);
            } else {
                $return['flag'] = 0;
            }
        }

        return json($return);
    }

    public function cancel() //退出社团
    {
        $clubId = input('param.id');
        $clubJoinModel = new ClubJoinModel();
        $clubJoinList = $clubJoinModel->getListByWhere(array('club_id' => $clubId, 'member_id' => session('memberId')));
        $params = [];
        $params['id'] = $clubJoinList[0]['id'];
        $params['verify_status'] = 0;
        $flag = $clubJoinModel->edit($params, '');

        return json($flag);
    }

    public function follow() //关注社团
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if (empty($member['member_tel'])) {
            $return['flag'] = -1;
        } else {
            $clubId = input('param.id');
            $is_follow = input('param.is_follow');
            $clubModel = new ClubModel();
            $clubInfo = $clubModel->getInfoById($clubId);
            $clubFollowModel = new ClubFollowModel();
            $clubFollowList = $clubFollowModel->getListByWhere(array('club_id' => $clubId, 'member_id' => session('memberId')));
            if (!empty($clubFollowList)) {
                if ($clubInfo && $clubInfo['club_status'] = 1) {
                    $return['flag'] = $clubFollowModel->updateFollow(session('memberId'), $clubId, $is_follow);
                }
            } else {
                if ($clubInfo && $clubInfo['club_status'] = 1) {
                    $return['flag'] = $clubFollowModel->insertFollow(session('memberId'), $clubId, $is_follow);
                }
            }

        }
        return json($return);
    }

    public function past() //社团签到
    {
        $clubId = input('param.id');
        $clubModel = new ClubModel();
        $clubInfo = $clubModel->getInfoById($clubId);
        if (!empty($clubInfo)) {
            $clubRuleModel = new ClubRuleModel();
            $clubExperienceModel = new ClubExperienceModel();
            $where = [];
            $where['rule_name'] = ['like', '%社团签到%'];
            $where['rule_status'] = 1;
            $clubRuleInfo = $clubRuleModel->getInfoByWhere($where);
            if (!empty($clubRuleInfo)) {
                $Counts = $clubExperienceModel->getCounts(array('member_id' => session('memberId'), 'content' => ['like', '%社团签到%'], 'create_time' => array(array('gt', strtotime(date('Y-m-d'))), array('lt', strtotime(date('Y-m-d', strtotime('+1 day')))))));
                if ($Counts < $clubRuleInfo['rule_num']) {
                    $arr = [];
                    $arr['member_id'] = session('memberId');
                    $arr['club_id'] = $clubInfo['id'];
                    $arr['content'] = '社团签到+' . $clubRuleInfo['rule_experience'] . '经验值';
                    $arr['create_time'] = time();
                    $clubExperienceModel->insert($arr);
                    $club_experience = intval($clubInfo['club_experience']) + intval($clubRuleInfo['rule_experience']);
                    $return = $clubModel->updateByWhere(array('club_experience' => $club_experience), '', array('id' => $clubInfo['id']));
                }
            }

        }

        return json($return);
    }

    public function notice() //社团公告
    {
        if (request()->isPost()) {
            $clubNotice = input('param.notice');
            $clubModel = new ClubModel();
            $club = $clubModel->getInfoByWhere(array('club_owner_id' => session('memberId'), 'club_status' => '1'));
            if (empty($club)) {
                return json(['code' => -1, 'msg' => '社团不存在或已被禁用']);
            } else {
                $params = [];
                $params['club_notice'] = $clubNotice;
                $flag = $clubModel->updateByWhere($params, '', array('id' => $club['id']));

                if ($flag['code'] == 1) {
                    $return['code'] = -2;
                    $return['msg'] = '社团公告修改成功！';
                    $comment_content = "修改了社团公告";
                    $messageModel = new MessageModel();
                    $clubJoinModel = new ClubJoinModel();
                    $clubJoinList = $clubJoinModel->getListByWhere(array('club_id' => $club['id'], 'verify_status' => 1));
                    foreach ($clubJoinList as $key => $vo) {
                        $messageModel->insertMessage(session('memberId'), $vo['member_id'], $params['club_notice'], '4', $comment_content);
                    }
                    $this->redirect('club/clubHome', array('clubId' => $club['id']), 3, '页面跳转中~');
                } else {
                    $return['msg'] = $flag['msg'];
                }
                return json($return);
            }
        } else {
            $clubModel = new ClubModel();
            $memberId = session('memberId');
            $clubInfo = $clubModel->getInfoByWhere(array('club_owner_id' => $memberId));
            $this->assign([
                'ClubInfo' => $clubInfo
            ]);
        }

        return $this->fetch('/edit-notice');
    }

    public function member_list() //社团会员列表
    {
        $clubId = input('param.club_id');
        $clubJoinModel = new ClubJoinModel();
        $clubJoinList = $clubJoinModel->getListByWhere(array('club_id' => $clubId, 'verify_status' => '1'));
        if (!empty($clubJoinList)) {
            foreach ($clubJoinList as $key => $vo) {
                $memberModel = new MemberModel();
                $member_info = $memberModel->getInfoById($clubJoinList[$key]['member_id']);
                $clubJoinList[$key]['member_id'] = $member_info['id'];
                $clubJoinList[$key]['member_name'] = $member_info['member_name'];
                $clubJoinList[$key]['member_icon'] = $member_info['member_icon'];
                $clubJoinList[$key]['member_intro'] = $member_info['member_intro'];
                $clubJoinList[$key]['member_school'] = $member_info['member_school'];
            }
        }
        $this->assign([
            'clubJoinList' => $clubJoinList
        ]);
        return $this->fetch('/member-list');
    }

    public function del_member() //社团会员删除
    {
        $memberId = input('param.member_id');
        $id = session('memberId');
        if ($memberId == $id) {
            $return['flag'] = -1;
            return json($return);
        }
        $messageModel = new MessageModel();
        $clubJoinModel = new ClubJoinModel();
        $clubModel = new ClubModel();
        $clubInfo = $clubModel->getInfoByWhere(array('club_owner_id' => $id));
        $clubJoinInfo = $clubJoinModel->getInfoByWhere(array('member_id' => $memberId, 'club_id' => $clubInfo['id']));
        if (!empty($clubJoinInfo)) {
            $toMemberId = $memberId;
            $clubInfo = $clubModel->getInfoById($clubJoinInfo['club_id']);
            $message_content = "您已被" . $clubInfo['club_name'] . "社团团长移除社团！";
            $messageModel->insertMessage(session('memberId'), $toMemberId, $message_content);
            $return['flag'] = $clubJoinModel->del($clubJoinInfo['id']);
            return json($return);
        }
    }

    public function clubList() //社团列表
    {
        $memberId = session('memberId');
        $clubName = input('param.club_name');
        $ClubWhere = [];
        if (isset($clubName) && !empty($clubName)) {
            $ClubWhere['club_name'] = ['like', '%' . $clubName . '%'];
        }
        $ClubWhere['club_status'] = 1;
        $clubModel = new ClubModel();
        $clubList = $clubModel->getListByWhere($ClubWhere);
        $clubFollowModel = new ClubFollowModel();
        $clubFollowList = $clubFollowModel->getListByWhere(array('member_id' => $memberId));
        foreach ($clubList as $key => $vo) {
            foreach ($clubFollowList as $keyword => $volist) {
                if ($vo['id'] == $volist['club_id']) {
                    $clubList[$key]['is_follow'] = $volist['is_follow'];
                    $clubList[$key]['member_id'] = $volist['member_id'];
                }

            }
        }
        $this->assign([
            'clubList' => $clubList,
            'clubFollowList' => $clubFollowList,
            'memberId' => $memberId
        ]);
        return $this->fetch('/club-list');
    }

}

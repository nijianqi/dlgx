<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\MemberModel;
use app\index\model\ActivityModel;
use app\index\model\ActCommentModel;
use app\index\model\ActJoinModel;
use app\index\model\ActivityApplyModel;
use app\index\model\ActivityAlbumModel;
use app\index\model\ActComAlbumModel;
use app\index\model\MessageModel;

class Activity extends Controller
{
    protected $beforeActionList = [
        'checkMember' => ['only' => 'index,actMember,actList,join,comment,cancel,launchActivity']
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

    public function index() //活动详情
    {
        $actId = input('param.act_id');
        $actJoinModel = new ActJoinModel();
        $memberCounts = $actJoinModel->getCounts(array('act_id' => $actId));
        $actJoinList = $actJoinModel->getJoinMember(array('act_id' => $actId), 0, 10);
        $this->assign([
            'memberCounts' => $memberCounts,
            'actJoinList' => $actJoinList
        ]);

        $activityModel = new ActivityModel();
        $activityInfo = $activityModel->getInfoById($actId);
		if($activityInfo){
        $stime = $activityInfo['act_start_time'];
        $etime = $activityInfo['act_end_time'];
        if(time() < $stime) {
            $activityInfo['act_status'] = 1;
        } elseif(time() > $etime) {
            $activityInfo['act_status'] = 3;
        } else {
            $activityInfo['act_status'] = 2;
        }
        if($activityInfo['act_money'] == 0){
            $activityInfo['act_money'] = '免费';
        }else{
            $activityInfo['act_money'] = $activityInfo['act_money'].'元';
        }
        $activityInfo['act_start_time'] = date('Y.m.d', $stime);
        $activityInfo['act_end_time'] = date('Y.m.d', $etime);
        $days = floor( ( time() - $activityInfo['act_release_time'] ) / 86400 );
        if(0 == $days) {
            $activityInfo['act_days'] = '今天';
        } else {
            $activityInfo['act_days'] = $days.'天前';
        }
        if($activityInfo['act_from_id'] == 0){
            $activityInfo['act_from_name'] = '官方';
            $activityInfo['act_school'] = '';
        }else{
            $memberModel = new MemberModel();
            $member_info = $memberModel->getInfoById($activityInfo['act_from_id']);
            if($member_info){
                $activityInfo['act_from_name'] = $member_info['member_name'];
                $activityInfo['act_school'] = $member_info['member_school'];
            }
        }
        $this->assign([
            'activityInfo' => $activityInfo
        ]);

        $activityAlbumModel = new ActivityAlbumModel();
        $actAlbum_list = $activityAlbumModel->getListByWhere(array('act_id' => $actId));
        $this->assign([
            'actAlbum_list' => $actAlbum_list
        ]);

        $actCommentModel = new ActCommentModel();
        $actComment_list = $actCommentModel->getListByWhere(array('act_id' => $actId));
        foreach( $actComment_list as $key=>$val){
            $memberModel = new MemberModel();
            $member_info = $memberModel->getInfoById($val['member_id']);
            $to_member_info = $memberModel->getInfoById($val['to_member_id']);
            $actComment_list[$key]['member_name'] = $member_info['member_name'];
            $actComment_list[$key]['member_id'] = $member_info['id'];
            $actComment_list[$key]['to_member_name'] = $to_member_info['member_name'];
            $actComment_list[$key]['to_member_id'] = $to_member_info['id'];
            $actComment_list[$key]['member_icon'] = $member_info['member_icon'];
            $time = $val['comment_create_time'] - time();
            if ($time) {
                $hour =  floor((time() - $val['comment_create_time'])/ 86400 * 24);
                if($hour < 1 ){
                    $min =  floor((time() - $val['comment_create_time'])/ 86400 * 24 * 60);
                    $actComment_list[$key]['comment_create_min'] = $min;
                }elseif($hour == 24 || $hour > 24){
                    $day =  floor((time() - $val['comment_create_time'])/ 86400);
                    $actComment_list[$key]['comment_create_day'] = $day;
                }elseif($hour < 24 && $hour > 1 || $hour = 1){
                    $actComment_list[$key]['comment_create_hour'] = $hour;
                }
            }
            $actComAlbumModel = new ActComAlbumModel();
            $actComAlbumList = $actComAlbumModel->getListByWhere(array('comment_id'=> $val['id']));
            $actComment_list[$key]['actComAlbumList'] =  $actComAlbumList;
        }
        $actCommentCounts = $actCommentModel->getCounts(array('act_id' => $actId));
        $memberId = session('memberId');
        $actJoinInfo = $actJoinModel->getInfoByWhere(array('member_id'=>$memberId ,'act_id'=>$actId));
        if(!empty($actJoinInfo)){
            $is_join = 1;
        }else{
            $is_join = 0;
        }
        $this->assign([
            'actComment_list' => $actComment_list,
            'actCommentCounts'=>$actCommentCounts,
            'memberId' => $memberId,
            'is_join'=>$is_join
        ]);
        if($activityInfo['act_type'] == 1){ //线上
            return $this->fetch('/onact-detail');
        }else{ //线下
            return $this->fetch('/offact-detail');
        }
		}else{
			$this->redirect('activity/actList');
		}
    }

    public function actMember() //活动成员
    {
        $actId = input('param.act_id');
        $actJoinModel = new ActJoinModel();
        $actJoinList = $actJoinModel->getJoinMember(array('act_id' => $actId));
        $this->assign([
            'actJoinList' => $actJoinList
        ]);

        return $this->fetch('/act-member');
    }

    public function actList() //活动列表
    {
        $actType = input('param.act_type'); //活动类型
        $actName = input('param.act_name');
        $club_owner_id = input('param.club_owner_id');
        $activityWhere = [];
        if(!empty($actType)){
            $activityWhere['act_type'] = $actType;
        }
        if (isset($actName) && !empty($actName)) {
            $activityWhere['act_name'] = ['like', '%' . $actName . '%'];
        }
        if (isset($club_owner_id) && !empty($club_owner_id)) {
            $activityWhere['act_from_id'] = $club_owner_id ;
        }
        $activityWhere['act_release_time'] = ['>', 0];
        $activityField = 'id,act_name,act_detail_img,act_release_time,act_start_time,act_end_time,act_from_id,act_type';
        $activityModel = new ActivityModel();
        $activityList = $activityModel->getListByWhere($activityWhere, $activityField, 0,0,'act_start_time');

        foreach($activityList as $key => $vo){
            $stime = $vo['act_start_time'];
            $etime = $vo['act_end_time'];
            if(time() < $stime) {
                $activityList[$key]['act_status'] = 1;
            } elseif(time() > $etime) {
                $activityList[$key]['act_status'] = 3;
            } else {
                $activityList[$key]['act_status'] = 2;
            }
            $activityList[$key]['act_start_time'] = date('Y.m.d', $stime);
            $activityList[$key]['act_end_time'] = date('m.d', $etime);
            $activityList[$key]['act_release_time'] = date('Y年m月d日', $vo['act_release_time']);
            $days = floor((time() - $activityList[$key]['act_release_time']) / 86400);
            if($days == 0) {
                $activityList[$key]['act_days'] = '今天';
            } else {
                $activityList[$key]['act_days'] = $days.'天前';
            }
            if($activityList[$key]['act_from_id'] == 0){
                $activityList[$key]['act_from_name'] = '官方';
                $activityList[$key]['act_from_icon'] = '';
            }else{
                $memberModel = new MemberModel();
                $member_info = $memberModel->getInfoById($activityList[$key]['act_from_id']);
                $activityList[$key]['act_from_name'] = $member_info['member_name'];
                $activityList[$key]['act_from_icon'] = $member_info['member_icon'];
            }
        }
        $this->assign([
            'activityList' => $activityList
        ]);
        return $this->fetch('/act-list');
    }

    public function join() //参加活动
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $return['flag'] = -1;
        } else {
            $actId = input('param.id');
            $actJoinModel = new ActJoinModel();
            $counts = $actJoinModel->getCounts(array('act_id' => $actId, 'member_id' => session('memberId')));
            if(empty($counts)) {
                $params = [];
                $params['act_id'] = $actId;
                $params['member_id'] = session('memberId');
                $params['join_time'] = time();
                $return = db('act_join')->insertGetId($params);
                if($return){
                    $ActivityModel = new ActivityModel();
                    $activity = $ActivityModel->getInfoById($actId);
                    $message_content = '参加了你的活动'.": ".$activity['act_name'];
                    $message_type = 4;
                    $messageModel = new MessageModel();
                    $messageModel->insertMessage(session('memberId'),$activity['act_from_id'],$message_content,$message_type);
                    $messageModel->insertMessage($activity['act_from_id'],session('memberId'),'biu~您已成功报名"'.$activity['act_name'].'"活动!请在规定时间内进行参加!',1);
                }

            } else {
                $return['flag'] = 0;
            }
        }

        return json($return);
    }

    public function cancel() //取消参加活动
    {
        $actId = input('param.id');
        $memberId = session('memberId');
        $ActivityModel = new ActivityModel();
        $activity = $ActivityModel->getInfoById($actId);
        $messageModel = new MessageModel();
        $messageModel->insertMessage($activity['act_from_id'],session('memberId'),'biu~您已成功取消报名"'.$activity['act_name'].'"活动!');
        $return['flag'] = db('act_join')->where(array('act_id' => $actId, 'member_id' => $memberId))->delete();
        return json($return);
    }

    public function comment() //评论
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
            $actId = input('param.act_id');
            $comment = input('param.comment');
            $commentId = input('param.comment_id');
            $ActivityModel = new ActivityModel();
            $activity = $ActivityModel->getInfoById($actId);
            if($activity) {
                if (empty($commentId)) {
                    $commentId = 0;
                }
                $actCommentModel = new ActCommentModel();
                $return = $actCommentModel->insertComment($actId, $comment, $commentId);
                if (request()->file()) {
                    $album = request()->file('file');
                    $actComAlbumModel = new ActComAlbumModel();
                    foreach ($album as $key => $val) {
                        $album_img = $actComAlbumModel->insertAlbum($val);
                        $actComAlbumModel->insertGetId(array('comment_id' => $return, 'album_img' => $album_img.'?imageMogr2/size-limit/300k', 'create_time' => time()));
                    }
                }
            }else{
                echo "<script>alert('活动不存在');</script>";
            }
        }

    }

    public function launchActivity() //发起活动
    {
        if (request()->isPost()) {
            $param = input('param.');
            if(request()->file()){
                $album = request()->file('album');
                $activityAlbumModel = new ActivityAlbumModel();
                foreach($album as $key=>$val){
                    $album_img = $activityAlbumModel->insertAlbum($val);
                    $activityAlbumModel->insertGetId(array('act_id'=>0,'act_name'=>$param['act_name'],'album_img'=>$album_img.'?imageMogr2/size-limit/300k','create_time'=>time()));
                    $param['act_detail_img'] = $album_img;
				}
            }
			if(empty($param['act_detail_img'])){
				$param['act_detail_img'] = '';
			}
            preg_match_all('/\d/',$param['act_start_time'],$arr);
            $sTime=implode('',$arr[0]);
            $sTime=strtotime($sTime);
            $param['act_start_time'] = $sTime;
            preg_match_all('/\d/',$param['act_end_time'],$arr);
            $eTime=implode('',$arr[0]);
            $eTime=strtotime($eTime);
            $param['act_end_time'] = $eTime;
            if($sTime == $eTime ){
                $return['code'] = -1;
                $return['msg'] = '开始时间不能等于结束时间';
                $this->assign([
                    'return' => $return
                ]);
            }
            $param['apply_time'] = time();
            $memberModel = new MemberModel();
            $member = $memberModel->getInfoById(session('memberId'));
            if($member['member_status'] == 2){
                $return['code'] = -1;
                $return['msg'] = '你的会员被禁用了';
                $this->assign([
                    'return' => $return
                ]);
            }
            $param['club_owner_id'] = session('memberId');
			$param['verify_idea'] = '';
            $activityApply = new ActivityApplyModel();
            $flag = $activityApply->insert($param);
            $return['code'] = $flag['code'];
            $return['msg'] = $flag['msg'];
            $this->assign([
                'return' => $return
            ]);
        }
        if(empty($return)){
            $return['code'] = 0;
            $return['msg'] = '';
        }
        $sTime = date('Y 年 m 月 d 日', time());
        $eTime = date('Y 年 m 月 d 日', strtotime('+1 day'));
        $this->assign([
            'act_start_time' => $sTime,
            'act_end_time' => $eTime,
            'return' => $return
        ]);
        return $this->fetch('/launch-activity');
    }
}

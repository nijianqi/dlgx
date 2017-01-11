<?php
namespace app\weapp\controller;

use think\Controller;
use app\index\model\MemberModel;
use app\index\model\BannerModel;
use app\index\model\ActivityModel;
use app\index\model\VideoModel;
use app\index\model\ClubModel;
use app\index\model\ClubFollowModel;
use app\index\model\MessageModel;

class Index extends Controller
{

    public function index() //首页活动展示
    {
        $bannerWhere = [];  //轮播图-滑动显示img
        $bannerWhere['banner_release_time'] = ['>', 0];
        $bannerModel = new BannerModel();
        $bannerList = $bannerModel->getListByWhere($bannerWhere, '*', 0, 3, 'banner_release_time');
        $return['bannerList'] = $bannerList;

        $activityOnlineWhere = [];  //线上活动
        $activityOnlineWhere['act_release_time'] = ['>', 0];
        $activityOnlineWhere['act_type'] = 1; //线上
        $activityField = 'id,act_name,act_detail_img,act_release_time,act_start_time,act_end_time,act_from_id,act_type,is_top';
        $activityModel = new ActivityModel();
        $activityOnlineList = $activityModel->getListByWhere($activityOnlineWhere, $activityField, 0, 0, 'act_end_time desc');
        $flag=array();
        $flag2=array();
        foreach ($activityOnlineList as $key => $vo) {
            $flag[]=$activityOnlineList[$key]['is_top'];
            $flag2[]=$vo['act_end_time'];
        }
        array_multisort($flag, SORT_ASC,$flag2, SORT_DESC,$activityOnlineList);
        $return['activityOnlineList'] = $activityOnlineList;

        $activityLineWhere = [];   //线下活动
        $activityLineWhere['act_release_time'] = ['>', 0];
        $activityLineWhere['act_type'] = 2; //线下
        $activityField = 'id,act_name,act_detail_img,act_release_time,act_start_time,act_end_time,act_from_id,act_type,is_top';
        $activityLineList = $activityModel->getListByWhere($activityLineWhere, $activityField, 0, 0, 'act_start_time desc');
        $top=array();
        $top2=array();
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
            $top[]=$activityLineList[$key]['is_top'];
            $top2[]=$vo['act_start_time'];
        }
        array_multisort($top, SORT_ASC ,$top2, SORT_DESC,$activityLineList);
        $return['activityLineList'] = $activityLineList;

        $videoWhere = [];
        $videoWhere['video_release_time'] = ['>', 0];
        $videoField = 'id,video_name,video_img,video_url,video_release_time';
        $videoModel = new VideoModel();
        $videoList = $videoModel->getListByWhere($videoWhere, $videoField);
        foreach ($videoList as $key => $vo) {
            $videoList[$key]['video_release_time'] = date('m月d日', $vo['video_release_time']);
        }
        $return['videoList'] = $videoList;

        return json($return);
    }

    public function showClub()  //首页社团显示
    {
        $clubWhere = [];
        $clubWhere['club_create_time'] = ['>', 0];
        $clubWhere['club_status'] = 1;
        $clubField = 'id,club_name,club_school,club_icon,club_intro,club_owner_id';
        $clubModel = new ClubModel();
        $clubList = $clubModel->getListByWhere($clubWhere, $clubField, 0);
        $return['clubList'] = $clubList;
        return json($return);
    }

}
<?php
namespace app\admin\controller;

use app\admin\model\ActivityModel;
use app\index\model\ActJoinModel;
use app\index\model\MemberModel;
use app\admin\model\ActivityApplyModel;
use app\index\model\ActivityAlbumModel;
use app\index\model\ActCommentModel;
use app\admin\model\MessageModel;
use app\admin\model\ClubModel;
use app\admin\model\ClubJoinModel;
use app\admin\model\ClubRuleModel;
use app\index\model\ClubExperienceModel;

class Activity extends Base
{
    //活动列表
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $order = $param['sortName'].' '.$param['sortOrder'];
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['act_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $activity = new ActivityModel();
            $selectResult = $activity->getListByWhere($where,'*', $offset, $limit ,$order);
            $top= config('is_top');
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['act_create_time'] = date('Y-m-d H:i:s', $vo['act_create_time']);
                $selectResult[$key]['act_start_time'] = date('Y-m-d H:i:s', $vo['act_start_time']);
                $selectResult[$key]['act_end_time'] = date('Y-m-d H:i:s', $vo['act_end_time']);
                $selectResult[$key]['act_money'] =  $vo['act_money'].'元';
                if ($vo['act_release_time'] == 0) {
                    $selectResult[$key]['act_release_time'] = '未发布';
                } else {
                    $selectResult[$key]['act_release_time'] = date('Y-m-d H:i:s', $vo['act_release_time']);
                }
                if ($vo['act_type'] == 1) {
                    $selectResult[$key]['act_type'] = '线上';
                } else {
                    $selectResult[$key]['act_type'] = '线下';
                }
                $member = new MemberModel();
                if($vo['act_from_id']!=0){
                    $info = $member->getInfoById($vo['act_from_id']);//查询发起人名字
                    $selectResult[$key]['act_from_id']=$info['member_name'];
                }else{
                    $selectResult[$key]['act_from_id']='官方';
                }
                $selectResult[$key]['is_top'] = $top[$vo['is_top']];
                $operate = [
                    '查看活动成员' => url('activity/show', ['id' => $vo['id']]),
                    '编辑' => url('activity/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $activity->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加活动
    public function add()
    {
        if (request()->isPost()) {
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['act_start_time'] = strtotime($param['act_start_time']);
            $param['act_end_time'] = strtotime($param['act_end_time']);
            $param['act_create_time'] = time();
            if ($param['activity_status'] == 1) {
                $param['act_release_time'] = time();
            } elseif ($param['activity_status'] == 2) {
                $param['act_release_time'] = 0;
            }
            unset($param['activity_status']);
            $activity = new activityModel();
            $flag = $activity->insert($param, 'ActivityValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //编辑活动
    public function edit()
    {
        $activity = new activityModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $param['act_start_time'] = strtotime($param['act_start_time']);
            $param['act_end_time'] = strtotime($param['act_end_time']);
            $param['act_create_time'] = time();
            if ($param['activity_status'] == 1) {
                $param['act_release_time'] = time();
            } elseif ($param['activity_status'] == 2) {
                $param['act_release_time'] = 0;
            }
            unset($param['activity_status']);
            $flag = $activity->edit($param, 'ActivityValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $activity->getInfoById($id);
        $info['act_start_time'] = date('Y-m-d H:i:s', $info['act_start_time']);
        $info['act_end_time'] = date('Y-m-d H:i:s', $info['act_end_time']);
        $info['act_create_time'] = date('Y-m-d H:i:s', $info['act_create_time']);
        if ($info['act_release_time'] == 0) {
            $info['activity_status'] = 2;
        } else {
            $info['activity_status'] = 1;
        }
        $this->assign([
            'activity' => $info,
            'is_top' => config('is_top')
        ]);
        return $this->fetch();
    }
    //删除活动
    public function del()
    {
        $id = input('param.id');
        $activity = new activityModel();
		$actJoin = new ActJoinModel();
		$actJoin->delByWhere(array('act_id'=>$id));
		$ActComment = new ActCommentModel();
		$ActComment->delByWhere(array('act_id'=>$id));
		$ActivityAlbum = new ActivityAlbumModel();
		$ActivityAlbum->delByWhere(array('act_id'=>$id));
		$message = new messageModel();
		$message->delByWhere(array('act_id'=>$id));
        $flag = $activity->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    //编辑活动
    public function show()
    {
        $actJoin = new ActJoinModel();
        $id = input('param.id');
        $list = $actJoin ->getJoinMember(array('act_id'=>$id));
        $this->assign([
            'list' => $list
        ]);
        return $this->fetch();
    }
    //活动审核列表
    public function apply()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $order = $param['sortName'].' '.$param['sortOrder'];
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['act_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $activityApply = new ActivityApplyModel();
            $selectResult = $activityApply->getListByWhere($where, '*', $offset, $limit ,$order);
            $status = config('apply_status');
            foreach ($selectResult as $key => $vo) {
                $member = new MemberModel();
                $info = $member->getInfoById($vo['club_owner_id']);//查询社团团长名字
                $selectResult[$key]['apply_time'] = date('Y-m-d H:i:s', $vo['apply_time']);
                $selectResult[$key]['act_start_time'] = date('Y-m-d H:i:s', $vo['act_start_time']);
                $selectResult[$key]['act_end_time'] = date('Y-m-d H:i:s', $vo['act_end_time']);
                $selectResult[$key]['club_owner_id'] = $info['member_name'];
                if ($selectResult[$key]['verify_status'] == 1) {
                    $operate = [
                        '审核' => url('activity/editapply', ['id' => $vo['id']]),
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }
                $selectResult[$key]['verify_status'] = $status[$vo['verify_status']];

            }
            $return['total'] = $activityApply->getCounts($where);  //总数据
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //编辑审核
    public function editApply()
    {
        $activityApply = new ActivityApplyModel();
        $actJoinModel = new ActJoinModel();
        $activity = new ActivityModel();
        $activityAlbum = new ActivityAlbumModel();
        $messageModel = new MessageModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $activityApply->edit($param);
            if ($param['verify_status'] == 2) {
                unset($param['verify_status']);
                unset($param['id']);
                unset($param['verify_idea']);
                unset($param['apply_time']);
				unset($param['act_sponsor']);
                $param['act_from_id'] =  $param['club_owner_id'];
                unset($param['club_owner_id']);
                $param['act_type'] = 2;
				$param['act_list_img'] = '';
                $param['act_create_time'] = time();
                $param['act_release_time'] = time();

                $activity->insert($param, 'ActivityValidate');
                $activityInfo = $activity->getInfoByWhere(array('act_name'=>$param['act_name']));
                $activityAlbumList = $activityAlbum ->getListByWhere(array('act_name'=>$param['act_name'],'act_id'=>0));
                if($activityAlbumList){
                    $activityAlbum->updateByWhere(array('act_id'=>$activityInfo['id']),'',array('act_name'=>$activityInfo['act_name']));
                }
                $params = [];
                $params['act_id'] =$activityInfo['id'];

                $params['member_id'] =$activityInfo['act_from_id'];
                $params['join_time'] = time();
                $actJoinModel->insert($params,'ActivityValidate');

                $clubModel = new ClubModel();
                $clubJoinModel = new ClubJoinModel();
                $clubRuleModel = new ClubRuleModel();
                $clubExperienceModel = new ClubExperienceModel();
                $clubInfo = $clubModel->getInfoByWhere(array('club_owner_id'=>$activityInfo['act_from_id']));
                $Counts = $clubExperienceModel->getCounts(array('member_id'=>session('memberId'),'content'=>['like', '%线下活动%'],'create_time'=>array(array('gt',strtotime(date('Y-m-d'))),array('lt',strtotime(date('Y-m-d',strtotime('+1 day')))))));
                $where = [];
                $where['rule_name'] = ['like', '%线下活动%'];
                $where['rule_status'] = 1;
                $clubRuleInfo = $clubRuleModel->getInfoByWhere($where);
                if(!empty($clubRuleInfo)){
                    if($Counts<= $clubRuleInfo['rule_num']){
                        $arr = [];
                        $arr['member_id'] = $activityInfo['act_from_id'];
                        $arr['club_id'] = $clubInfo['id'];
                        $arr['content'] = '线下活动+'.$clubRuleInfo['rule_experience'].'经验值';
                        $arr['create_time'] = time();
                        $clubExperienceModel->insert($arr);
                        $clubModel->updateByWhere(array('club_experience'=>$clubInfo['club_experience']+$clubRuleInfo['rule_experience']),'',array('club_owner_id'=>$activityInfo['act_from_id']));
                    }
                }
                $clubJoinList = $clubJoinModel->getListByWhere(array('club_id'=>$clubInfo['id'],'verify_status'=>1));
                foreach($clubJoinList as $key=>$vo){
                        $messageModel->insertMessage($activityInfo['act_from_id'],$vo['member_id'],'发布了活动'.$param['act_name'],'4');
                    }
            }else{
                $messageModel->insertMessage('0',$param['club_owner_id'],'拒绝了活动"'.$param['act_name'].'"活动的申请",拒绝理由为:'.$param['verify_idea'],'3');
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $activityApply->getInfoById($id);
        $this->assign([
            'act' => $info,
            'verify_status' => config('verify_status')
        ]);
        return $this->fetch('activity/edit_apply');
    }
}
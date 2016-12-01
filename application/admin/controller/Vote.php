<?php
namespace app\admin\controller;

use app\admin\model\VoteModel;
use app\index\model\VoteJoinModel;
use app\index\model\VoteNumModel;
use app\index\model\MemberModel;
use app\admin\model\MessageModel;
use app\admin\model\VoteApplyModel;
use app\index\model\VoteCommentModel;

class Vote extends Base
{
    //投票活动列表
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['vote_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $vote = new VoteModel();
            $selectResult = $vote->getListByWhere($where,'*', $offset, $limit);
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['vote_create_time'] = date('Y-m-d H:i:s', $vo['vote_create_time']);
                $selectResult[$key]['vote_start_time'] = date('Y-m-d H:i:s', $vo['vote_start_time']);
                $selectResult[$key]['vote_end_time'] = date('Y-m-d H:i:s', $vo['vote_end_time']);
                $selectResult[$key]['vote_url'] ='/index.php/index/vote/index/vote_id/'.$vo['id'].'.html';
                if ($vo['vote_release_time'] == 0) {
                    $selectResult[$key]['vote_release_time'] = '未发布';
                } else {
                    $selectResult[$key]['vote_release_time'] = date('Y-m-d H:i:s', $vo['vote_release_time']);
                }
                $operate = [
                    '查看活动成员' => url('vote/show', ['id' => $vo['id']]),
                    '编辑' => url('vote/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $vote->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加投票活动
    public function add()
    {
        if (request()->isPost()) {
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['vote_start_time'] = strtotime($param['vote_start_time']);
            $param['vote_end_time'] = strtotime($param['vote_end_time']);
            $param['vote_create_time'] = time();
            if ($param['vote_status'] == 1) {
                $param['vote_release_time'] = time();
            } elseif ($param['vote_status'] == 2) {
                $param['vote_release_time'] = 0;
            }
            unset($param['vote_status']);
            $vote = new VoteModel();
            $flag = $vote->insert($param, 'voteValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //编辑投票活动
    public function edit()
    {
        $vote = new VoteModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $param['vote_start_time'] = strtotime($param['vote_start_time']);
            $param['vote_end_time'] = strtotime($param['vote_end_time']);
            $param['vote_create_time'] = time();
            if ($param['vote_status'] == 1) {
                $param['vote_release_time'] = time();
            } elseif ($param['vote_status'] == 2) {
                $param['vote_release_time'] = 0;
            }
            unset($param['vote_status']);
            $flag = $vote->edit($param, 'voteValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $vote->getInfoById($id);
        $info['vote_start_time'] = date('Y-m-d H:i:s', $info['vote_start_time']);
        $info['vote_end_time'] = date('Y-m-d H:i:s', $info['vote_end_time']);
        $info['vote_create_time'] = date('Y-m-d H:i:s', $info['vote_create_time']);
        if ($info['vote_release_time'] == 0) {
            $info['vote_status'] = 2;
        } else {
            $info['vote_status'] = 1;
        }
        $this->assign([
            'vote' => $info
        ]);
        return $this->fetch();
    }
    //删除投票活动
    public function del()
    {
        $id = input('param.id');
        $vote = new VoteModel();
		$voteJoin = new VoteJoinModel();
		$voteJoin->delByWhere(array('vote_id'=>$id));
		$voteComment = new VoteCommentModel();
		$voteComment->delByWhere(array('vote_id'=>$id));
		$message = new messageModel();
		$message->delByWhere(array('vote_id'=>$id));
        $flag = $vote->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    //投票参赛人员
    public function show()
    {
        $voteJoin = new VoteJoinModel();
        $voteNumModel = new VoteNumModel();
        $id = input('param.id');
        $voteJoinList = $voteJoin ->getJoinMember(array('vote_id'=>$id));
        $flag = array();
        foreach ($voteJoinList as $key => $val) {
            $voteJoinList[$key]['album_img'] = unserialize($val['album_img']);
            $voteJoinList[$key]['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $id, 'join_id' => $val['member_id']));
            $flag[] = $voteJoinList[$key]['vote_num'];
        }
        array_multisort($flag, SORT_DESC, $voteJoinList);
        $this->assign([
            'list' => $voteJoinList
        ]);
        return $this->fetch();
    }
    //投票活动参赛审核列表
    public function apply()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['cp_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $voteApply = new VoteApplyModel();
            $selectResult = $voteApply->getListByWhere($where, $offset, $limit);
            $status = config('apply_status');
            foreach ($selectResult as $key => $vo) {
                $member = new MemberModel();
                $info = $member->getInfoById($vo['member_id']);//查询会员名字
                $selectResult[$key]['apply_time'] = date('Y-m-d H:i:s', $vo['apply_time']);
                $selectResult[$key]['member_id'] = $info['member_name'];
                if ($selectResult[$key]['verify_status'] == 1) {
                    $operate = [
                        '审核' => url('vote/editApply', ['id' => $vo['id']]),
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }
                $selectResult[$key]['verify_status'] = $status[$vo['verify_status']];

            }
            $return['total'] = $voteApply->getCounts($where);  //总数据
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //编辑投票审核
    public function editApply()
    {
        $voteApply = new VoteApplyModel();
        $voteJoinModel = new voteJoinModel();
        $messageModel = new MessageModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $voteApply->edit($param);
            if ($param['verify_status'] == 2) {
                unset($param['verify_status']);
                unset($param['id']);
                unset($param['verify_idea']);
                unset($param['apply_time']);
                $param['join_time']= time();
                $voteJoinCounts= $voteJoinModel->getCounts(array('vote_id' => $param['vote_id']));
                if(!empty($voteJoinCounts)){
                        $param['cp_id']= $voteJoinCounts+1;
                }else{
                    $param['cp_id']= 1;
                }
                $voteJoinModel->insert($param, '');
                $messageModel->insertMessage('0',$param['member_id'],'投票活动的报名申请成功了','3');
            }else{
                $messageModel->insertMessage('0',$param['member_id'],'投票活动的报名申请被拒绝了,拒绝理由为:'.$param['verify_idea'],'3');
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $voteApply->getInfoById($id);
        $this->assign([
            'vote' => $info,
            'verify_status' => config('verify_status')
        ]);
        return $this->fetch('vote/edit_apply');
    }
}
<?php
namespace app\admin\controller;

use app\admin\model\ClubModel;
use app\admin\model\ClubTypeModel;
use app\admin\model\ClubRuleModel;
use app\index\model\ClubAlbumModel;
use app\index\model\ClubFollowModel;
use app\index\model\ClubJoinModel;
use app\index\model\MemberModel;
use app\index\model\ClubApplyModel;
use app\admin\model\MessageModel; 

class Club extends Base
{
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['club_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $club = new ClubModel();
            $selectResult = $club->getListByWhere($where, '*',$offset, $limit);
            $status = config('club_status');
            foreach ($selectResult as $key => $vo) {
                $member = new MemberModel();
                $info = $member->getInfoById($vo['club_owner_id']);//查询社团团长名字
                $clubType = new ClubTypeModel();
                $clubTypeInfo= $clubType->getInfoById($vo['club_type']);//查询社团类型名称
                $selectResult[$key]['club_type'] = $clubTypeInfo['type_name'];
                $selectResult[$key]['club_owner_id'] = $info['member_name'];
                $selectResult[$key]['club_create_time'] = date('Y年m月d日', $vo['club_create_time']);
                $selectResult[$key]['club_status'] = $status[$vo['club_status']];
                $operate = [
                    '编辑' => url('club/edit', ['id' => $vo['id']]),
					'删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $club->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //编辑社团
    public function edit()
    {
        $club = new ClubModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $club->edit($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $clubType= new ClubTypeModel();
        $clubTypeList = $clubType->getListByWhere();
        $this->assign([
            'club' => $club->getInfoById($id),
            'clubTypeList' =>$clubTypeList,
            'club_status' => config('club_status')
        ]);
        return $this->fetch();
    }
    //删除社团
    public function del()
    {
        $id = input('param.id');
        $club = new ClubModel();
		$clubFollow= new ClubFollowModel();
        $clubFollow->delByWhere(array('club_id'=>$id));
		$clubJoin= new ClubJoinModel();
        $clubJoin->delByWhere(array('club_id'=>$id));
		$ClubAlbum= new ClubAlbumModel();
        $ClubAlbum->delByWhere(array('club_id'=>$id));
        $flag = $club->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    //社团审核列表
    public function apply()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['club_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $clubApply = new ClubApplyModel();
            $selectResult = $clubApply->getListByWhere($where, $offset, $limit);
            $status = config('apply_status');
            foreach ($selectResult as $key => $vo) {
                $member = new MemberModel();
                $info = $member->getInfoById($vo['club_owner_id']);//查询社团团长名字
                $selectResult[$key]['club_owner_id'] = $info['member_name'];
                $clubType = new ClubTypeModel();
                $clubTypeInfo= $clubType->getInfoById($vo['club_type']);//查询社团类型名称
                $selectResult[$key]['club_type'] = $clubTypeInfo['type_name'];
                $selectResult[$key]['apply_time'] = date('Y-m-d H:i:s', $vo['apply_time']);;
                if ($selectResult[$key]['verify_status'] == 1) {
                    $operate = [
                        '审核' => url('club/editApply', ['id' => $vo['id']]),
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }
                $selectResult[$key]['verify_status'] = $status[$vo['verify_status']];

            }
            $return['total'] = $clubApply->getCounts($where);  //总数据
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //编辑社团审核
    public function editApply()
    {
        $clubApply = new ClubApplyModel();
        $clubAlbum = new ClubAlbumModel();
        $clubFollow = new ClubFollowModel();
        $clubJoin = new ClubJoinModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $clubApply->edit($param);
            if ($param['verify_status'] == 2) {
                $club = new ClubModel();
                unset($param['verify_status']);
                unset($param['id']);
                unset($param['verify_idea']);
                $param['club_status'] = 1;
				$param['club_notice'] = '';
                $param['club_create_time'] = time();
                $club->insert($param);
                $club_info = $club->getInfoByWhere(array('club_name'=>$param['club_name']));
                $clubAlbumList = $clubAlbum ->getListByWhere(array('club_name'=>$param['club_name'],'club_id'=>0));
                if($clubAlbumList){
                    $clubAlbum->updateByWhere(array('club_id'=>$club_info['id']),'',array('club_name'=>$club_info['club_name']));
                }
                $clubJoin->insert(array('club_id'=>$club_info['id'],'member_id'=>$club_info['club_owner_id'],'apply_time'=>time())); //会员默认加入，关注社团
                $clubFollow->insert(array('club_id'=>$club_info['id'],'member_id'=>$club_info['club_owner_id'],'is_follow'=>2,'apply_time'=>time()));
                $messageModel = new MessageModel();
                $messageModel->insertMessage(0,$club_info['club_owner_id'],'您申请的'.$club_info['club_name'].'社团已通过审核，快点召集小伙伴加入吧!',3);
			}else{
                $messageModel = new MessageModel();
                $messageModel->insertMessage(0,$param['club_owner_id'],'很抱歉，您申请的'.$param['club_name'].'社团已被拒绝,拒绝原因为:'.$param['verify_idea'].'!',3);
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $clubApply = new ClubApplyModel();
        $clubApplyInfo = $clubApply->getInfoById($id);
        $this->assign([
            'club' => $clubApplyInfo,
            'verify_status' => config('verify_status')
        ]);
        return $this->fetch('club/edit_apply');
    }
    //社团类型
    public function type()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['type_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $clubType = new ClubTypeModel();
            $selectResult = $clubType->getListByWhere($where, '*', $offset, $limit);
            $status = config('type_status');
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['type_status'] = $status[$vo['type_status']];
                $operate = [
                    '编辑' => url('club/editType', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $clubType->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch('club/type');
    }
    //添加社团类型
    public function addType()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $clubType = new ClubTypeModel();
            $flag = $clubType->insert($param,'ClubTypeValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $this->assign([
            'status' => config('type_status')
        ]);
        return $this->fetch('club/add_type');
    }
    //编辑社团类型
    public function editType()
    {
        $clubType = new ClubTypeModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $clubType->edit($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'clubType' => $clubType->getInfoById($id),
            'type_status' => config('type_status')
        ]);
        return $this->fetch('club/edit_type');
    }
    //删除社团类型
    public function delType()
    {
        $id = input('param.id');
        $clubType = new ClubTypeModel();
        $flag = $clubType->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    //社团经验规则
    public function rule()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['rule_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $clubRule = new ClubRuleModel();
            $selectResult = $clubRule->getListByWhere($where,'*',$offset, $limit);
            $status = config('type_status');
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['rule_status'] = $status[$vo['rule_status']];
                $operate = [
                    '编辑' => url('club/editRule', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $clubRule->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch('club/rule');
    }
    //添加社团经验规则
    public function addRule()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $clubRule = new ClubRuleModel();
            $flag = $clubRule->insert($param,'ClubRuleValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $this->assign([
            'status' => config('type_status')
        ]);
        return $this->fetch('club/add_rule');
    }
    //编辑社团经验规则
    public function editRule()
    {
        $clubRule = new ClubRuleModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $clubRule->edit($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'clubRule' => $clubRule->getInfoById($id),
            'rule_status' => config('type_status')
        ]);
        return $this->fetch('club/edit_rule');
    }
    //删除社团经验规则
    public function delRule()
    {
        $id = input('param.id');
        $clubRule = new ClubRuleModel();
        $flag = $clubRule->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
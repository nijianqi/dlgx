<?php
namespace app\admin\controller;

use app\admin\model\MemberModel;

class Member extends Base
{
    //会员列表
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['image_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $member = new MemberModel();
            $selectResult = $member->getListByWhere($where,'*', $offset, $limit);
            $status = config('member_status');
            $sex = config('member_sex');
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['member_create_time'] = date('Y-m-d H:i:s', $vo['member_create_time']);
                $selectResult[$key]['member_sex'] = $sex[$vo['member_sex']];
                $selectResult[$key]['member_status'] = $status[$vo['member_status']];
                $operate = [
                    '编辑' => url('member/edit', ['id' => $vo['id']])
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $member->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }

    //编辑会员
    public function edit()
    {
        $member = new MemberModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $member->edit($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'member' => $member->getInfoById($id),
            'member_status' => config('club_status')
        ]);
        return $this->fetch();
    }

}
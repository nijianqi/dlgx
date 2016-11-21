<?php
namespace app\admin\controller;

use app\admin\model\SchoolModel;
use app\admin\model\AreaModel;

class School extends Base
{
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['school_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $school = new SchoolModel();
            $selectResult = $school->getSchoolByWhere($where,'*', $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $operate = [
                    '编辑' => url('school/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $school->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加学校
    public function add()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $school = new SchoolModel();
            $flag = $school->insert($param, 'schoolValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $area = new AreaModel();
        $areaList = $area->getListByWhere();
        $this->assign([
            'areaList' => $areaList,
        ]);
        return $this->fetch();
    }
    //编辑学校
    public function edit()
    {
        $school = new SchoolModel();
        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $school->edit($param, 'schoolValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $school->getInfoById($id);
        $area = new AreaModel();
        $areaList = $area->getListByWhere();
        $this->assign([
            'areaList' => $areaList,
            'school' => $info
        ]);
        return $this->fetch();
    }
    //删除学校
    public function del()
    {
        $id = input('param.id');
        $school = new SchoolModel();
        $flag = $school->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
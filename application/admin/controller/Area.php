<?php
namespace app\admin\controller;

use app\admin\model\AreaModel;

class Area extends Base
{
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['area_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $area = new AreaModel();
            $selectResult = $area->getListByWhere($where,'*', $offset, $limit,'id asc');
            foreach($selectResult as $key=>$vo){
                $operate = [
                    '编辑' => url('area/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $area->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加地区
    public function add()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $area = new AreaModel();
            $flag = $area->insert($param, 'AreaValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //编辑地区
    public function edit()
    {
        $area = new AreaModel();
        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $area->edit($param, 'AreaValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $area->getInfoById($id);
        $this->assign([
            'area' => $info
        ]);
        return $this->fetch();
    }
    //删除地区
    public function del()
    {
        $id = input('param.id');
        $area = new AreaModel();
        $flag = $area->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
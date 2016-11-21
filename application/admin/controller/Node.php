<?php
namespace app\admin\controller;

use app\admin\model\NodeModel;

class Node extends Base
{
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['node_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $node = new NodeModel();
            $selectResult = $node->getListByWhere($where,'*', $offset, $limit);
            foreach($selectResult as $key=>$vo){
                if(1 == $vo['id']){
                    $selectResult[$key]['operate'] = '';
                    continue;
                }
                $operate = [
                    '编辑' => url('node/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('".$vo['id']."')",
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $node->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }

    public function add()
    {
        $node = new NodeModel();
		if(request()->isAjax()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $flag = $node->insert($param,'NodeValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $this->assign([
            'node' => $node->getListByWhere(array('father_node_id' => 0)),
            'status' => config('user_status')
        ]);
        return $this->fetch();
    }

    public function edit()
    {
        $node = new NodeModel();
        if(request()->isAjax()){
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $node->edit($param,'NodeValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'nodes' => $node->getListByWhere(array('father_node_id' => 0)),
            'node' => $node->getInfoById($id)
        ]);
        return $this->fetch();
    }

    public function del($id)
    {
        $node = new NodeModel();
        $flag = $node->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}

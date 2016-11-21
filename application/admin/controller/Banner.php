<?php
namespace app\admin\controller;

use app\admin\model\BannerModel;

class Banner extends Base
{
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['banner_title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $banner = new BannerModel();
            $selectResult = $banner->getListByWhere($where,'*', $offset, $limit);
            foreach($selectResult as $key=>$vo){
                if($vo['banner_release_time'] == 0) {
                    $selectResult[$key]['banner_release_time'] = '未发布';
                } else {
                    $selectResult[$key]['banner_release_time'] = date('Y-m-d H:i:s', $vo['banner_release_time']);
                }
                $operate = [
                    '编辑' => url('banner/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $banner->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加轮播图
    public function add()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['banner_create_time'] = time();
            if($param['banner_status'] == 1) {
                $param['banner_release_time'] = time();
            } elseif($param['banner_status'] == 2) {
                $param['banner_release_time'] = 0;
            }
            unset($param['banner_status']);
            $banner = new BannerModel();
            $flag = $banner->insert($param, 'BannerValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //编辑轮播图
    public function edit()
    {
        $banner = new BannerModel();
        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);
            if($param['banner_status'] == 1) {
                $param['banner_release_time'] = time();
            } elseif($param['banner_status'] == 2) {
                $param['banner_release_time'] = 0;
            }
            unset($param['banner_status']);
            $flag = $banner->edit($param, 'BannerValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $banner->getInfoById($id);
        if($info['banner_release_time'] == 0) {
            $info['banner_status'] = 2;
        } else {
            $info['banner_status'] = 1;
        }
        $this->assign([
            'banner' => $info
        ]);
        return $this->fetch();
    }
    //删除轮播图
    public function del()
    {
        $id = input('param.id');
        $banner = new BannerModel();
        $flag = $banner->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
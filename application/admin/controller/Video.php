<?php
namespace app\admin\controller;

use app\admin\model\VideoModel;

class Video extends Base
{
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['video_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $video = new VideoModel();
            $selectResult = $video->getListByWhere($where,'*', $offset, $limit);
            foreach($selectResult as $key=>$vo){
                if($vo['video_release_time'] == 0) {
                    $selectResult[$key]['video_release_time'] = '未发布';
                } else {
                    $selectResult[$key]['video_release_time'] = date('Y-m-d H:i:s', $vo['video_release_time']);
                }
                $operate = [
                    '编辑' => url('video/edit', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $video->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加视频
    public function add()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['video_create_time'] = time();
            if($param['video_status'] == 1) {
                $param['video_release_time'] = time();
            } elseif($param['video_status'] == 2) {
                $param['video_release_time'] = 0;
            }
            unset($param['video_status']);
            $video = new VideoModel();
            $flag = $video->insert($param, 'VideoValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //编辑视频
    public function edit()
    {
        $video = new VideoModel();
        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);
            if($param['video_status'] == 1) {
                $param['video_release_time'] = time();
            } elseif($param['video_status'] == 2) {
                $param['video_release_time'] = 0;
            }
            unset($param['video_status']);
            $flag = $video->edit($param, 'VideoValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $video->getInfoById($id);
        if($info['video_release_time'] == 0) {
            $info['video_status'] = 2;
        } else {
            $info['video_status'] = 1;
        }
        $this->assign([
            'video' => $info
        ]);
        return $this->fetch();
    }
    //删除视频
    public function del()
    {
        $id = input('param.id');
        $video = new VideoModel();
        $flag = $video->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
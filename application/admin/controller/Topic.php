<?php
namespace app\admin\controller;

use app\admin\model\TopicModel;
use app\index\model\TopicCollectModel;
use app\index\model\TopicLikeModel;
use app\index\model\TopicCommentModel;
use app\index\model\TopicTypeModel;
use app\index\model\TopicAlbumModel;
use app\index\model\MessageModel;
use app\index\model\MemberModel;

class Topic extends Base
{
    //话题列表
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $order = $param['sortName'].' '.$param['sortOrder'];
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['topic_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $topic = new TopicModel();
            $selectResult = $topic->getListByWhere($where,'*', $offset, $limit ,$order);
            $status = config('topic_status');
            $top= config('is_top');
            foreach ($selectResult as $key => $vo) {
                $member = new MemberModel();
                if($vo['topic_owner_id']!=0){
                    $info = $member->getInfoById($vo['topic_owner_id']);//查询发起人名字
                }else{
                    $info['member_name']='官方';
                }
                $selectResult[$key]['topic_owner_id'] = $info['member_name'];
                $selectResult[$key]['topic_status'] = $status[$vo['topic_status']];
                $selectResult[$key]['topic_create_time'] = date('Y-m-d H:i:s', $vo['topic_create_time']);
                if ($vo['topic_release_time'] == 0) {
                    $selectResult[$key]['topic_release_time'] = '未发布';
                } else {
                    $selectResult[$key]['topic_release_time'] = date('Y-m-d H:i:s', $vo['topic_release_time']);
                }
                $selectResult[$key]['is_top'] = $top[$vo['is_top']];
                $operate = [
                    '编辑' => url('topic/edit', ['id' => $vo['id']]),
					'删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $topic->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加话题
    public function add()
    {
        if (request()->isPost()) {
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['topic_create_time'] = time();
            if ($param['topic_status'] == 1) {
                $param['topic_release_time'] = time();
                $param['topic_create_time'] = time();
            } elseif ($param['Topic_status'] == 2) {
                $param['topic_release_time'] = 0;
                $param['topic_create_time'] = time();
            }
            $param['topic_owner_id'] = '0';
            unset($param['topic_status']);
            $topic = new TopicModel();
            $flag = $topic->insert($param, 'TopicValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //编辑话题状态
    public function edit()
    {
        $topic = new TopicModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $topic->edit($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'topic' => $topic->getInfoById($id),
            'topic_status' => config('topic_status'),
            'is_top' => config('is_top')
        ]);
        return $this->fetch();
    }
    //删除话题
    public function del()
    {
        $id = input('param.id');
        $topic = new TopicModel();
        $topicCollect = new TopicCollectModel();
        $topicCollect->delByWhere(array('topic_id'=>$id));
        $topicLike = new TopicLikeModel();
        $topicLike->delByWhere(array('topic_id'=>$id));
        $topicComment = new TopicCommentModel();
        $topicComment->delByWhere(array('topic_id'=>$id));
		$TopicAlbum = new TopicAlbumModel();
        $TopicAlbum->delByWhere(array('topic_id'=>$id));
		$message = new messageModel();
		$message->delByWhere(array('topic_id'=>$id));
        $flag = $topic->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
	
	 //话题类型
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
            $topicType = new TopicTypeModel();
            $selectResult = $topicType->getListByWhere($where, $offset, $limit);
            $status = config('type_status');
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['type_status'] = $status[$vo['type_status']];
                $operate = [
                    '编辑' => url('topic/editType', ['id' => $vo['id']]),
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $topicType->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch('topic/type');
    }
    //添加参与话题类型
    public function addType()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $topicType = new TopicTypeModel();
            $flag = $topicType->insert($param,'ClubTypeValidate');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $this->assign([
            'status' => config('notice_status')
        ]);
        return $this->fetch('topic/add_type');
    }
    //编辑参与话题类型
    public function editType()
    {
        $topicType = new TopicTypeModel();
        if (request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $topicType->edit($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'topicType' => $topicType->getInfoById($id),
            'type_status' => config('type_status')
        ]);
        return $this->fetch('topic/edit_type');
    }
    //删除参与话题类型
    public function delType()
    {
        $id = input('param.id');
        $topicType = new TopicTypeModel();
        $flag = $topicType->del($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
	
}
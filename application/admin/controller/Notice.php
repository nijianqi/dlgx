<?php
namespace app\admin\controller;

use app\admin\model\NoticeModel;
use app\admin\model\MessageModel;
use app\admin\model\MemberModel;

class Notice extends Base
{
    //公告列表
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['notice_title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $notice = new NoticeModel();
            $selectResult = $notice->getListByWhere($where,'*', $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['notice_release_time'] =  date('Y-m-d H:i:s', $vo['notice_release_time']);
                $operate = [
                    '删除' => "javascript:del('".$vo['id']."')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $notice->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //添加公告
    public function add()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['notice_release_time'] = time();
            $notice = new NoticeModel();
            $flag = $notice->insert($param,'NoticeValidate');
            if($flag['code'] == 1){
                $messageModel = new MessageModel();
                $memberModel = new MemberModel();
                $memberList = $memberModel->getListByWhere(array('member_status'=>1));
                foreach($memberList as $key=>$vo){
                   $messageModel->insertMessage('0',$vo['id'],$param['notice_content'],'3');
                }
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }
    //删除公告
    public function del()
    {
        $id = input('param.id');
        $notice = new NoticeModel();
        $noticeInfo = $notice->getInfoById($id);
        $flag = $notice->del($id);
        if($flag['code'] == 1){
            $messageModel = new MessageModel();
            $messageModel->delByWhere(array('message_content'=>$noticeInfo['notice_content'],'message_type'=>3));
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
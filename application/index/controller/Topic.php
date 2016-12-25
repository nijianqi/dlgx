<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\MemberModel;
use app\index\model\TopicModel;
use app\index\model\ClubModel;
use app\admin\model\ClubRuleModel;
use app\admin\model\ClubJoinModel;
use app\index\model\ClubExperienceModel;
use app\index\model\TopicCollectModel;
use app\index\model\TopicCommentModel;
use app\index\model\TopicComLikeModel;
use app\index\model\TopicLikeModel;
use app\index\model\TopicAlbumModel;
use app\index\model\TopComAlbumModel;
use app\index\model\MessageModel;

class Topic extends Controller
{
    protected $beforeActionList = [
        'checkMember' => ['only' => 'collect,comment,like,createTop,cancel']
    ];

    public function checkMember()
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
		if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        }
        if($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
    }

    public function index() //话题详情
    {
		$memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
		if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
		
        $topicId = input('param.topic_id');
        $topicModel = new TopicModel();
        $topic_info = $topicModel->getInfoByWhere(array('id' => $topicId,'topic_status' => 1));
        if($topic_info){
		$memberModel = new MemberModel();
        $member_info = $memberModel->getInfoById($topic_info['topic_owner_id']);
        $topic_info['member_name'] = $member_info['member_name'];
        $topic_info['member_icon'] = $member_info['member_icon'];
        $time = $topic_info['topic_create_time'] - time();
        if ($time) {
            $hour =  floor((time() - $topic_info['topic_create_time'])/ 86400 * 24);
            if($hour < 1 ){
                $min =  floor((time() - $topic_info['topic_create_time'])/ 86400 * 24 * 60);
                $topic_info['topic_create_min'] = $min;
            }elseif($hour == 24 || $hour > 24){
                $day =  floor((time() - $topic_info['topic_create_time'])/ 86400);
                $topic_info['topic_create_day'] = $day;
            }elseif($hour < 24 && $hour > 1 || $hour == 1 ){
                $topic_info['topic_create_hour'] = $hour;
            }
        }
        $topicAlbumModel = new TopicAlbumModel();
        $topicAlbumList = $topicAlbumModel->getListByWhere(array('topic_id' => $topicId));
        $topicCommentModel = new TopicCommentModel();
        $topicCommentCounts = $topicCommentModel->getCounts(array('topic_id' => $topicId));
        $topic_info['topicCommentCounts'] = $topicCommentCounts;
        $topicCommentList = $topicCommentModel->getListByWhere(array('topic_id' => $topicId));
        $flag=array();
        foreach($topicCommentList as $key=>$val){
            $memberModel = new MemberModel();
            $member_info = $memberModel->getInfoById($val['member_id']);
            $to_member_info = $memberModel->getInfoById($val['to_member_id']);
            $topicCommentList[$key]['member_name'] = $member_info['member_name'];
            $topicCommentList[$key]['member_id'] = $member_info['id'];
            $topicCommentList[$key]['to_member_name'] = $to_member_info['member_name'];
            $topicCommentList[$key]['to_member_id'] = $to_member_info['id'];
            $topicCommentList[$key]['member_icon'] = $member_info['member_icon'];
            $topicComLikeModel = new TopicComLikeModel();
            $topicComLike_info = $topicComLikeModel->getInfoByWhere(array('comment_id'=>$val['id'],'member_id'=>session('memberId')));
            if(!empty($topicComLike_info)){
                $topicCommentList[$key]['is_comLike'] = $topicComLike_info['is_like'];
            }else{
                $topicCommentList[$key]['is_comLike'] = '1';
            }
            $topicComLike_Counts= $topicComLikeModel->getCounts(array('comment_id'=>$val['id'],'is_like'=>'2'));
            $topicCommentList[$key]['topicComLike_Counts'] = $topicComLike_Counts;
            $flag[]=$topicCommentList[$key]['topicComLike_Counts'];
            $time = $val['comment_create_time'] - time();
        if ($time) {
            $hour =  floor((time() - $val['comment_create_time'])/ 86400 * 24);
            if($hour < 1 ){
                $min =  floor((time() - $val['comment_create_time'])/ 86400 * 24 * 60);
                $topicCommentList[$key]['comment_create_min'] = $min;
            }elseif($hour == 24 || $hour > 24){
                $day =  floor((time() - $val['comment_create_time'])/ 86400);
                $topicCommentList[$key]['comment_create_day'] = $day;
            }elseif($hour < 24 && $hour > 1 || $hour = 1){
                $topicCommentList[$key]['comment_create_hour'] = $hour;
            }
        }
			$topComAlbumModel = new TopComAlbumModel();
            $topComAlbumList = $topComAlbumModel->getListByWhere(array('comment_id'=> $val['id']));
            $topicCommentList[$key]['topComAlbumList'] =  $topComAlbumList;
        }
            array_multisort($flag, SORT_DESC, $topicCommentList);
        $topicLikeModel = new TopicLikeModel();
        $topicLikeCounts = $topicLikeModel->getCounts(array('topic_id' => $topicId,'is_like' => 2));
        $topic_info['topicLikeCounts'] = $topicLikeCounts;
        $topicLikeInfo = $topicLikeModel->getInfoByWhere(array('topic_id' => $topicId,'member_id'=>session('memberId')));
        if(!empty($topicLikeInfo)){
            $topic_info['topic_is_like'] = $topicLikeInfo['is_like'];
        }else{
            $topic_info['topic_is_like'] =1;
        }
        $topicCollectModel = new TopicCollectModel();
        $topicCollectInfo = $topicCollectModel->getInfoByWhere(array('topic_id' => $topicId,'member_id'=>session('memberId')));
        if(!empty($topicCollectInfo)){
            $topic_info['topic_is_collect'] = $topicCollectInfo['is_collect'];
        }else{
            $topic_info['topic_is_collect'] =1;
        }
        $memberId = session('memberId');
        $this->assign([
            'topic_info' => $topic_info,
            'topicCommentList'=>$topicCommentList,
            'topicAlbumList' =>$topicAlbumList,
            'memberId' => $memberId
        ]);
        return $this->fetch('/topic-detail');
		}else{
			$this->redirect('topic/newTopList');
		}
    }

    public function newTopList() //最新话题列表
    {
		$memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
		if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
        if(request()->isAjax()) {
            $topicModel = new TopicModel();
            $topic_list = $topicModel->getTopicMember(array('topic_status' => 1), '', '', 'topic_create_time desc');
            $topicAlbumModel = new TopicAlbumModel();
            $topicLikeModel = new TopicLikeModel();
            $topicCollectModel = new TopicCollectModel();
            $topicCommentModel = new TopicCommentModel();
            if (!empty($topic_list)) {
                $flag=array();
				$flag2=array();
                foreach ($topic_list as $key => $vo) {
                    $topicAlbumList = $topicAlbumModel->getListByWhere(array('topic_id' => $vo['id']), '', '', '3');
                    $topic_list[$key]['topicAlbumList'] = $topicAlbumList;
                    $time = $vo['topic_create_time'] - time();
                    if ($time) {
                        $hour = floor((time() - $vo['topic_create_time']) / 86400 * 24);
                        if ($hour < 1) {
                            $min = floor((time() - $vo['topic_create_time']) / 86400 * 24 * 60);
                            if($min == 0){
                                $topic_list[$key]['topic_create_time'] = '刚刚';
                            }else{
                                $topic_list[$key]['topic_create_time'] = $min.'分钟前';
                            }
                        } elseif ($hour == 24 || $hour > 24 && $hour < 720) {
                            $day = floor((time() - $vo['topic_create_time']) / 86400);
                            $topic_list[$key]['topic_create_time'] = $day.'天前';
                        } elseif ($hour < 24 && $hour > 1 || $hour == 1) {
                            $topic_list[$key]['topic_create_time'] = $hour.'小时前';
                        }elseif($hour > 720){
                            $topic_list[$key]['topic_create_time'] = date('Y年m月d日', $vo['topic_create_time']);
                        }else{
                            $topic_list[$key]['topic_create_time'] = '刚刚';
                        }
                    }
                    $topicAlbumCounts = $topicAlbumModel->getCounts(array('topic_id' => $vo['id']));
                    $topic_list[$key]['topicAlbumCounts'] = $topicAlbumCounts;
                    $topicLikeCounts = $topicLikeModel->getCounts(array('topic_id' => $vo['id'], 'is_like' => 2));
                    $topic_list[$key]['topicLikeCounts'] = $topicLikeCounts;
                    $topicCollectCounts = $topicCollectModel->getCounts(array('topic_id' => $vo['id'], 'is_collect' => 2));
                    $topic_list[$key]['topicCollectCounts'] = $topicCollectCounts;
                    $topicCommentCounts = $topicCommentModel->getCounts(array('topic_id' => $vo['id']));
                    $topic_list[$key]['topicCommentCounts'] = $topicCommentCounts;
                    $topicLikeInfo = $topicLikeModel->getInfoByWhere(array('member_id' => session('memberId'), 'topic_id' => $vo['id']));
                    $topic_list[$key]['is_like'] = $topicLikeInfo['is_like'];
                    $topicCollectInfo = $topicCollectModel->getInfoByWhere(array('member_id' => session('memberId'), 'topic_id' => $vo['id']));
                    $topic_list[$key]['is_collect'] = $topicCollectInfo['is_collect'];
                    $flag[]=$topic_list[$key]['is_top'];
					$flag2[]=$vo['id'];
                }
                array_multisort($flag, SORT_ASC,$flag2, SORT_DESC,$topic_list);
            }
            $return['lists'] = $topic_list;
            return json($return);
        }
        return $this->fetch('/topic');
    }

    public function hotTopList() //热门话题列表
    {
		$memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
		if(empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
        if(request()->isAjax()) {
            $topicModel = new TopicModel();
            $topic_list = $topicModel->getTopicMember(array('topic_status' => 1,'topic_create_time'=>array(array('gt',strtotime(date('Y-m-d',strtotime('-1 day')))),array('lt',strtotime(date('Y-m-d'))))), '', '', 'topic_num desc');
            $topicAlbumModel = new TopicAlbumModel();
            $topicLikeModel = new TopicLikeModel();
            $topicCollectModel = new TopicCollectModel();
            $topicCommentModel = new TopicCommentModel();
            if (!empty($topic_list)) {
                $flag=array();
                foreach ($topic_list as $key => $vo) {
                    $topicAlbumList = $topicAlbumModel->getListByWhere(array('topic_id' => $vo['id']), '', '', '3');
                    $topic_list[$key]['topicAlbumList'] = $topicAlbumList;
                    $time = $vo['topic_create_time'] - time();
                    if ($time) {
                        $hour = floor((time() - $vo['topic_create_time']) / 86400 * 24);
                        if ($hour < 1) {
                            $min = floor((time() - $vo['topic_create_time']) / 86400 * 24 * 60);
                            if($min == 0){
                                $topic_list[$key]['topic_create_time'] = '刚刚';
                            }else{
                                $topic_list[$key]['topic_create_time'] = $min.'分钟前';
                            }
                        } elseif ($hour == 24 || $hour > 24 && $hour < 720) {
                            $day = floor((time() - $vo['topic_create_time']) / 86400);
                            $topic_list[$key]['topic_create_time'] = $day.'天前';
                        } elseif ($hour < 24 && $hour > 1 || $hour == 1) {
                            $topic_list[$key]['topic_create_time'] = $hour.'小时前';
                        }elseif($hour > 720){
                            $topic_list[$key]['topic_create_time'] = date('Y年m月d日', $vo['topic_create_time']);
                        }else{
                            $topic_list[$key]['topic_create_time'] = '刚刚';
                        }
                    }
                    $topicAlbumCounts = $topicAlbumModel->getCounts(array('topic_id' => $vo['id']));
                    $topic_list[$key]['topicAlbumCounts'] = $topicAlbumCounts;
                    $topicLikeCounts = $topicLikeModel->getCounts(array('topic_id' => $vo['id'], 'is_like' => 2));
                    $topic_list[$key]['topicLikeCounts'] = $topicLikeCounts;
                    $topicCollectCounts = $topicCollectModel->getCounts(array('topic_id' => $vo['id'], 'is_collect' => 2));
                    $topic_list[$key]['topicCollectCounts'] = $topicCollectCounts;
                    $topicCommentCounts = $topicCommentModel->getCounts(array('topic_id' => $vo['id']));
                    $topic_list[$key]['topicCommentCounts'] = $topicCommentCounts;
                    $topicLikeInfo = $topicLikeModel->getInfoByWhere(array('member_id' => session('memberId'), 'topic_id' => $vo['id']));
                    $topic_list[$key]['is_like'] = $topicLikeInfo['is_like'];
                    $topicCollecInfo = $topicCollectModel->getInfoByWhere(array('member_id' => session('memberId'), 'topic_id' => $vo['id']));
                    $topic_list[$key]['is_collect'] = $topicCollecInfo['is_collect'];
                    $flag[]=$topic_list[$key]['topicLikeCounts'];
                }
                array_multisort($flag, SORT_DESC, $topic_list);
            }
            $return['lists'] = $topic_list;
            return json($return);
        }
        return $this->fetch('/topic-hot');
    }

    public function collect() //收藏话题
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
            $topicId = input('param.id');
            $is_collect = input('param.is_collect');
            $topicModel = new TopicModel();
            $topicInfo = $topicModel->getInfoById($topicId);
            if($topicInfo){
                $topicCollectModel = new TopicCollectModel();
                $topicCollectList = $topicCollectModel->getListByWhere(array('topic_id' => $topicId, 'member_id' => session('memberId')));
                if(!empty($topicCollectList)){
                    if($topicInfo&&$topicInfo['topic_status'] = 1){
                        $return['flag'] = $topicCollectModel->updateCollect(session('memberId'),$topicId,$is_collect);
                    }
                }else{
                    if($topicInfo&&$topicInfo['topic_status'] = 1){
                        $return['flag'] = $topicCollectModel->insertCollect(session('memberId'),$topicId,$is_collect);
                    }
                }
            }else{
                $return['flag']  = ['code' => -1, 'data' =>'', 'msg' => '收藏失败，话题不存在'];
            }


        }
        return json($return);
    }

    public function comment() //评论话题
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
            $topicId = input('param.topic_id');
            $comment = input('param.comment');
            $commentId = input('param.comment_id');
            $topicCommentModel = new TopicCommentModel();
            $TopicModel = new TopicModel();
            $topic = $TopicModel->getInfoById($topicId);
            if($topic){
                $TopicModel->update(array('topic_num'=>$topic['topic_num']+1),array('id'=>$topicId));
                if(empty($commentId)){
                    $commentId = 0;
                }
                $return = $topicCommentModel->insertComment($topicId,$comment,$commentId);
                $clubModel = new ClubModel();
                $clubJoinModel = new ClubJoinModel();
                $clubJoinInfo = $clubJoinModel->getInfoByWhere(array('member_id'=>session('memberId')));
                $clubInfo = $clubModel->getInfoByWhere(array('id'=>$clubJoinInfo['club_id']));
                if(!empty($clubInfo)){
                    $clubRuleModel = new ClubRuleModel();
                    $clubExperienceModel = new ClubExperienceModel();
                    $where = [];
                    $where['rule_name'] = ['like', '%评论话题%'];
                    $where['rule_status'] = 1;
                    $clubRuleInfo = $clubRuleModel->getInfoByWhere($where);
                    if(!empty($clubRuleInfo)){
                        $Counts = $clubExperienceModel->getCounts(array('member_id'=>session('memberId'),'content'=>['like', '%评论话题%'],'create_time'=>array(array('gt',strtotime(date('Y-m-d'))),array('lt',strtotime(date('Y-m-d',strtotime('+1 day')))))));
                        if($Counts <= $clubRuleInfo['rule_num']){
                            $arr = [];
                            $arr['member_id'] = session('memberId');
                            $arr['club_id'] = $clubInfo['id'];
                            $arr['content'] = '评论话题+'.$clubRuleInfo['rule_experience'].'经验值';
                            $arr['create_time'] = time();
                            $clubExperienceModel->insert($arr);
                            $club_experience = intval($clubInfo['club_experience'])+intval($clubRuleInfo['rule_experience']);
                            $clubModel->updateByWhere(array('club_experience'=>$club_experience),'',array('id'=>$clubInfo['id']));
                        }
                    }

                }
                if(request()->file()){
                    $album = request()->file('file');
                    $topComAlbumModel = new TopComAlbumModel();
                    foreach($album as $key=>$val){
                        $album_img = $topComAlbumModel->insertAlbum($val);
                        $topComAlbumModel->insertGetId(array('comment_id'=>$return,'album_img'=>$album_img.'?imageMogr2/size-limit/300k','create_time'=>time()));
                    }
                }
            }else{
                echo "<script>alert('话题不存在');</script>";
            }

        }
    }

    public function like() //点赞话题
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
            $topicId = input('param.id');
            $is_like = input('param.is_like');
            $topicModel = new TopicModel();
            $topicInfo = $topicModel->getInfoById($topicId);
            if($topicInfo){
                $topicLikeModel = new TopicLikeModel();
                $topicLikeList = $topicLikeModel->getListByWhere(array('topic_id' => $topicId, 'member_id' => session('memberId')));
                if(!empty($topicLikeList)){
                    if($topicInfo&&$topicInfo['topic_status'] = 1){
                        $return['flag'] = $topicLikeModel->updateLike(session('memberId'),$topicId,$is_like);
                    }
                }else{
                    if($topicInfo&&$topicInfo['topic_status'] = 1){
                        $return['flag'] = $topicLikeModel->insertLike(session('memberId'),$topicId,$is_like);
                    }
                }
            }else{
                $return['flag']  = ['code' => -1, 'data' =>'', 'msg' => '点赞失败，话题不存在'];
            }

        }
        return json($return);
    }

    public function com_like() //点赞评论
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
            $commentId = input('param.id');
            $is_like = input('param.is_like');
            $topicCommentModel = new TopicCommentModel();
            $commentInfo = $topicCommentModel->getInfoById($commentId);
            if($commentInfo){
                $topicComLikeModel = new TopicComLikeModel();
                $topicLikeComList = $topicComLikeModel->getListByWhere(array('comment_id' => $commentId, 'member_id' => session('memberId')));
                if(!empty($topicLikeComList)){
                        $return['flag'] = $topicComLikeModel->updateLike(session('memberId'),$commentId,$is_like);
                }else{
                        $return['flag'] = $topicComLikeModel->insertLike(session('memberId'),$commentId,$is_like);
                }
            }else{
                $return['flag']  = ['code' => -1, 'data' =>'', 'msg' => '点赞失败，话题评论不存在'];
            }


        }
        return json($return);
    }
	
	public function del() //删除话题
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if(empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
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
            $message = new MessageModel();
            $message->delByWhere(array('topic_id'=>$id));
            $flag = $topic->del($id);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
    }

    public function createTop() //发布话题
    {
       if (request()->isPost()) {
            $param = input('param.');
            $topic = new TopicModel();
			if(empty(session('memberId'))) {
            $this->redirect('index/show');
        }
            $param['topic_owner_id'] = session('memberId');
            $param['topic_create_time'] = time();
            $param['topic_release_time'] = time();
            $topic_id = $topic->insertGetId($param);
            if(request()->file()){
               $album = request()->file('file');
               $topAlbumModel = new TopicAlbumModel();
               foreach($album as $key=>$val){
                   $album_img = $topAlbumModel->insertAlbum($val);
                   $topAlbumModel->insertGetId(array('topic_id'=>$topic_id,'album_img'=>$album_img.'?imageMogr2/size-limit/300k','create_time'=>time()));
               }
           }
           $topicLikeModel = new TopicLikeModel();
           $topicCollectModel = new TopicCollectModel();
           $topicCollectModel->insert(array('topic_id'=>$topic_id,'member_id'=>session('memberId'),'is_collect'=>2,'apply_time'=>time()));
           $flag = $topicLikeModel->insert(array('topic_id'=>$topic_id,'member_id'=>session('memberId'),'is_like'=>2,'apply_time'=>time()));
           $clubModel = new ClubModel();
           $clubJoinModel = new ClubJoinModel();
           $clubJoinInfo = $clubJoinModel->getInfoByWhere(array('member_id'=>session('memberId')));
           $clubInfo = $clubModel->getInfoByWhere(array('id'=>$clubJoinInfo['club_id']));
           if(!empty($clubInfo)){
               $clubRuleModel = new ClubRuleModel();
               $clubExperienceModel = new ClubExperienceModel();
               $where = [];
               $where['rule_name'] = ['like', '%发布话题%'];
               $where['rule_status'] = 1;
               $clubRuleInfo = $clubRuleModel->getInfoByWhere($where);
               if(!empty($clubRuleInfo)){
                   $Counts = $clubExperienceModel->getCounts(array('member_id'=>session('memberId'),'content'=>['like', '%发布话题%'],'create_time'=>array(array('gt',strtotime(date('Y-m-d'))),array('lt',strtotime(date('Y-m-d',strtotime('+1 day')))))));
                   if($Counts <= $clubRuleInfo['rule_num']){
                       $arr = [];
                       $arr['member_id'] = session('memberId');
                       $arr['club_id'] = $clubInfo['id'];
                       $arr['content'] = '发布话题+'.$clubRuleInfo['rule_experience'].'经验值';
                       $arr['create_time'] = time();
                       $clubExperienceModel->insert($arr);
                       $club_experience = intval($clubInfo['club_experience'])+intval($clubRuleInfo['rule_experience']);
                       $clubModel->updateByWhere(array('club_experience'=>$club_experience),'',array('id'=>$clubInfo['id']));
                   }
               }

           }
           $return['code'] = $flag['code'];
		   if($flag['msg'] == '添加成功'){
			    $return['msg'] = '话题发布成功!';
		   }else{
			    $return['msg'] = $flag['msg'];
		   }
           $this->assign([
               'return' => $return
           ]);
        }
        if(empty($return)){
            $return['code'] = 0;
            $return['msg'] = '';
        }
        $this->assign([
            'return' => $return
        ]);
        return $this->fetch('/topic-publish');
    }
}

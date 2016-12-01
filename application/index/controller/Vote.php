<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\MemberModel;
use app\index\model\VoteModel;
use app\index\model\VoteCommentModel;
use app\index\model\VoteJoinModel;
use app\index\model\VoteApplyModel;
use app\index\model\VoteNumModel;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Vote extends Controller
{
    protected $beforevoteionList = [
        'checkMember' => ['only' => 'index,vote,comment,apply']
    ];

    public function checkMember()
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if (empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if (empty($member['member_tel'])) {
            $this->redirect('member/edit');
        }
        if ($member['member_status'] == 2) {
            $this->redirect('member/index');
        }
    }

    public function index() //投票活动详情
    {
        $voteId = input('param.vote_id');
        $cpId = input('param.cp_id');
        $voteModel = new VoteModel();
        $voteJoinModel = new VoteJoinModel();
        $memberCounts = $voteJoinModel->getCounts(array('vote_id' => $voteId));
        $voteNumModel = new VoteNumModel();
        $voteNumCounts = $voteNumModel->getCounts(array('vote_id' => $voteId));
        $voteInfo = $voteModel->getInfoById($voteId);
        if ($voteInfo) {
            $voteInfo['vote_time'] = $voteInfo['vote_end_time'] - time();
            $voteModel->edit(array('id' => $voteId, 'visits' => $voteInfo['visits'] + 1));
        }
        if (!empty($cpId)) {
            $where['cp_id'] = ['like', '%' . $cpId . '%'];
            $voteJoinInfo = $voteJoinModel->getInfoBywhere($where);
            if (!empty($voteJoinInfo)) {
                $voteJoinInfo['album_img'] = unserialize($voteJoinInfo['album_img']);
                $voteJoinInfo['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $voteJoinInfo['member_id']));
            }
            $this->assign([
                'memberCounts' => $memberCounts,
                'voteJoinInfo' => $voteJoinInfo,
                'voteInfo' => $voteInfo,
                'voteNumCounts' => $voteNumCounts,
                'voteId' => $voteId
            ]);
        } else {
            $voteJoinList = $voteJoinModel->getJoinMember(array('vote_id' => $voteId), '', '', 'join_time asc');
            foreach ($voteJoinList as $key => $val) {
                $voteJoinList[$key]['album_img'] = unserialize($val['album_img']);
                $voteJoinList[$key]['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $val['member_id']));
            }
            $this->assign([
                'memberCounts' => $memberCounts,
                'voteJoinList' => $voteJoinList,
                'voteInfo' => $voteInfo,
                'voteNumCounts' => $voteNumCounts,
                'voteId' => $voteId
            ]);
        }
        return $this->fetch('/vote');
    }

    public function rankingList() //排行榜
    {
        $voteId = input('param.vote_id');
        $cpId = input('param.cp_id');
        $voteJoinModel = new VoteJoinModel();
        $voteNumModel = new VoteNumModel();
        if (!empty($cpId)) {
            $where['cp_id'] = ['like', '%' . $cpId . '%'];
            $voteJoinInfo = $voteJoinModel->getInfoBywhere($where);
            if (!empty($voteJoinInfo)) {
                $voteJoinInfo['album_img'] = unserialize($voteJoinInfo['album_img']);
                $voteJoinInfo['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $voteJoinInfo['member_id']));
                $flag = array();
                $voteJoinList = $voteJoinModel->getListBywhere(array('vote_id' => $voteId), '', '', '', 'id desc');
                foreach ($voteJoinList as $key => $val) {
                    $voteJoinList[$key]['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $val['member_id']));
                    $flag[] = $voteJoinList[$key]['vote_num'];
                }
                array_multisort($flag, SORT_DESC, $voteJoinList);
                foreach ($voteJoinList as $key => $val) {
                    if ($val['member_id'] == $voteJoinInfo['member_id']) {
                        $paiMing = $key + 1;
                    }
                }
            } else {
                $voteJoinInfo = '';
                $paiMing = '';
            }
            $this->assign([
                'voteJoinInfo' => $voteJoinInfo,
                'paiMing' => $paiMing,
                'voteId' => $voteId
            ]);
        } else {
            $voteJoinList = $voteJoinModel->getJoinMember(array('vote_id' => $voteId), '', '', 'id desc');
            $flag = array();
            foreach ($voteJoinList as $key => $val) {
                $voteJoinList[$key]['album_img'] = unserialize($val['album_img']);
                $voteJoinList[$key]['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $val['member_id']));
                $flag[] = $voteJoinList[$key]['vote_num'];
            }
            array_multisort($flag, SORT_DESC, $voteJoinList);
            $this->assign([
                'voteJoinList' => $voteJoinList,
                'voteId' => $voteId
            ]);
        }
        return $this->fetch('/ranking-list');
    }

    public function playerDetail() //选手详情
    {
        $memberId = input('param.member_id');
        $voteId = input('param.vote_id');
        $voteJoinModel = new VoteJoinModel();
        $voteJoinInfo = $voteJoinModel->getInfoBywhere(array('member_id' => $memberId, 'vote_id' => $voteId));
        $voteJoinInfo['album_img'] = unserialize($voteJoinInfo['album_img']);
        $voteNumModel = new VoteNumModel();
        $voteJoinInfo['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $voteJoinInfo['member_id']));
        $voteJoinList = $voteJoinModel->getListBywhere(array('vote_id' => $voteId), '', '', '', 'id desc');
        $flag = array();
        foreach ($voteJoinList as $key => $val) {
            $voteJoinList[$key]['vote_num'] = $voteNumModel->getCounts(array('vote_id' => $voteId, 'join_id' => $val['member_id']));
            $flag[] = $voteJoinList[$key]['vote_num'];
        }
        array_multisort($flag, SORT_DESC, $voteJoinList);
        foreach ($voteJoinList as $key => $val) {
            if ($val['member_id'] == $voteJoinInfo['member_id']) {
                $paiMing = $key + 1;
            }
        }
        $voteCommentModel = new VoteCommentModel();
        $voteCommentCounts = $voteCommentModel->getCounts(array('vote_id' => $voteId, 'join_id' => $memberId));
        $voteComList = $voteCommentModel->getListByWhere(array('vote_id' => $voteId, 'join_id' => $memberId));
        foreach ($voteComList as $key => $val) {
            $memberModel = new MemberModel();
            $member_info = $memberModel->getInfoById($val['member_id']);
            $to_member_info = $memberModel->getInfoById($val['to_member_id']);
            $voteComList[$key]['member_name'] = $member_info['member_name'];
            $voteComList[$key]['member_id'] = $member_info['id'];
            $voteComList[$key]['to_member_name'] = $to_member_info['member_name'];
            $voteComList[$key]['to_member_id'] = $to_member_info['id'];
            $voteComList[$key]['member_icon'] = $member_info['member_icon'];
        }
        if (empty($return)) {
            $return['code'] = 0;
            $return['msg'] = '';
        }
        $MemberId = session('memberId');
        $this->assign([
            'voteJoinInfo' => $voteJoinInfo,
            'voteCommentCounts' => $voteCommentCounts,
            'voteComList' => $voteComList,
            'voteId' => $voteId,
            'return' => $return,
            'paiMing' => $paiMing,
            'MemberId' => $MemberId
        ]);
        return $this->fetch('/player-detail');
    }

    public function comment() //评论
    {
        $memberModel = new MemberModel();
        $member = $memberModel->getInfoById(session('memberId'));
        if (empty($member['member_tel'])) {
            $this->redirect('member/edit');
        } else {
            $voteId = input('param.vote_id');
            $JoinId = input('param.join_id');
            $comment = input('param.comment');
            $commentId = input('param.comment_id');
            $VoteModel = new VoteModel();
            $Vote = $VoteModel->getInfoById($voteId);
            if ($Vote) {
                if (empty($commentId)) {
                    $commentId = 0;
                }
                $voteCommentModel = new VoteCommentModel();
                $return = $voteCommentModel->insertComment($voteId, $comment, $commentId, $JoinId);
                return json($return);
            } else {
                echo "<script>alert('选手不存在');</script>";
            }
        }

    }

    public function apply() //我要报名
    {
        $voteId = input('param.vote_id');
		$VoteModel = new VoteModel();
		$voteInfo = $VoteModel->getInfoById($voteId);
		if (empty(session('memberId'))) {
            $this->redirect('index/index');
        }
        if (request()->isPost()) {
            $param = input('param.');
            $VoteApply = new VoteApplyModel();
            $Vote = $VoteApply->getInfoByWhere(array('member_id' => session('memberId'), 'vote_id' => $voteId, 'verify_status' => array('neq', 3)));
            if (!empty($Vote)) {
                $return['code'] = -1;
                $return['msg'] = '您已报名过此次投票活动，可别分身乏术哦！';
                $this->assign([
                    'return' => $return
                ]);
            } elseif ($voteInfo['vote_end_time'] < time()) {
                $return['code'] = -1;
                $return['msg'] = '活动已结束！';
                $this->assign([
                    'return' => $return
                ]);
            } else {
                if (request()->file()) {
                    $album = request()->file('album');
                    foreach ($album as $key => $val) {
                        $album_img[] = $this->insertAlbum($val);
                    }
                    $param['album_img'] = serialize($album_img);
                }
                $param['vote_id'] = $voteId;
                $param['apply_time'] = time();
                $param['member_id'] = session('memberId');
                $param['verify_idea'] = '';
                $VoteApply = new VoteApplyModel();
                $flag = $VoteApply->insert($param);
                $return['code'] = $flag['code'];
                $return['msg'] = $flag['msg'];
                $this->assign([
                    'return' => $return
                ]);
            }
        }
        if (empty($return)) {
            $return['code'] = 0;
            $return['msg'] = '';
        }
        $this->assign([
            'return' => $return,
            'voteId' => $voteId
        ]);
        return $this->fetch('/apply');
    }

    public function vote() //投票
    {
        if (request()->isAjax()) {
            $voteId = input('param.vote_id');
			if(empty(session('memberId'))) {
            $this->redirect('index/index');
             }
            $Vote = new VoteModel();
            $VoteNum = new VoteNumModel();
            $vote_counts = $VoteNum->getCounts(array('vote_id' => $voteId, 'member_id' => session('memberId'), 'create_time' => array(array('gt', strtotime(date("Y-m-d"))), array('lt', strtotime(date('Y-m-d', strtotime('+1 day'))))))); //获取今天的投票数
            $voteInfo = $Vote->getInfoById($voteId);
            if ($voteInfo['vote_end_time'] < time()) {
                $return['code'] = -1;
                $return['msg'] = '活动已结束';
                return json($return);
            } else {
                if ($vote_counts < $voteInfo['vote_xznum']) {
                    $param = input('param.');
                    $param['member_id'] = session('memberId');
                    $param['create_time'] = time();
                    $return = $VoteNum->insert($param);
                    return json($return);
                } else {
                    $return['code'] = -1;
                    $return['msg'] = '超出每天限制投票数，每天只能投' . $voteInfo['vote_xznum'] . '票';
                    return json($return);
                }
            }
        }

    }

    public function insertAlbum($file) //放入七牛云相册
    {
        require_once APP_PATH . '../vendor/qiniu/autoload.php';
        // 用于签名的公钥和私钥
        $accessKey = config('ACCESSKEY');
        $secretKey = config('SECRETKEY');
        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);
        $bucket = config('BUCKET');
        // 生成上传Token
        $token = $auth->uploadToken($bucket);
        // 要上传文件的本地路径
        $filePath = $file->getRealPath();

        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
        // 上传到七牛后保存的文件名
        $key = substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err != null) {
            return FALSE;
        } else {
            return config('DOMAIN') . '/' . $key;
        }
    }
}

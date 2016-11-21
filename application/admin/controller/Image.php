<?php
namespace app\admin\controller;

use app\admin\model\ImageModel;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

class Image extends Base
{
    //图片列表
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
            $image = new ImageModel();
            $selectResult = $image->getListByWhere($where,'*', $offset, $limit);
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['image_create_time'] = date('Y-m-d H:i:s', $vo['image_create_time']);
                $operate = [
                    '删除' => "javascript:del('" . $vo['id'] . "')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $image->getCounts($where);
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }
    //上传图片
    public function upload()
    {
        if(request()->file()){
            $file = request()->file('fileList');
            require APP_PATH . '../vendor/qiniu/autoload.php';
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
            $key = substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            if ($err != null) {
                return FALSE;
            } else {
                $image = new ImageModel();
                $param =array();
                $param['image_name'] = $key;
                $param['image_url'] = config('DOMAIN').'/'.$key;
                $param['image_create_time'] = time();
                $image->insert($param, 'ImageValidate');
                return config('DOMAIN').'/'.$key;
            }
        }
        return $this->fetch();
    }
    //删除图片
    public function del()
    {
        $id = input('param.id');
        require APP_PATH . '../vendor/qiniu/autoload.php';
        //用于签名的公钥和私钥
        $accessKey = config('ACCESSKEY');
        $secretKey = config('SECRETKEY');
        //初始化Auth状态
        $auth = new Auth($accessKey, $secretKey);
        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);
        //你要测试的空间， 并且这个key在你空间中存在
        $bucket = config('BUCKET');
        $image = new ImageModel();
        $info = $image->getInfoById($id);
        $key = $info['image_name'];
        //删除$bucket 中的文件 $key
        $err = $bucketMgr->delete($bucket, $key);
        if ($err != null) {
            return FALSE;
        } else {
            $flag = $image->del($id);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
    }

}
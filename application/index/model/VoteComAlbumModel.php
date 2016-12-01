<?php
namespace app\index\model;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class VoteComAlbumModel extends BaseModel
{
    protected $table = "dlgx_vote_comalbum";

    public function insertAlbum($file) //放入相册图片
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
        $key = substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err != null) {
            return FALSE;
        } else {
            return config('DOMAIN').'/'.$key;
        }
    }
}

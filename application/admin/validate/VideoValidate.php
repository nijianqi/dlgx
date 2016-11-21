<?php
namespace app\admin\validate;

use think\Validate;

class VideoValidate extends Validate
{
    protected $rule = [
        ['video_name', 'unique:video', '该视频已存在']
    ];
}
<?php
namespace app\admin\validate;

use think\Validate;

class ImageValidate extends Validate
{
    protected $rule = [
        ['image_name', 'unique:image', '该图片名称已存在']
    ];
}
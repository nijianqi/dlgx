<?php
namespace app\admin\validate;

use think\Validate;

class BannerValidate extends Validate
{
    protected $rule = [
        ['banner_title', 'unique:banner', '该轮播图已存在']
    ];
}
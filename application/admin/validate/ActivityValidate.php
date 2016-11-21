<?php
namespace app\admin\validate;

use think\Validate;

class ActivityValidate extends Validate
{
    protected $rule = [
        ['act_name', 'unique:activity', '该活动已存在'],
    ];
}
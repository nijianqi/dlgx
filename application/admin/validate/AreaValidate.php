<?php
namespace app\admin\validate;

use think\Validate;

class AreaValidate extends Validate
{
    protected $rule = [
        ['area_name', 'unique:area', '该地区已存在']
    ];
}
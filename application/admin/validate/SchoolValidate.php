<?php
namespace app\admin\validate;

use think\Validate;

class SchoolValidate extends Validate
{
    protected $rule = [
        ['school_name', 'unique:school', '该地区已存在']
    ];
}
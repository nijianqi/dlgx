<?php
namespace app\admin\validate;

use think\Validate;

class ClubTypeValidate extends Validate
{
    protected $rule = [
        ['type_name', 'unique:ClubType', '该类型已存在']
    ];
}
<?php
namespace app\index\validate;

use think\Validate;

class ClubValidate extends Validate
{
    protected $rule = [
        ['club_name', 'require|unique:club', ['社团名称不能为空','该社团已存在']],
    ];

}
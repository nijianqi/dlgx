<?php
namespace app\admin\validate;

use think\Validate;

class VoteValidate extends Validate
{
    protected $rule = [
        ['vote_name', 'unique:vote', '该投票活动已存在'],
    ];
}
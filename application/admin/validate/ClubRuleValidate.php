<?php
namespace app\admin\validate;

use think\Validate;

class ClubRuleValidate extends Validate
{
    protected $rule = [
        ['rule_name', 'unique:ClubRule', '该社团规则已存在']
    ];
}
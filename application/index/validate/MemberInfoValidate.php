<?php
namespace app\index\validate;

use think\Validate;

class MemberInfoValidate extends Validate
{
    protected $rule = [
        ['member_name', 'require', '姓名不能为空'],
        ['member_sex', 'require', '性别不能为空'],
        ['member_school', 'require', '学校不能为空'],
        ['member_department', 'require', '院系不能为空'],
        ['member_class', 'require', '专业班级不能为空'],
    ];

}
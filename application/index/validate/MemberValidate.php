<?php
namespace app\index\validate;

use think\Validate;

class MemberValidate extends Validate
{
    protected $rule = [
        ['real_name', 'require', '姓名不能为空'],
        ['member_sex', 'require', '性别不能为空'],
        ['member_school', 'require', '学校不能为空'],
        ['member_department', 'require', '院系不能为空'],
        ['member_class', 'require', '专业班级不能为空'],
        ['member_tel', 'require', '手机号码不能为空']
    ];

}
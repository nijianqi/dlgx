<?php
namespace app\admin\validate;

use think\Validate;

class TopicValidate extends Validate
{
    protected $rule = [
        ['topic_name', 'unique:topic', '该话题已存在']
    ];
}
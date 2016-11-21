<?php
namespace app\admin\validate;

use think\Validate;

class NodeValidate extends Validate
{
    protected $rule = [
        ['node_name', 'unique:node', '该节点已存在']
    ];

}
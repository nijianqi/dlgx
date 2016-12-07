<?php

return [
    //模板参数替换
    'view_replace_str' => array(
        '__CSS__'    => '/static/admin/css',
        '__IMAGES__' => '/static/admin/images',
        '__JS__'     => '/static/admin/js'
    ),
    //管理员状态
    'user_status' => [
        '1' => '正常',
        '2' => '禁用'
    ],
    //角色状态
    'role_status' => [
        '1' => '启用',
        '2' => '禁用'
    ],
    //公告状态
    'notice_status' => [
        '1' => '发布',
        '2' => '不发布'
    ],
    //社团审核状态
    'verify_status' => [
        '2' => '通过',
        '3' => '拒绝'
    ],
    //社团审核状态
    'apply_status' => [
        '1' => '审核中',
        '2' => '已通过',
        '3' => '已拒绝'
    ],
    //社团状态
    'club_status' => [
        '1' => '正常',
        '2' => '禁用'
    ],
    //社团类型状态
    'type_status' => [
        '1' => '正常',
        '2' => '禁用'
    ],
    //话题状态
    'topic_status' => [
        '1' => '正常',
        '2' => '禁用'
    ],
    //视频状态
    'video_status' => [
        '1' => '发布',
        '2' => '不发布'
		] ,
    //会员性别
    'member_sex' => [
	    '0' => '未知',
        '1' => '男',
        '2' => '女'
    ],
    //会员性别
    'member_status' => [
        '1' => '正常',
        '2' => '禁用'
    ],
    //七牛上传配置
    'ACCESSKEY'=>'77TFY2VYN65eFsy9gi0m0wp7l2BiUmZ1SknbcLW0',
    'SECRETKEY'=>'s6D2Cf6ZnCWCkcamaim-FlCQNo-ekHxeD-ge-3pt',
    'BUCKET'=>'dlgx',
    'DOMAIN'=>'http://odfgs4sbe.bkt.clouddn.com',
    //备份目录
    'back_path' => APP_PATH . '../backup/'
];

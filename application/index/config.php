<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    //模板参数替换
    'view_replace_str' => array(
        '__CSS__' => '/static/index/css',
        '__IMAGES__' => '/static/index/images',
        '__JS__' => '/static/index/js',
        '__SCRIPT__' => '/static/index/script',
        '__STATIC__' => '/static/index/static',
        '__PUBLIC__' => '/static/index',
        '__PLUGINS__'=> '/static/index/plugins'
    ),
    //七牛上传配置
    'ACCESSKEY'=>'77TFY2VYN65eFsy9gi0m0wp7l2BiUmZ1SknbcLW0',
    'SECRETKEY'=>'s6D2Cf6ZnCWCkcamaim-FlCQNo-ekHxeD-ge-3pt',
    'BUCKET'=>'dlgx',
    'DOMAIN'=>'http://odfgs4sbe.bkt.clouddn.com',
    //备份目录
    'back_path' => APP_PATH . '../backup/'
];
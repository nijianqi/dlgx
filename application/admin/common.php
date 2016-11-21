<?php

// 生成操作按钮
function showOperate($operate = [])
{
    if(empty($operate)){
        return '';
    }
    $option = <<<EOT
    <div class="btn-group">
    <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        操作 <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
EOT;
    foreach($operate as $key=>$vo){
        $option .= '<li><a href="'.$vo.'">'.$key.'</a></li>';
    }
    $option .= '</ul></div>';
    return $option;
}

// 将字符解析成数组
function parseParams($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}

// 子孙树，用于菜单整理
function subTree($param, $pid = 0)
{
    static $res = [];
    foreach($param as $key=>$vo){
        if( $pid == $vo['pid'] ){
            $res[] = $vo;
            subTree($param, $vo['id']);
        }
    }
    return $res;
}

// 整理菜单方法
function prepareMenu($param)
{
    $parent = []; // 父类
    $child = [];  // 子类
    foreach($param as $key=>$vo){
        if($vo['father_node_id'] == 0){
            $vo['href'] = '#';
            $parent[] = $vo;
        }else{
            $vo['href'] = url($vo['controller_name'] .'/'. $vo['action_name']);
            $child[] = $vo;
        }
    }
    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){
            if($v['father_node_id'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }
    unset($child);
    return $parent;
}

// 解析备份sql文件
function analysisSql($file)
{
    // sql文件包含的sql语句数组
    $sqls = array();
    $f = fopen ( $file, "rb" );
    // 创建表缓冲变量
    $create = '';
    while ( ! feof ( $f ) ) {
        // 读取每一行sql
        $line = fgets ( $f );
        // 如果包含空白行，则跳过
        if (trim ( $line ) == '') {
            continue;
        }
        // 如果结尾包含';'(即为一个完整的sql语句，这里是插入语句)，并且不包含'ENGINE='(即创建表的最后一句)，
        if (! preg_match ( '/;/', $line, $match ) || preg_match ( '/ENGINE=/', $line, $match )) {
            // 将本次sql语句与创建表sql连接存起来
            $create .= $line;
            // 如果包含了创建表的最后一句
            if (preg_match ( '/ENGINE=/', $create, $match )) {
                // 则将其合并到sql数组
                $sqls [] = $create;
                // 清空当前，准备下一个表的创建
                $create = '';
            }
            // 跳过本次
            continue;
        }
        $sqls [] = $line;
    }
    fclose ( $f );
    return $sqls;
}




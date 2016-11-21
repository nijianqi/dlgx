<?php
namespace app\admin\model;

class NodeModel extends BaseModel
{
    protected $table = "dlgx_node";
    /**
     * 根据节点数据获取对应的菜单
     * @param $nodeStr
     * @return array
     */
    public function getMenu($nodeStr = '')
    {
        //超级管理员没有节点数据
        $where = empty($nodeStr) ? 'is_menu = 2' : 'is_menu = 2 and id in('.$nodeStr.')';
        $result = db('node')->field('id,node_name,controller_name,action_name,father_node_id,style')->where($where)->select();
        $menu = prepareMenu($result);
        return $menu;
    }

    public function getNodeInfo($id)
    {
        $result = $this->field('id,node_name,father_node_id')->select();
        $str = "";
        $node = $this->getInfoById($id);
        if(!empty($node)){
            $node = explode(',', $node);
        }
        foreach($result as $key=>$vo){
            $str .= '{ "id": "' . $vo['id'] . '", "father_node_id":"' . $vo['father_node_id'] . '", "name":"' . $vo['node_name'].'"';
            if(!empty($node) && in_array($vo['id'], $node)){
                $str .= ' ,"checked":1';
            }
            $str .= '},';
        }
        return "[" . substr($str, 0, -1) . "]";
    }
}

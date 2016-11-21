<?php
namespace app\index\model;

use think\Model;

class BaseModel extends Model
{
    //根据条件获取数据列表
    public function getListByWhere($where = array(), $field = '*', $offset = 0, $limit = 0, $order = 'id desc')
    {
        return $this->where($where)->field($field)->limit($offset, $limit)->order($order)->select();
    }
    //根据条件获取数据数量
    public function getCounts($where = array())
    {
        return $this->where($where)->count();
    }
    //根据ID获取数据
    public function getInfoById($id)
    {
        return $this->where('id', $id)->find();
    }
    //根据条件获取数据
    public function getInfoByWhere($where = array())
    {
        return $this->where($where)->find();
    }
    //插入数据
    public function insert($param, $validateName = '')
    {
        try{
            $result =  $this->validate($validateName)->save($param);
            if(FALSE === $result){
                //验证失败
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    //更新数据
    public function edit($param, $validateName = '')
    {
        try{
            $result =  $this->validate($validateName)->save($param, ['id' => $param['id']]);
            if(FALSE === $result){
                //验证失败
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '更新成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    //根据条件更新数据
    public function updateByWhere($param, $validateName = '', $where = array())
    {
        try{
            $result =  $this->validate($validateName)->update($param, $where);
            if(FALSE === $result){
                //验证失败
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '更新成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    //删除数据
    public function del($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
	//根据条件删除数据
    public function delByWhere($param)
    {
        try{
            $this->where($param)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}

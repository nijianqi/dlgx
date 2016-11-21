<?php
namespace app\admin\model;

class SchoolModel extends BaseModel
{
    protected $table = 'dlgx_school';

    public function getSchoolByWhere($where = array(), $offset = 0, $limit = 0)
    {
        return $this->field('dlgx_school.*,area_name')->join('dlgx_area', 'dlgx_school.area_id = dlgx_area.id')->where($where)->limit($offset, $limit)->order('id desc')->select();
    }
}
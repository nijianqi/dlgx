<?php
namespace app\index\controller;

use think\Controller;
use app\index\model\AreaModel;
use app\index\model\SchoolModel;

class School extends Controller
{
    public function index()
    {
        $this->assign([
            'memberForm' => input('param.memberForm')
        ]);

        $areaModel = new AreaModel();
        $areaList = $areaModel->getListByWhere();
        $this->assign([
            'areaList' => $areaList
        ]);

        $schoolModel = new SchoolModel();
        $schoolList = $schoolModel->getListByWhere();
        $this->assign([
            'schoolList' => $schoolList
        ]);

        return $this->fetch('/school');
    }
    public function show()
    {
        $this->assign([
            'memberForm' => input('param.memberForm')
        ]);

        $areaModel = new AreaModel();
        $areaList = $areaModel->getListByWhere();
        $this->assign([
            'areaList' => $areaList
        ]);

        $schoolModel = new SchoolModel();
        $schoolList = $schoolModel->getListByWhere();
        $this->assign([
            'schoolList' => $schoolList
        ]);

        return $this->fetch('/school-list');
    }
}

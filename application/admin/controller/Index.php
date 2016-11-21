<?php
namespace app\admin\controller;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('/index');
    }
    /**
     * 后台默认首页
     * @return mixed
     */
    public function indexPage()
    {
        return $this->fetch('index');
    }
}

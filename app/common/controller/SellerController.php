<?php
/**
 * Created by PhpStorm.
 * User: LYC
 * Date: 2018/4/17
 * Time: 14:31
 */

namespace app\common\controller;


use think\Db;

class SellerController extends BaseController
{
    protected static $isSeller;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        self::checkSeller();
    }

    public function checkSeller(){
        if(empty(self::$isSeller)){
            $input=input('post.');
            $user=Db::table('users')->where('user_id','=',$input['user_id'])->find();
            if($user['isseller'] != '1'){
                self::$isSeller='0';
                die(['code'=>'100','msg'=>'未授权']);
            }else{
                self::$isSeller='1';
            }
        }elseif (self::$isSeller != '1'){
            self::$isSeller='0';
            die(['code'=>'100','msg'=>'未授权']);
        }
    }
}
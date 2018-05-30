<?php
/**
 * Created by PhpStorm.
 * User: LYC
 * Date: 2018/4/15
 * Time: 18:07
 */

namespace app\user\controller;


use think\Controller;
use think\Db;
use think\Request;

class UserLogin extends Controller
{
    /**
     * 登录
     * @return \think\response\Json
     * @param username
     * @param password
     */
    function login(){
        $input=input('post.');
        $list=[];
        if(!$input['phone']||!$input['password']){
            $list=['code'=>'100','msg'=>'手机号码和密码不能为空'];
        }elseif(strlen($input['phone']) != 11 &&
            !is_numeric($input['phone'])){
            $list=['code'=>'100','msg'=>'手机号码不能小于11位'];
        }elseif(strlen($input['password'])<8){
            $list=['code'=>'100','msg'=>'密码不能小于8位'];
        } else{
            $user=Db::table('users')->where('phone','=',
                $input['phone'])->find();
            if($input['password']==$user['password']){
                $token = self::setToken();
                $data = [
                    'user_id'=>$user['user_id'],
                    'user_token'=>$token,
                    'time_out'=>date('Y-m-d H:i:s',strtotime('+7 day')),
                    'update_date'=>date('Y-m-d H:i:s',time())
                ];
                $tokenCount=Db::table('user_token')->where('user_id','=',
                    $user['user_id'])->count();
                if ($tokenCount==0){
                    $count=Db::table('user_token')->insert($data);
                    if($count==0){
                        $list=['code'=>'100','msg'=>'登录失败'];
                    }else{
                        $list=['code'=>'200','msg'=>'登录成功','data'=>[
                            'data'=>$user
                            ,'token'=>$token]];
                    };
                }elseif ($tokenCount > 1){
                    Db::table('user_token')->where('user_id','=',
                        $user['user_id'])->delete();
                    $list=['code'=>'100','msg'=>'登录失败，请重试'];
                }else{
                    $count=Db::table('user_token')
                        ->where('user_id','=',$user['user_id'])
                        ->update($data);
                    if(intval($count)!=0){
                        $list=['code'=>'0','msg'=>'登录成功','data'=>[
                            'data'=>$user,
                            'token'=>$token]];
                    }else{
                        $list=['code'=>'100','msg'=>'登录失败'];
                    }
                }
            }else{
                $list=['code'=>'100','msg'=>'用户名或密码错误'];
            }
        }
        return json_encode($list);
    }

    public static function setToken()
    {
        $token = md5(uniqid(md5(microtime(true)),true));  //生成一个不会重复的字符串
        $token = sha1($token);  //加密
        return $token;
    }

   /**
    * @param referee_phone
    * @param password
    * @param re_password
    * @param phone
    */
    public function register()
    {
        $input=input('post.');
        $list=[];
        if(!$input['phone']||!$input['password']){
            $list=['code'=>'100','msg'=>'用户名和密码不能为空'];
        }
        elseif(strlen($input['password'])<8){
            $list=['code'=>'100','msg'=>'密码不能小于8位'];
        }elseif ($input['password']!=$input['re_password']){
            $list=['code'=>'100','msg'=>'两次输入的密码不一致'];
        }elseif(strlen($input['phone']) != 11 ||
        !is_numeric($input['phone'])){
            $list =['code'=>'100','msg'=>'请输入合法的手机号码'];}
        else{
            $lega_phone=true;
            if(!empty($input['referee_phone'])){
                if(strlen($input['referee_phone']) != 11 ||
                    !is_numeric($input['referee_phone'])){
                    $lega_phone=false;
                    $list=['code'=>'100','msg'=>'请输入合法的手机号码'];
                }
            }
            if($lega_phone==true){
                $count=Db::table('users')->where('phone','=',$input['phone'])->count();
                if($count>0){
                    $list=['code'=>'100','msg'=>'手机号码已被注册'];
                }else{
                    $data=[
                        'password'=>$input['password'],
                        'isseller'=>'0',
                        'phone'=>$input['phone'],
                        'referee_phone'=>$input['referee_phone'],
                        'create_date'=>date('Y-m-d H:i:s',time()),
                        'update_date'=>date('Y-m-d H:i:s',time())
                    ];
                    if(Db::table('users')->insert($data)){
                        $list=['code'=>'200','msg'=>'注册成功'];
                    }else{
                        $list=['code'=>'100','msg'=>'注册失败'];
                    }
                }
            }
        }
        return json_encode($list);
    }

}
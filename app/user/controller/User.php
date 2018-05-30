<?php
/**
 * Created by PhpStorm.
 * User: LYC
 * Date: 2018/4/8
 * Time: 20:01
 */

namespace app\user\controller;

use app\common\controller\BaseController;
use app\common\MyConfig;
use think\Db;
use think\Exception;
use think\Request;

class User extends BaseController
{

//  /*
//   * 获取所有购物车数量
//   * */
//  public function getshopcatnum(){
//      $input = input('post.');
//      if($input['user_id'] != null){
//          $catnum = Db::table('order')
//              ->where('user_id','=',$input['user_id'])
//              ->where('status','=',$input['orderType'])
//              ->count();
//          return json_encode(['code'=>'100','msg'=>'error','data'=>$catnum]);
//      }else{
//          return json_encode(['code'=>'100','msg'=>'error','data'=>'0']);
//      }
//  }

    /*
     * 获取所有优惠卷
     * */
    public function getCoupon($user_id)
    {
        $user_id = input('post.user_id');
        $data = Db::table('coupon')->where('user_id', '=', $user_id)->select();
        return $data;
    }

    /**
     * @return string
     * @param user_id
     */
    public function getCollectGoods()
    {
        $input = input('post.');
        $goods = Db::table('collection')
            ->where('user_id', '=', $input['user_id'])
            ->select();

        $data = [];
        $i = 0;
        $collectionInfo = [];
        foreach ($goods as $good) {
            $goodsInfo = Db::table('goods')->where('id', '=', $good['goods_id'])->find();
            $collectionInfo[$i] = [
                'collect_id' => $good['id'],
                'goods_id' => $goodsInfo['id'],
                'iconimage_src' => $goodsInfo['iconimage_src'],
                'price' => $goodsInfo['price'],
                'describe' => $goodsInfo['describe'],
                'create_date' => $goodsInfo['create_date']
            ];
//          $data[$goodsInfo['id']]=$collectionInfo;
            $i++;
        }
        return json_encode(['code' => '0', 'msg' => '收藏列表获取成功', 'data' => $collectionInfo]);
    }

    /**
     * @return string
     * @param goods_id
     * @param user_id
     */
    public function addCollectionGoods()
    {
        $input = input('post.');
        $data = [
            'goods_id' => $input['goods_id'],
            'user_id' => $input['user_id'],
            'create_date' => date('y-m-d h:i:s', time()),
            'update_date' => date('y-m-d h:i:s', time())
        ];
        $list = [];
        $res = Db::table('collection')->insert($data);
        if ($res > 0) {
            $list = ['code' => '0', 'msg' => '收藏成功', 'data' => []];
        } else {
            $list = ['code' => '100', 'msg' => '收藏失败', 'data' => []];
        }
        return json_encode($list);
    }


    /**
     * @return string
     * @param user_id
     * @param goods_id
     */
    public function deleteCollectionGoods()
    {
        $input = input('post.');
        $list = [];
        $res = Db::table('collection')
            ->where('user_id', '=', $input['user_id'])
            ->where('goods_id', '=', $input['goods_id'])
            ->delete();
        if ($res > 0) {
            $list = ['code' => '0', 'msg' => '删除成功', 'data' => []];
        } else {
            $list = ['code' => '100', 'msg' => '删除失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**
     * 获取收货地址
     */
    public function getReceivingAddress(Request $request)
    {

        $user_id = input('post.user_id');
        if ($user_id != null) {
            $data = Db::table('receiving_address')
                ->where('user_id', '=', $user_id)->select();
            return json_encode(['code' => '0', 'msg' => '', 'data' => $data]);
        } else {
            return json_encode(['code' => '100', 'msg' => 'error', 'data' => '']);
        }
    }

    /** 添加收货地址,如果isdefault_address->0：不是默认地址，isdefault_address->1：默认地址
     * @param Request $request
     * @return string
     * @param receiving_name
     * @param address_region
     * @param address_details
     * @param phone_number
     * @param user_id
     */
    public function addReceivingAddress()
    {
        $input = input('post.');
        $list = [];
        if (strlen($input['receiving_name']) == 0) {
            $list = ['code' => '100', 'msg' => '收货人名字不能为空'];
        }
        if (strlen($input['address_region']) == 0) {
            $list = ['code' => '100', 'msg' => '收货区域不能为空'];
        }
        if (strlen($input['address_details']) == 0) {
            $list = ['code' => '100', 'msg' => '详细收货地址不能为空'];
        }
        if (strlen($input['phone_number']) != 11 &&
            is_numeric($input['phone_number'])
        ) {
            $list = ['code' => '100', 'msg' => '请输入合法的手机号码'];
        }
        $data = [
            'user_id' => $input['user_id'],
            'receiving_name' => $input['receiving_name'],
            'address_region' => $input['address_region'],
            'address_details' => $input['address_details'],
            'phone_number' => $input['phone_number'],
            'isdefault_address' => '0',
            'created_date' => date('Y-m-d H:i:s', time()),
            'update_date' => date('Y-m-d H:i:s', time())
        ];
        if (Db::table('receiving_address')->insert($data)) {
            $list = ['code' => '0', 'msg' => '添加收货地址成功'];
        } else {
            $list = ['code' => '100', 'msg' => '添加收货失败'];
        }
        return $list;
    }

    /**
     *修改默认收货地址
     */
    public function setDefaultReceivingAddress(Request $request)
    {
        $user_id = input('post.user_id');
        $input = input('post.');
        if ($user_id == null) {
            return json(['msg', '用户id不能为空']);
        }
        if (strlen($input['receivingAddressID']) == 0) {
            return json('msg', '收货地址id不能为空');
        }
        Db::startTrans();
        try {
            Db::table('receiving_address')
                ->where('user_id', '=', $user_id)
                ->update(['isdefault_address' => '0']);
            $update_ToOne_count = Db::table('receiving_address')
                ->where('user_id', '=', $user_id)
                ->where('id', '=', $input['receivingAddressID'])
                ->update(['isdefault_address' => '1',
                    'update_date' => date('y-m-d h:i:s', time())]);
            if ($update_ToOne_count != 0) {
                Db::commit();
                return json(['code' => '0', 'msg' => '修改默认收货地址成功']);
            } else {
                throw  new Exception();
            }
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => '100', '修改默认收货失败']);
        }
    }

    /**
     *删除收货地址
     */
    public function deleteReceivingAddress()
    {
        $input = input('post.');
        $list = [];
        $count = Db::table('receiving_address')
            ->where('user_id', '=', $input['user_id'])
            ->where('id', '=', $input['receivingAddressID'])
            ->delete();
        if ($count == 1) {
            $list = ['code' => '0', 'msg' => '删除地址成功', 'data' => []];
        } else {
            $list = ['code' => '100', 'msg' => '删除地址失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**
     *修改默认地址
     * @return string
     * @param receiving_name
     * @param address_region
     * @param address_details
     * @param phone_number
     * @param receiving_address_id
     * @param user_id
     */
    public function updateReceivingAddress()
    {
        $input = input('post.');
        $list = [];
        if (strlen($input['receiving_name']) == 0) {
            $list = ['code' => '100', 'msg' => '收货人名字不能为空'];
        }
        if (strlen($input['address_region']) == 0) {
            $list = ['code' => '100', 'msg' => '收货区域不能为空'];
        }
        if (strlen($input['address_details']) == 0) {
            $list = ['code' => '100', 'msg' => '详细收货地址不能为空'];
        }
        if (strlen($input['phone_number']) != 11 &&
            is_numeric($input['phone_number'])
        ) {
            $list = ['code' => '100', 'msg' => '请输入合法的手机号码'];
        }
        $data = [
            'receiving_name' => $input['receiving_name'],
            'address_region' => $input['address_region'],
            'address_details' => $input['address_details'],
            'phone_number' => $input['phone_number'],
            'isdefault_address' => '0',
            'update_date' => date('Y-m-d H:i:s', time())
        ];
        $count = Db::table('receiving_address')
            ->where('user_id', '=', $input['user_id'])
            ->where('id', '=', $input['receiving_address_id'])->update($data);
        if ($count == 1) {
            $list = ['code' => '0', 'msg' => '修改收货地址成功'];
        } else {
            $list = ['code' => '100', 'msg' => '修改收货地址失败'];
        }
        return $list;
    }

    /**
     *删除或添加收货地址
     */
    public function addOrUpdateReceivingAddress()
    {
        $input = input('post.');
        $list = [];
        if (empty($input['receiving_address_id'])) {
            $list = self::addReceivingAddress();
        } else {
            $list = self::updateReceivingAddress();
        }
        return json_encode($list);
    }

    /**
     * @return string
     * @param goods_id
     * @param user_id
     * @param Specifications
     * @param quantity
     * @param orderType
     * @param catString  购物车
     */
    public function createOrder()
    {
        $input = input('post.');
        $insertorder_goods = [];
        if (!empty($input['orderType']) && $input['orderType'] == 'G') {
            $status = 'G';
        } else {
            $status = 'P';
        }
        $defaultReceivingAdd_ID = Db::table('receiving_address')
            ->where('user_id', '=', $input['user_id'])
            ->where('isdefault_address', '=', '1')->value('id');
        $temp_order_account = date('ymdHis') . rand(1000, 2000);
        $findorders = Db::table('order')->where('order_account', '=', $temp_order_account)->select();
        while (sizeof($findorders) != 0) {
            $temp_order_account = date('ymdHis') . rand(1000, 2000);
            $findorders = Db::table('order')->where('order_account', '=', $temp_order_account)->select();
        }
//      $order_account = $temp_order_account;

        if (empty($input['catString'])) {
            $goods = Db::table('goods_detail')->where('goods_id', '=', $input['goods_id'])->find();
            $insertData = [
                'order_account' => $temp_order_account,
                'user_id' => $input['user_id'],
                'seller_id' => $goods['seller_id'],
                'status' => $status,
                'specifications' => $input['specifications'],
                'receiving_address_id' => $defaultReceivingAdd_ID,
                'transaction_price' => ($goods['price'] * $input['quantity']),
                'create_date' => date('y-m-d h:i:s', time()),
                'update_date' => date('y-m-d h:i:s', time())
            ];
            $insertorder_goods[0] = [
                'order_account' => $temp_order_account,
                'goods_id' => $input['goods_id'],
                'seller_id' => $goods['seller_id'],
                'specifications' => $input['specifications'],
                'quantity' => $input['quantity']
            ];
        } else {
            $total_prices = 0;
            $catStr = $input['catString'];
            $orderCount = explode('@', $catStr);
            $seller_id=null;
            for ($i = 0; $i < sizeof($orderCount); $i++) {
                $idCount = explode('=', $orderCount[$i]);
                if(!empty($idCount[0])&&!empty($idCount[1])){
                    $catid = $idCount[0];
                    $catnum = $idCount[1];
                    $order = Db::table('order')->where('user_id', '=', $input['user_id'])->where('order_id', '=', $catid)->find();
                    $insertorder_goods[$i] = [
                        'order_account' => $temp_order_account,
                        'goods_id' => $order['goods_id'],
                        'seller_id' => $order['seller_id'],
                        'specifications' => $order['specifications'],
                        'quantity' => $catnum
                    ];
                    $c=Db::table('order')->where('order_id', '=', $catid)->delete();
                    if ($order['quantity'] != 0) {
                        $total_prices = $total_prices + ($order['transaction_price'] / $order['quantity']) * $catnum;
                    }
                }
            }
            $transaction_price = sprintf("%.2f", $total_prices);
            $insertData = [
                'order_account' => $temp_order_account,
                'user_id' => $input['user_id'],
                'status' => $status,
                'seller_id'=>$seller_id,
                'receiving_address_id' => $defaultReceivingAdd_ID,
                'transaction_price' => $transaction_price,
                'create_date' => date('y-m-d h:i:s', time()),
                'update_date' => date('y-m-d h:i:s', time())
            ];
        }
        if (!empty($input['orderType']) && $input['orderType'] == 'G') {
            $res = Db::table('order')->insert([
                'user_id' => $input['user_id'],
                'seller_id' => $goods['seller_id'],
                'goods_id' => $input['goods_id'],
                'status' => $status,
                'specifications' => $input['specifications'],
                'quantity' => $input['quantity'],
                'transaction_price' => ($goods['price'] * $input['quantity']),
                'create_date' => date('y-m-d h:i:s', time()),
                'update_date' => date('y-m-d h:i:s', time())
            ]);
            if ($res > 0) {
                $list = ['code' => '0', 'msg' => '添加购物车成功', 'data' => []];
            } else {
                $list = ['code' => '100', 'msg' => '添加购物车失败'];
            }
        } else {
            $res1 = Db::table('order')->insert($insertData);
            $res2 = Db::table('order_goods')->insertAll($insertorder_goods);
            if (($res1 * $res2) > 0) {
                $list = ['code' => '0', 'msg' => '创建待付款订单成功', 'data' => ['order_account' => $temp_order_account]];
            } else {
                $list = ['code' => '100', 'msg' => '创建待付款订单失败'];
            }
        }
        return json_encode($list);
    }

    /**
     * @return string
     * @param order_account   直接购买
     * @param user_id
     * @param orderType  购物车
     * orderType与order_id必须传一个
     */
    public function getCreateOrderInfo()
    {
        $input = input('post.');
        $orders = [];
        $list = [];
        $total_price = 0.00;
        $order_goods = [];
        $shopcats=null;
        if (empty($input['order_account']) && !empty($input['orderType']) && $input['orderType'] == 'G') {
            $orderInfo = Db::table('order')
                ->where('user_id', '=', $input['user_id'])
                ->where('status', '=', 'G')
                ->select();
            for ($j = 0; $j < sizeof($orderInfo); $j++) {
                $orders[$j] = $orderInfo[$j];
            }
        } else {
            $orderInfo = Db::table('order')
                ->where('user_id', '=', $input['user_id'])
                ->where('status', '=', "P")->select();
            $orders= $orderInfo;
        }
        $receivingAddArray = Db::table('receiving_address')
            ->where('user_id', '=', $input['user_id'])
            ->select();
        $i = 0;
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $previewImgArray = null;
                $goods = null;
                if (empty($input['order_account'])) {
                    $goods = Db::table('goods')->where('id', '=', $order['goods_id'])->find();
                    $shopcats[$i] = [
                        'cat_id' => $order['order_id'],
                        'goods_id' => $order['goods_id'],
                        'iconimage_src' => $goods['iconimage_src'],
                        'describe' => $goods['describe'],
                        'specifications' => $order['specifications'],
                        'unit_price' => $goods['price'],
                        'quantity' => $order['quantity'],
                        'total_price' => sprintf("%.2f", $goods['price'] * $order['quantity'])
                    ];
                    $total_price = $goods['price'] * $order['quantity'] + $total_price;
                } else {
                    $j = 0;
                    $orders_goods = Db::table('order_goods')->where('order_account', '=', $order['order_account'])->select();
                    $status = $order['status'];
                    foreach ($orders_goods as $good) {
                        $goods = Db::table('goods')->where('id', '=', $good['goods_id'])->find();
                        $order_goods[$j] = [
                            'goods_id' => $goods['id'],
                            'iconimage_src' => $goods['iconimage_src'],
                            'describe' => $goods['describe'],
                            'specifications' => $good['specifications'],
                            'unit_price' => $goods['price'],
                            'quantity' => $good['quantity'],
                            'total_price' => sprintf("%.2f", $goods['price'] * $good['quantity']),
                        ];
                        $j++;
                    }
                    $total_price = $order['transaction_price'];
                }
                $i++;
            }
            $transaction_price = sprintf("%.2f", $total_price);
        } else {
            $order_goods = [];
            $transaction_price = sprintf("%.2f", 0);
            $receivingAddArray = [];
        }
        if (!empty($input['order_account'])) {
            $data = [
                'order_account' => $input['order_account'],
                'order_goods' => $order_goods,
                'status' => $status,
                'transaction_price' => $transaction_price,
                'receiving_address' => $receivingAddArray
            ];
        } else {
            $data = [
                'cat_goods' => $shopcats,
                'transaction_price' => $transaction_price,
            ];
        }
        $list = ['code' => '0', 'msg' => '', 'data' => $data];
        return json_encode($list);
    }

    /**删除订单
     * @return string
     * @param user_id
     * @param order_account
     */
    public function deleteOrder()
    {
        $input = input('post.');
        $list = [];
        $goods = Db::table('order_goods')
            ->where('order_account', '=', $input['order_account'])
            ->delete();
        $count = Db::table('order')
            ->where('user_id', '=', $input['user_id'])
            ->where('order_account', '=', $input['order_account'])
            ->delete();
        if ($count * $goods > 0) {
            $list = ['code' => '0', 'msg' => '删除订单成功', 'data' => []];
        } else {
            $list = ['code' => '100', 'msg' => '删除订单失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**删除订单
     * @return string
     * @param user_id
     * @param order_id
     * @param id_str
     */
    public function deleteCartGoods()
    {
        $input = input('post.');
        $list = [];
        $count=0;
        $idArray=explode("@",$input["id_str"]);
        for ($i=0;$i<sizeof($idArray);$i++){
            $re = Db::table('order')
                ->where('order_id', '=',$idArray[$i])
                ->delete();
            $count= $count+$re;
        }
        if ($count == sizeof($idArray)-1) {
            $list = ['code' => '0', 'msg' => '删除订单成功', 'data' => []];
        } else {
            $list = ['code' => '100', 'msg' => '删除订单失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**确认收货
     * @return string
     * @param user_id
     * @param order_account
     */
    public function trueReceiptgoods()
    {
        $input = input('post.');
        $list = [];
        $update = [
            'status' => 'R',
            'update_date' => date('y-m-d h:i:s', time())
        ];
        $res = Db::table('order')
            ->where('user_id', '=', $input['user_id'])
            ->where('order_account', '=', $input['order_account'])
            ->update($update);
        if ($res > 0) {
            $list = ['code' => '0', 'msg' => '确认收货成功', 'data' => []];
        } else {
            $list = ['code' => '100', 'msg' => '确认收货失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**评价商品
     * @return string
     * @return goodsreviewstr  goods_id=review@拼接字符串
     * @param user_id
     * @param order_account
     */
    public function reviewgoods()
    {
        $input = input('post.');
        $list = [];
        $goodsreviews = explode('@', $input['goodsreviewstr']);
        for ($i = 0; $i < sizeof($goodsreviews); $i++) {
            $goodsreview = explode('=', $goodsreviews[$i]);
            $goodsid = $goodsreview[0];
            $review = $goodsreview[1];
            $goods = Db::table('order_goods')
                ->where('order_account', '=', $input['order_account'])
                ->where('goods_id', '=', $goodsid)
                ->find();
            $reviews[$i] = [
                'goods_id' => $goodsid,
                'user_id' => $input['user_id'],
                'review' => $review,
                'order_account' => $input['order_account'],
                'specifications' => $goods['specifications'],
                'crate_date' => date('y-m-d', time())
            ];
        }
        $result = Db::table('reviews')->insertAll($reviews);
        if ($result == sizeof($goodsreviews)) {
            $update = [
                'status' => 'D',
                'update_date' => date('y-m-d h:i:s', time())
            ];
            $res = Db::table('order')
                ->where('user_id', '=', $input['user_id'])
                ->where('order_account', '=', $input['order_account'])
                ->update($update);
            if ($res > 0) {
                $list = ['code' => '0', 'msg' => '评价成功', 'data' => []];
            } else {
                $list = ['code' => '100', 'msg' => '评价失败', 'data' => []];
            }
        } else {
            $list = ['code' => '100', 'msg' => '插入评价数据失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**
     * @return string
     * @param username
     * @param birthday
     * @param email
     * @param sex
     */
    public function updateUserInfo()
    {
        $input = input('post.');
        $list = [];
        if (empty($input['username'])) {
            $list = ['code' => '100', 'msg' => '用户昵称不能为空', 'data' => []];
        } elseif (empty('birthday')) {
            $list = ['code' => '100', 'msg' => '用户生日不能为空', 'data' => []];
        } elseif (empty('email')) {
            $list = ['code' => '100', 'msg' => '用户邮箱不能为空', 'data' => []];
        } else {
            $insertData = [
                'username' => $input['username'],
                'birthday' => date('y-m-d', strtotime($input['birthday'])),
                'email' => $input['email'],
                'sex' => $input['sex'],
                'update_date' => date('y-m-d h:i:s', time())
            ];
            $res = Db::table('users')->where('user_id', '=', $input['user_id'])->update($insertData);
            if ($res > 0) {
                $list = ['code' => '0', 'msg' => '修改用户信息成功', 'data' => []];
            } else {
                $list = ['code' => '100', 'msg' => 'error', 'data' => []];
            }
        }
        return json_encode($list);
    }

    /**更换头像
     * @return string
     * @param headImage
     * @param user_id
     */
    public function uploadUserHeadImage()
    {
        $img = request()->file('headImage');
        $user_id = request()->post('user_id');

        $list = [];
        if (!empty($img)) {
            $info = $img->move(ROOT_PATH . 'public' . DS . 'upload' . DS . 'user_head_image');
            $dirSrc = ROOT_PATH . 'public' . DS . 'upload' . DS . 'user_head_image';
            $imgName = $info->getSaveName();
            $imgSrc = $dirSrc . '\\' . $imgName;
            $count = Db::table('users')
                ->where('user_id', '=', $user_id)
                ->update(['head_icon_image_src' => $imgSrc]);
            dump($count);
            if ($count == 1) {
                $list = ['code' => '0', 'msg' => '头像上传成功', 'data' => []];
            } else {
                $list = ['code' => '100', 'msg' => 'error', 'data' => []];
            }
        }
        return json_encode($list);
    }

    /**
     * @return string
     * @param password
     * @param new_password
     * @param re_new_password
     */
    public function updateUserPassword()
    {
        $input = input('post.');
        $list = [];
        if (empty($input['password'])) {
            $list = ['code' => '100', 'msg' => '旧密码不能为空', 'data' => []];
        } elseif (empty($input['new_password'])) {
            $list = ['code' => '100', 'msg' => '新密码不能为空', 'data' => []];
        } else {
            $user = Db::table('users')->where('user_id', '=', $input['user_id'])->find();
            if ($input['new_password'] != $input['re_new_password']) {
                $list = ['code' => '100', 'msg' => '两次输入的密码不一致', 'data' => []];
            } else {
                if ($input['password'] != $user['password']) {
                    $list = ['code' => '100', 'msg' => '密码错误', 'data' => []];
                } else {
                    $count = Db::table('users')->where('user_id', '=', $input['user_id'])->update(['password' => $input['new_password']]);
                    if ($count != 1) {
                        $list = ['code' => '100', 'msg' => 'error', 'data' => []];
                    } else {
                        $list = ['code' => '0', 'msg' => '修改密码成功', 'data' => []];
                    }
                }
            }
        }
        return json_encode($list);
    }

    /**获取商品评价列表
     * @return string
     * @return goods_id
     */
    public function getreviewgoods()
    {
        $input = input('post.');
        $list = [];

        $reviews = Db::table('reviews')
            ->where('goods_id', '=', $input['goods_id'])
            ->select();
        $i = 0;
        if (sizeof($reviews) == 0) {
            foreach ($reviews as $review) {
                $users = Db::table('users')
                    ->where('user_id', '=', $review['user_id'])
                    ->find();
                $reviewsdata[$i] = [
                    'username' => $users['username'],
                    'review' => $review['review'],
                    'specifications' => $review['specifications'],
                    'crate_date' => $review['crate_date']
                ];
                $i++;
            }
            $list = ['code' => '0', 'msg' => '获取评价成功', 'data' => $reviewsdata];
        } else {
            $list = ['code' => '100', 'msg' => '该商品没有评价', 'data' => []];
        }
        return json_encode($list);
    }

    /**支付订单
     * @return string
     * @param note_message
     * @param user_id
     * @param order_account
     */
    public function pay()
    {
        $input = input('post.');
        $list = [];
        if (self::getPayStatus() == true) {
            $note_message = null;
            if (!empty($input['note_message'])) {
                $note_message = $input['note_message'];
            }
            $update = [
                'status' => 'C',
                'note_meassge' => $note_message,
                'update_date' => date('y-m-d h:i:s', time())
            ];
            $res = Db::table('order')
                ->where('user_id', '=', $input['user_id'])
                ->where('order_account', '=', $input['order_account'])
                ->update($update);
            if ($res > 0) {
                $list = ['code' => '0', 'msg' => '支付成功', 'data' => []];
            } else {
                $list = ['code' => '100', 'msg' => '支付失败', 'data' => []];
            }
        } else {
            $list = ['code' => '100', 'msg' => '支付失败', 'data' => []];
        }
        return json_encode($list);
    }

    /**
     * @return bool
     * 预留支付接口
     */
    public function getPayStatus()
    {
        return true;
    }

    /**
     * @return string
     * user_id
     */
    public function getUserInfo()
    {
        $input = input('post.');
        $userInfo = Db::table('users')->where('user_id', '=', $input['user_id'])->find();

        $integral = $userInfo['integral'];
        $kind_qt = MyConfig::kind_qt;
        $kind_hj = MyConfig::kind_hj;
        $kind_bj = MyConfig::kind_bj;
        $kind_zs = MyConfig::kind_zs;

        if ($integral < $kind_qt) {
            $kind = 1;
            if (($integral / 10) == 0) {
                $rank = 1;
            } else {
                $rank = ceil($integral / 10);
            }
        } elseif ($kind_qt <= $integral && $integral < $kind_hj) {
            $kind = 2;
            $score = $integral - $kind_qt;
            $rank = ceil($score / 20);
        } elseif ($kind_hj <= $integral && $integral < $kind_bj) {
            $kind = 3;
            $score = $integral - $kind_hj;
            $rank = ceil($score / 30);
        } elseif ($kind_bj <= $integral && $integral < $kind_zs) {
            $kind = 4;
            $score = $integral - $kind_bj;
            $rank = ceil($score / 40);
        } else {
            $kind = 5;
            $score = $integral - $kind_zs;
            $rank1 = ceil($score / 40);
            if ($rank1 < 5) {
                $rank = $rank1;
            } else {
                $rank = 5;
            }
        }

        $couponCount = Db::table('coupon')->where('user_id', '=', $input['user_id'])->count();

        $order_array = self::getOrders($input['user_id']);

        $coupons = self::getCoupon($input['user_id']);

        $orderPnum = Db::table('order')->where('user_id', '=', $input['user_id'])->where('status', '=', 'P')->count();
        $orderCnum = Db::table('order')->where('user_id', '=', $input['user_id'])->where('status', '=', 'C')->count();
        $orderRnum = Db::table('order')->where('user_id', '=', $input['user_id'])->where('status', '=', 'R')->count();
        $orderDnum = Db::table('order')->where('user_id', '=', $input['user_id'])->where('status', '=', 'D')->count();
        $orderallnum = $orderPnum + $orderCnum + $orderRnum + $orderDnum;
        $data = [
            'username' => $userInfo['username'],
            'integral' => $userInfo['integral'],
            'head_icon_image_src' => $userInfo['head_icon_image_src'],
            'kind' => $kind,
            'rank' => $rank,
            'couponCount' => $couponCount,
            'order_array' => $order_array,
            'coupons' => $coupons,
            'orderPnum' => $orderPnum,
            'orderCnum' => $orderCnum,
            'orderRnum' => $orderRnum,
            'orderDnum' => $orderDnum,
            'orderallnum' => $orderallnum
        ];

        return json_encode(['code' => '0', 'msg' => '', 'data' => $data]);
    }


    /*
     * 获取所有订单及状态信息
     *  //status的值可能是P等待支付,C等待确认收货，R待评价，D已完成
     * */
    public function getOrders($user_id)
    {
        $orders = Db::table('order')->where('user_id', '=', $user_id)->select();
        $orders_lists = [];
        $i = 0;
        foreach ($orders as $order) {
            $order_account = $order['order_account'];
            if (!empty($order_account)) {
                $order_goods = Db::table('order_goods')
                    ->where('order_account', '=', $order_account)
                    ->select();
                $j = 0;
                foreach ($order_goods as $good) {
                    $goods = Db::table('goods')->where('id', '=', $good['goods_id'])->find();
                    $order_goods[$j] = [
                        'goods_id' => $goods['id'],
                        'iconimage_src' => $goods['iconimage_src'],
                        'describe' => $goods['describe'],
                        'specifications' => $good['specifications'],
                        'unit_price' => $goods['price'],
                        'quantity' => $good['quantity'],
                    ];
                    $j++;
                }
                $orderitem = Db::table('order')->where('order_account', '=', $order_account)->find();
                $userinfo = Db::table('users')->where('user_id', '=', $user_id)->find();
                $orders_lists[$i] = [
                    'order_account' => $order_account,
                    'status' => $orderitem['status'],
                    'username' => $userinfo['username'],
                    'user_id' => $userinfo['user_id'],
                    'update_date' => $orderitem['update_date'],
                    'transaction_price' => $orderitem['transaction_price'],
                    'order_goods' => $order_goods,
                ];
                $i++;
            }
        }
        return $orders_lists;
    }

    public function isLogin()
    {
        return json_encode(['code' => '0', 'msg' => '登录成功', 'data' => []]);
    }
}
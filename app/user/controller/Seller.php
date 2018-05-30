<?php
/**
 * Created by PhpStorm.
 * User: LYC
 * Date: 2018/4/17
 * Time: 14:29
 */

namespace app\user\controller;


use app\common\controller\SellerController;
use think\Db;
use app\common\MyConfig;

class Seller extends SellerController
{
    /**
     * @return string
     * @param user_id
     */
    public function getShoppingInfo(){
        $input=input('post.');
//      dump(date('y-m-d',time()));
        $shoppingInfo=Db::table('shopping')->where('user_id','=',$input['user_id'])->find();
        $users = Db::table('users')->where('user_id','=',$input['user_id'])->find();
        $integral=$shoppingInfo['integral'];
        $kind_qt=MyConfig::shoppingKind_qt;
        $kind_hj=MyConfig::shoppingKind_hj;
        $kind_bj=MyConfig::shoppingKind_bj;
        $kind_zs=MyConfig::shoppingKind_zs;

        if($integral<$kind_qt){
            $kind=1;
        }elseif ($kind_qt<=$integral && $integral<$kind_hj){
            $kind=2;
        }elseif ($kind_hj<=$integral && $integral<$kind_bj){
            $kind=3;
        }elseif ($kind_bj<=$integral && $integral<$kind_zs){
            $kind=4;
        }else{
            $kind=5;
        }

        $orders=Db::table('order')
            ->where('seller_id','=',$shoppingInfo['id'])
            ->where('status','=','R')
            ->whereOr('status','=','D')
            ->select();
        
        $Commission_list = self::getSellercommission($input['user_id']);
        
        $allCommission='0.00';
        $totalSale='0.00';
        $todaySale='0.00';
        foreach ($orders as $order){
            $orderAccount=null;
            $orderGoods=Db::table('order_goods')
                ->where('order_account','=',$order['order_account'])
                ->select();
            $totalCommission='0.00';
            foreach ($orderGoods as $goods){
                $goodsInfo=Db::table('goods')->where('id','=',$goods['goods_id'])->find();
                $totalCommission=$totalCommission+$goodsInfo['commission']*$goods['quantity'];
            }
            $allCommission = $totalCommission + $allCommission;
            $totalSale=$totalSale+$order['transaction_price'];
            if(date('y-m-d',strtotime($order['update_date']))==date('y-m-d',time())){
                $todaySale=$todaySale+$order['transaction_price'];
            };
        }

        $saleGoodsCount=Db::table('goods')->where('seller_id','=',$shoppingInfo['id'])->count();
        $data=[
            'seller_id'=>$shoppingInfo['id'],
            'head_icon_image_src'=>$users['head_icon_image_src'],
            'shopping_name'=>$shoppingInfo['shopping_name'],
            'integral'=>$shoppingInfo['integral'],
            'head_image_src'=>$shoppingInfo['head_image_src'],
            'kind'=>$kind,
            'saleGoodsCount'=>$saleGoodsCount,
            'allCommission'=>sprintf("%.2f",$allCommission),
            'totalSale'=>sprintf("%.2f",$totalSale),
            'todaySale'=>sprintf("%.2f",$todaySale),
            'Commission_list'=>$Commission_list
        ];

        return json_encode(['code'=>'0','msg'=>'','data'=>$data]);
    }
    
    /**
     * @return string
     * 获取分销商所有商品订单
     * user_id
     */
    public function getSellerorder(){
    	$shopinfo = Db::table('shopping')->where('user_id','=',input('post.user_id'))->find();
    	$seller_id=$shopinfo['id'];
    	$list=[];
    	$orders = Db::table('order')->where('seller_id','=',$seller_id)->select();
    	if(!empty($orders)){
    		$i=0;
    		$orderitems=[];
    		foreach($orders as $order){
    			$j=0;
    			$total_commission='0.00';
            	$orders_goods=Db::table('order_goods')->where('order_account','=',$order['order_account'])->select();
            	$order_goods=[];
            	foreach($orders_goods as $good){
            		$goods=Db::table('goods')->where('id','=',$good['goods_id'])->find();
            		$order_goods[$j]=[
		                'goods_id'=>$goods['id'],
		                'iconimage_src'=>$goods['iconimage_src'],
		                'describe'=>$goods['describe'],
		                'specifications'=>$good['specifications'],
		                'unit_price'=>$goods['price'],
		                'quantity'=>$good['quantity'],
		                'total_commission'=>sprintf("%.2f",$goods['commission']*$good['quantity'])
		            ];
		            $total_commission=$goods['commission']*$good['quantity']+$total_commission;
		            $j++;
            	}
            	$all_commission=sprintf("%.2f",$total_commission);
            	$userinfo = Db::table('users')->where('user_id','=',$order['user_id'])->find();
            	$orderitems[$i]=[
	                'order_account'=>$order['order_account'],
	                'username'=>$userinfo['username'],
			        'user_id'=>$userinfo['user_id'],
			        'order_goods'=>$order_goods,
			        'status'=>$order['status'],
			        'all_commission'=>$all_commission,
			        'update_date'=>$order['update_date'],
			        'transaction_price'=>$order['transaction_price']
	            ];
            	$i++;	
    		}
            $list = ['code'=>'0','msg'=>'店铺订单列表获取成功','data'=>$orderitems];
    	}else{
    		$list = ['code'=>'100','msg'=>'店铺订单列表为空','data'=>[]];	
    	}
	    return json_encode($list);
    }
    
    /**
     * @return string
     * 获取分销商所有佣金收入列表
     * user_id
     */
    public function getSellercommission($user_id){
    	$shopinfo = Db::table('shopping')->where('user_id','=',$user_id)->find();
    	$seller_id=$shopinfo['id'];
    	$orders = Db::table('order')
    	    ->where('seller_id','=',$seller_id)
    	    ->where('status','=','R')
    	    ->whereOr('status','=','D')
    	    ->select();
    	if(!empty($orders)){
    		$i=0;
    		$orderitems=[];
    		foreach($orders as $order){	
    			$total_commission='0.00';
            	$orders_goods=Db::table('order_goods')->where('order_account','=',$order['order_account'])->select();
            	foreach($orders_goods as $good){
            		$goods=Db::table('goods')->where('id','=',$good['goods_id'])->find();
		            $total_commission=$goods['commission']*$good['quantity']+$total_commission;
            	}
            	$all_commission=sprintf("%.2f",$total_commission);
            	$userinfo = Db::table('users')->where('user_id','=',$order['user_id'])->find();
            	$orderitems[$i]=[
	                'username'=>$userinfo['username'],
			        'user_id'=>$userinfo['user_id'],
			        'head_icon_image_src'=>$userinfo['head_icon_image_src'],
			        'all_commission'=>$all_commission,
			        'update_date'=>$order['update_date'],
			        'transaction_price'=>$order['transaction_price']
	            ];
            	$i++;	
    		}
    	}else{
    		$orderitems=[];	
    	}
	    return $orderitems;
    }
    
    /**
     * 修改店铺名字
     * @return string、
     * @param shopping_name
     * @param seller_id
     * @param user_id
     */
    public function updateShoppingInfo(){
        $input=input('post.');
        $updateData=[
            'shopping_name'=>$input['shopping_name']
        ];
        $count=Db::table('shopping')->where('id','=',$input['seller_id'])->update($updateData);
        $list=[];
        if($count>0){
            $list=['code'=>'0','msg'=>'修改店铺名成功','data'=>[]];
        }else{
            $list=['code'=>'100','msg'=>'修改店铺名失败','data'=>[]];
        }
        return json_encode($list);
    }
	
    /**
     * @return string
     * 获取所有商品
     */
    public function getAllGoods(){
        $allGoods=Db::table('goods')->where('seller_id','=','1111')->select();
        return json_encode(['code'=>'0','msg'=>'供应商的所有商品','data'=>$allGoods]);
    }
	
    /**
     * 上架商品
     * @return \think\response\Json
     * @param user_id
     * @param goods_id
     */
    public function putAwayUPGoods(){
    	$input=input('post.');
    	$shopinfo = Db::table('shopping')->where('user_id','=',$input['user_id'])->find();
    	$seller_id=$shopinfo['id'];
        $list=[];
        $count=Db::table('goods')
            ->where('seller_id','=',$seller_id)
            ->where('status','=','S')
            ->count();
        if($count>=100){
            $list=['code'=>'100','msg'=>'您只能上架100件商品','data'=>[]];
        }else{
            $goods=Db::table('goods')->where('id','=',$input['goods_id'])->find();
            unset($goods['seller_id']);
            unset($goods['id']);
            unset($goods['status']);
            $goods['seller_id']=$seller_id;
            $goods['status']='S';
            $g_id=Db::table('goods')->insertGetId($goods);
            if($g_id){
                $goodsInfo=Db::table('goods_detail')
                    ->where('goods_id','=',$input['goods_id'])
                    ->find();
                unset($goodsInfo['seller_id']);
                unset($goodsInfo['goods_id']);
                $goodsInfo['seller_id']=$seller_id;
                $goodsInfo['goods_id']=$g_id;
                $count=Db::table('goods_detail')->insert($goodsInfo);
                if($count>=0){
                    $list=['code'=>'0','msg'=>'成功上架一件商品'];
                }
            }else{
                $list=['code'=>'100','msg'=>'没有该商品信息'];
            }
        }
        return json_encode($list);
    }

    /**
     * 商品下架
     * @return \think\response\Jsoan
     * @param goods_id
     * @param user_id
     */
    public function soldDownGoods(){
        $good_id=input('post.goods_id');
        $list=[];
        $shopinfo = Db::table('shopping')->where('user_id','=',input('post.user_id'))->find();
    	$seller_id=$shopinfo['id'];
        if(empty($seller_id)){
            $list = ['code'=>'100','msg'=>'用户id不能为空'];
        }else{
            $count=Db::table('goods')
                ->where('id','=',$good_id)
                ->where('seller_id','=',$seller_id)
                ->update(['status'=>'X']);
            if($count!=0){
                $list=['code'=>'0','msg'=>'成功下架一件商品'];
            }else{
                $list=['code'=>'100','msg'=>'没有该商品信息'];
            }
        }
        return json_encode($list);
    }

	/**
     * @return \think\response\Json
     * 获取经销商所有商品
     * 上架商品status:S;下架商品Status:X
     */
    public function getMyGoods(){
    	$user_id=input('post.user_id');
    	$shopinfo = Db::table('shopping')->where('user_id','=',$user_id)->find();
    	$seller_id=$shopinfo['id'];
        $list=[];
        $goods_s=[];
        $goods_x=[];
        if(empty($seller_id)){
            $list = ['code'=>'100','msg'=>'用户id不能为空'];
        }else{
            $myGoods=Db::table('goods')->where('seller_id','=',$seller_id)->select();
            $data=[];
            foreach ($myGoods as $goods){
                if($goods['status']=='S'){
                    $goods_s[$goods['id']]=$goods;
                }elseif ($goods['status']=='X'){
                    $goods_x[$goods['id']]=$goods;
                }
            }
            $data = [
                'up'=>$goods_s,
                'down'=>$goods_x
            ];
            $list=['code'=>'0','msg'=>'','data'=>$data];
        }
        return json_encode($list);
    }
}
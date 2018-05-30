<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Session;


class Index extends Controller
{
    
     /**
     * @return \think\response\Json
     * @param type
     * @param seller_id
     * 通过分类获取商品
     * 微商爆款，精选秒杀，新品速递，特卖好货
     */
    public function getGoodsByType(){
        $input=input('post.');
        $list=[];
        $data=[];
        if(!empty($input['type'])){
            $data = Db::table('goods')
                ->where('lable', 'like', '%'.$input['type'].'%')
                ->where('seller_id','=',$input['seller_id'])
                ->order('sale_count desc')
                ->limit(8)->select();
        }
        $list=['code'=>'0','msg'=>'','data'=>$data];
        return json_encode($list);
    }

    
    public function getBestSellerGoods(){
        $list=null;
        if(request()->isPost()) {
            $type = Request::instance()->param('type');
            $sllser_id = Request::instance()->put('sllser_id');
            if ($type != null && $type != "") {
                $list = Db::table('goods')
                    ->where('category', '=', $type)
                    ->where('seller_id','=',$sllser_id)
                    ->order('saleNum_count desc')
                    ->limit(20)->find();
            } else {
                $list = Db::table('goods')->order('saleNum_count desc')->limit(20)->select();
            }
        }
        return json(['code'=>'0','msg'=>'','data'=>$list]);
    }


    /**
     * @return string
     * @param  seller_id
     * @param  searchText
     * @param  offset
     * @param  sortType
     * @param  typeLimit
     * @param  priceLimitStart
     * @param  priceLimitEnd
     */
    public function getGoodsOrSearch(){
//  	dump('sggg');
        $input=input('param.');
        $seller_id=$input['seller_id'];
        $offset=1;
        $list=[];
        $sortType=null;
        $priceLimitStart=0;
        $priceLimit=null;
        $type_limit=null;
        if(!empty($input['sortType'])){
            $sortType=self::getSortType($input['sortType']);
        }
        if(!empty($input['offset'])){
            $offset=$input['offset'];
        }
        if(!empty($input['typeLimit'])){
            $str=$input['typeLimit'];
            $type_limit='category '.' = '."'$str'";
        }
//      dump($type_limit);
        if(!empty($input['priceLimitStart']) || !empty($input['priceLimitEnd'])){
            if(empty($input['priceLimitStart'])){
                $input['priceLimitStart']='0';
            }
            if(empty($input['priceLimitEnd'])){
                $input['priceLimitEnd']=null;
            }
            $priceLimit=self::getPriceLimit($input['priceLimitStart'],$input['priceLimitEnd']);
        }
        if(!empty($input['searchText'])){
            $data=Db::table('goods')
                ->where('describe', 'like','%'.$input['searchText'].'%',
                    'or','describe', 'like',$input['searchText'])
                ->whereOr('category','like','%'.$input['searchText'].'%',
                    'or','category','like',$input['searchText'])
                ->whereOr('lable','like','%'.$input['searchText'].'%',
                    'or','lable','like',$input['searchText'])
                ->where('seller_id','=',$seller_id)
                ->where($priceLimit)
                ->order($sortType)
                ->page($offset,16)
                ->select();
            $dataCount=Db::table('goods')
                ->where('describe', 'like','%'.$input['searchText'].'%',
                    'or','describe', 'like',$input['searchText'])
                ->whereOr('category','like','%'.$input['searchText'].'%',
                    'or','category','like',$input['searchText'])
                ->whereOr('lable','like','%'.$input['searchText'].'%',
                    'or','lable','like',$input['searchText'])
                ->where('seller_id','=',$seller_id)
                ->where($priceLimit)
                ->count();
            $pageCount=ceil($dataCount/16);
            $list=['code'=>'0','msg'=>'','data'=>$data,'pageCount'=>$pageCount,'offset'=>$offset];
        }else{
            $data=Db::table('goods')->where('seller_id','=',$seller_id)
                ->where($type_limit)
                ->where($priceLimit)
                ->order($sortType)
                ->page($offset,16)
                ->select();
            $dataCount=Db::table('goods')->where('seller_id','=',$seller_id)
                ->where($type_limit)
                ->where($priceLimit)
                ->count();
            $pageCount=ceil($dataCount/16);
            $list=['code'=>'0','msg'=>'','data'=>$data,'pageCount'=>$pageCount,'offset'=>$offset];
        }
        return json_encode($list); 
    }
    
    
    /**获取商品评价列表
     * @return string
     * @return goods_id  
     */
    public function getreviewgoods(){
        $input=input('post.');
        $list=[];
        
        $reviews = Db::table('reviews')
            ->where('goods_id','=',$input['goods_id'])
            ->select();
        $i=0;
        if(!empty($reviews)){
        	foreach($reviews as $review){
	        	$users = Db::table('users')
	        	    ->where('user_id','=',$review['user_id'])
	        	    ->find();
	        	$reviewsdata[$i]= [
	        	    'username'=>$users['username'],
	        	    'review'=>$review['review'],
	        	    'specifications'=>$review['specifications'],
	        	    'crate_date'=>$review['crate_date']
	        	];
	        	$i++;
	        }
	        $list=['code'=>'0','msg'=>'获取评价成功','data'=>$reviewsdata]; 
        }else{
        	$list=['code'=>'100','msg'=>'该商品没有评价','data'=>[]];
        }
        return json_encode($list);
    }
    public function getSortType($type){
        $sortType=null;
        if($type == 'hotSaleUp'){
            $sortType='sale_count asc';
        }elseif ($type == 'hotSaleDown'){
            $sortType='sale_count desc';
        }elseif ($type == 'priceUp'){
            $sortType='price asc';
        }elseif ($type == 'priceDown'){
            $sortType='price desc';
        }
        return $sortType;
    }

    public function getPriceLimit($limitStart,$limitEnd){
        $priceLimit=null;
        if(!empty($limitStart && empty($limitEnd))){
            $priceLimit='price >= '.$limitStart;
        }elseif(empty($limitStart && !empty($limitEnd))){
            $priceLimit='price <= '.$limitEnd;
        }elseif (!empty($limitStart) && !empty($limitEnd)){
            $priceLimit='price between '.$limitStart.' and '.$limitEnd;
        }
        return $priceLimit;
    }


    /**
     * @return string
     * @param goods_id
     * @param seller_id
     */
    public function getProductDetail(){
        $input=input('post.');
        $list=[];
        if(!empty($input['goods_id'])){
            $Goods=Db::table('goods_detail')
                ->where('goods_id','=',$input['goods_id'])
                ->select();
            $columns=[$Goods[0]['column1'],$Goods[0]['column2'],$Goods[0]['column3'],$Goods[0]['column4']];
            $i=0;
            $colArray=[];
            foreach ($columns as $colStr){
                if($colStr){
                    $temName=null;
                    $cols=explode('@',$colStr);
                    $colArray[$i]=$cols;
                    $i++;
                }
            }

            $detailImgArray=explode('@',$Goods[0]['goods_detail_image_src']);
            $previewImgArray=explode('@',$Goods[0]['preview_image_src']);
            
            $isCollect=false;

            if(!empty($input['user_id'])){
                $goods=Db::table('collection')
                    ->where('user_id','=',$input['user_id'])
                    ->where('goods_id','=',$input['goods_id'])
                    ->find();
                if(sizeof($goods)>0){
                    $isCollect=true;
                }
            }
            
            $data=[
                'goods_id'=>$Goods[0]['goods_id'],
                'goods_name'=>$Goods[0]['goods_name'],
                'category'=>$Goods[0]['category'],
                'describe'=>$Goods[0]['describe'],
                'price'=>$Goods[0]['price'],
                'sale_count'=>$Goods[0]['sale_count'],
                'is_collection'=>$isCollect,
                'stocknum'=>$Goods[0]['stocknum'],
                'integral'=>$Goods[0]['integral'],
                'collection_count'=>$Goods[0]['collection_count'],
                'placeofdelivery'=>$Goods[0]['placeofdelivery'],
                
                'Postage'=>$Goods[0]['Postage'],
                'columns'=>$colArray,
                'goods_detail_image_src'=>$detailImgArray,
                'preview_image_src'=>$previewImgArray
            ];
            $list=['code'=>'0','msg'=>'','data'=>$data];
        }
        return json_encode($list);
    }
    
}

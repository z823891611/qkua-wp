<?php namespace Qk\Modules\Templates;

class Widgets{
    public function init(){
        //add_action( 'widgets_init', array($this,'register_widgets'));
        //if(is_admin()){
            new \Qk\Modules\Templates\Widgets\Author;
            new \Qk\Modules\Templates\Widgets\Post;
            
            //关于我们
            new \Qk\Modules\Templates\Widgets\About; 
            
            //连接组
            new \Qk\Modules\Templates\Widgets\Links;
            //下载
            new \Qk\Modules\Templates\Widgets\Download;
            
            //热门话题
            new \Qk\Modules\Templates\Widgets\Topic;
            
            //圈子信息
            new \Qk\Modules\Templates\Widgets\CircleInfo;
            
            //热门圈子
            new \Qk\Modules\Templates\Widgets\Circle;
        //}
    }

    public function register_widgets(){
        //文章聚合小工具
        // register_widget( '\Qk\Modules\Templates\Widgets\Post');

        // //关于我们小工具
        // register_widget( '\Qk\Modules\Templates\Widgets\About');

        // //连接组小工具
        // register_widget( '\Qk\Modules\Templates\Widgets\Links');

        // //团队小工具
        // register_widget( '\Qk\Modules\Templates\Widgets\Team');

        // //广告小工具
        // register_widget( '\Qk\Modules\Templates\Widgets\Html');
        // register_widget( '\Qk\Modules\Templates\Widgets\Tocbot');

        //if(!is_audit_mode()){
            //用户面板
            //register_widget( '\Qk\Modules\Templates\Widgets\User');

            // //签到小工具
            // register_widget( '\Qk\Modules\Templates\Widgets\Mission');

            // //最新评论小工具
            // register_widget( '\Qk\Modules\Templates\Widgets\Comment');

            // //优惠劵小工具
            // register_widget( '\Qk\Modules\Templates\Widgets\Coupon');

            // //商品聚合
            // register_widget( '\Qk\Modules\Templates\Widgets\Products');

            // //快讯小工具
            // register_widget( '\Qk\Modules\Templates\Widgets\Newsflashes');

            // //财富排行
            // register_widget( '\Qk\Modules\Templates\Widgets\CreditTop');

            //作者
            //register_widget( '\Qk\Modules\Templates\Widgets\Author');
            //new \Qk\Modules\Templates\Widgets\Author;
            
            // //下载小工具
            // register_widget( '\Qk\Modules\Templates\Widgets\Download');

            // //导航连接小工具
            // register_widget( '\Qk\Modules\Templates\Widgets\Bookmark');

            // if(qk_get_option('circle_main','circle_open')){
            //     register_widget( '\Qk\Modules\Templates\Widgets\CircleInfo');
            //     register_widget( '\Qk\Modules\Templates\Widgets\HotCircle');
            //     register_widget( '\Qk\Modules\Templates\Widgets\RecommendedCircle');
            // }
        //}
    }
}
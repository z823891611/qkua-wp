<?php namespace Qk\Modules\Templates;
use Qk\Modules\Common\Post;
use Qk\Modules\Common\Comment;
/******文章模块*******/

class Single{

    public function init(){ 
        
        //文章顶部加入面包屑
        add_action('qk_single_wrapper_before',array($this,'get_breadcrumbs'),1);
        
        //文章下载模块
        add_filter( 'qk_single_content_after', array($this,'post_download'),1); 
        
        //文章标签
        add_action('qk_single_article_after',array($this,'list_tags'),1);
        
        //文章举报
        // add_action('qk_single_article_after',array($this,'article_report'),2);
        
        add_action('qk_single_article_after',array($this,'article_footer'),10);
        
        add_filter('body_class', array($this,'body_class'),10);
        
        //加入顶部广告代码
        add_action('qk_single_article_before',array($this,'single_ad_top'),5);
        
        //加入底部上下文
        add_action('qk_single_content_after',array($this,'posts_prevnext'),4);
        
        //相关推荐
        add_action('qk_single_content_after',array($this,'posts_related'),5);
        
        //加入底部广告
        add_action('qk_single_content_after',array($this,'single_ad_bottom'),3);
        
        //视频顶部
        add_action('qk_single_wrapper_before',array($this,'video_single_top'));
        add_action('qk_single_wrapper_after',array($this,'video_single_bottom'));
        
        //视频play顶部
        add_action('qk_single_wrapper_before',array($this,'play_single_top'));
        
        add_filter( 'the_content', array($this,'filter_circle_content'));
        
        add_filter( 'the_title',array($this, 'filter_the_title'),10,2);
    }
    
    public static function  filter_the_title( $title, $post_id ) {
        // if($post_id === get_the_ID() ){
        //     return $post_id.$title;
        // }
        
        if ( 'post' == get_post_type($post_id) && is_singular() ) {
            
            $subtitle = get_post_meta($post_id, 'qk_subtitle', true);
            
            if (!empty($subtitle)) {
                return $title. $subtitle;
            }
        }
        
        return $title;
    }
    
    public static function  filter_circle_content( $content ) {
        
        // 检查当前文章是否为circle类型
        if ( 'circle' == get_post_type() ) {
            $content = Comment::comment_filters($content);
    
            preg_match_all('/#([^#]+)#/', $content, $_topics);
            if (!empty($_topics[1])) {
                foreach ($_topics[1] as $topic) {
                    // 检查话题是否存在
                    $term = term_exists($topic,'topic');
    
                    if ($term !== 0 && $term !== null) {
                        // 获取话题的链接
                        $term_link = get_term_link((int)$term['term_id']);
                        // 替换话题为链接
                        $content = str_replace("#$topic#", "<a href=".$term_link."> #$topic# </a>", $content);
                    }
                }
            }
        }
        
        return $content;
    }
    
    //增加页面布局类名
    public static function body_class($classes) {
        
        if(is_singular()) {
            $post_id = get_the_id();
            
            if(get_post_type($post_id) !== 'post') return $classes;
            
            $layout = self::get_single_post_settings($post_id,'single_sidebar_layout');
            
            $sidebar_open = qk_get_option('single_sidebar_open');
            
            if($layout && $sidebar_open) {
                $classes[] = 'qk-sidebar-'.$layout;
            }else{
                $classes[] = 'qk-sidebar-close';
            }
        }
        
        return $classes;
    }
    
    //获取内页的设置项
    public static function get_single_post_settings($post_id,$type){

        if(get_post_type($post_id) !== 'post') return true;

        //默认内页文章样式
        $default = qk_get_option($type);
        $default= $default ? $default :'';

        $post_style = get_post_meta($post_id,'qk_'.$type,true);

        return $post_style !== '' && $post_style !== 'global' ? $post_style :  ($default ? $default : '');
    }
    
    public function single_ad_top(){

        $post_id = get_the_id();

        // $post_style = self::get_single_post_settings($post_id,'single_post_style');

        $html = qk_get_option('single_top_ads');

        if($html){
            echo '<div class="single-top-html mg-b qk-radius">'.$html.'</div>';
        }
    }
    
    public function single_ad_bottom(){

        // $post_id = get_the_id();

        $html = qk_get_option('single_bottom_ads');

        if($html){
            echo '<div class="single-bottom-html mg-b box qk-radius">'.$html.'</div>';
        }
    }
    
    //文章下载模块
    public static function post_download(){
        $post_id = get_the_ID();
        
        $download_open = get_post_meta($post_id,'qk_single_post_download_open',true);
        //是否开启文章下载功能
        if(!$download_open) return;
        
        $download_data = get_post_meta($post_id,'qk_single_post_download_group',true);
        $download_data = is_array($download_data) ? $download_data : array();
        $download_data = apply_filters('filter_download_data',$download_data,$post_id);

        if(empty($download_data) || !is_array($download_data)) return;
        
        $tabs = '';
        
        if(count($download_data) > 1) {
            $tabs .= '<div class="scroll-tabs-wrapper" ref="scrollTab">
                        <ul class="tabs-content">';
            foreach ($download_data as $key => $value) {
                
                $title = isset($value['title']) ? $value['title'] : get_the_title($post_id);
                
                $tabs .= '<li class="tab-item" :class="[{\'active\':'.$key.' === index}]" @click="changeTab('.$key.')">
                                <div class="thumb">'.(isset($value['thumb']) && $value['thumb'] ? '<img src="'.qk_get_thumb(array('url'=>$value['thumb'],'width'=>40,'height'=>40)).'" />' : '<b>'.($key + 1).'</b>').'</div>
                                <span class="text-ellipsis">'.$title.'</span>
                            </li>';
            }
            
            $tabs .= '</ul>
                    </div>';
        }
        
        echo '
            <div class="post_download mg-b">
                <div class="widget-title">下载</div>
                <div id="download-box" class="download-box box qk-radius" style=" padding: 20px; " ref="downloadBox">
                    '.$tabs.'
                    <div class="download-list" v-if="data" v-cloak>
                        <div class="download-list-item">
                            <div class="title">
                                <h2 v-text="data.title"></h2>
                            </div>
                            <div class="attrs-list" v-if="data.attrs.length">
                                <div class="attr-item" v-for="(item,index) in data.attrs">
                                    <span>{{item.key}}：</span>
                                    <span>{{item.value}}</span>
                                </div>
                            </div>
                            <div class="rights">
                                <div @click="show = !show" :class="[{open:show}]">
                                    <div class="current-user"  v-if="!data.current_user.can.allow">
                                        <span v-if="data.current_user.can.type == \'money\'">需支付 <span style=" color: var(--color-primary); ">¥</span> <span style=" font-size: 20px; color: var(--color-primary); " v-text="data.current_user.can.value">28</span>
                                        </span>
                                        <span v-if="data.current_user.can.type == \'credit\'">需支付 <span style=" font-size: 20px; color: var(--color-primary); " v-text="data.current_user.can.value"></span><span style=" color: var(--color-primary); "> 积分</span>
                                        </span>
                                        <span v-if="data.current_user.can.type == \'free\'">免费下载</span>
                                        <span v-if="data.current_user.can.type == \'comment\'">评论后下载</span>
                                        <span v-if="data.current_user.can.type == \'login\'">登录后下载</span>
                                        <span v-if="data.current_user.can.type == \'password\'">输入密码下载</span>
                                        <span v-if="data.current_user.can.type == \'none\'">当前为指定权限用户下载</span>
                                        <i class="ri-arrow-right-s-line"></i>
                                    </div>
                                    <div class="current-user" v-else>
                                        <span v-if="data.current_user.can.free_count">今日免费下载剩余 <span style=" font-size: 20px; color: var(--color-primary); ">{{data.current_user.can.free_count}}</span> 次 </span>
                                        <span v-else>已获得下载权限</span>
                                    </div>
                                    <div class="download-btn">
                                        <button style=" padding: 6px 16px; " @click.stop="go()">
                                            <i class="ri-download-fill"></i>下载
                                        </button>
                                    </div>
                                </div>
                                <ul class="list" v-if="show">
                                    <li class="item" v-for="(item,index) in data.rights" :class="item.lv == data.current_user.lv.lv.lv || item.lv == data.current_user.lv.vip.lv ? \'current\' : \'\'">
                                        <div>
                                            <span>{{item.name}}</span>
                                        </div>
                                        <div>
                                            <span v-if="item.type == \'money\'">￥<span v-text="item.value"></span></span>
                                            <span v-if="item.type == \'credit\'"><span v-text="item.value"></span> 积分</span>
                                            <span v-if="item.type == \'free\'">免费下载</span>
                                            <span v-if="item.type == \'comment\'">评论后下载</span>
                                            <span v-if="item.type == \'login\'">登录后下载</span>
                                            <span v-if="item.type == \'password\'">输入密码下载</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <!--<div class="qk-flex" style="
    margin: 12px;
    width: 100%;
    font-size: 12px;
    color: var(--color-text-secondary);
    line-height: 12px;
"><i class="ri-error-warning-line" style="
    font-size: 14px;
    margin-right: 2px;
"></i>尊贵的VIP用户您每日免费下载资源次数，剩余 <span class="font-number" style="
    color: var(--color-primary);
">19</span> 次                        </div>-->
                        </div>
                    </div>
                </div>
            </div>
            <!--#资源下载-->
        ';
    }
    
    //获取文章内面包屑导航
    public static function get_breadcrumbs(){
        
        if(!qk_get_option('single_breadcrumb_open')) return;
        
        $categorys = get_the_category();
        $html = '';
        
        if ($categorys) {
            $category = $categorys[0];
            $html      .= '
            <ul class="breadcrumb wrapper">
    		    <li><a href="' . get_bloginfo('url') . '">首页</a></li>
    		    <li>' . get_category_parents($category->term_id, true,'</li><li>').'正文</li>
    		 </ul>';
        }
        
        echo $html;
    }
    
    public static function list_tags(){
        $post_id = get_the_id();
        if(get_post_type($post_id) !== 'post') return;
        
        if(!self::get_single_post_settings($post_id,'single_tags_open')) return;
        
        echo  self::get_tag_list($post_id);
    }
    
    //获取文章标签
    public static function get_tag_list($post_id,$return = false){
        
        $post_id = (int)$post_id ? $post_id : get_the_id();
        $tags = wp_get_post_tags($post_id);
        if(!$tags) return;
        
        $html = '<div class="post-tags qk-flex"><i class="ri-price-tag-3-line"></i>';
        foreach ( $tags as $tag ) {
            $thumb = qk_get_default_img();
            $thumb = '<img src="'.qk_get_thumb(array('url'=>$thumb,'height'=>40,'width'=>40)).'" class="w-h" alt="'.esc_attr($tag->name).'" />';
            $html .= '<a class="tag-item qk-radius qk-flex" href="'.esc_url(get_tag_link( $tag->term_id )).'"><span class="tag-img">'.$thumb.'</span>';
            $html .= '<span class="tag-text">'.esc_attr($tag->name).'</span></a>';
        }
        $html .= '</div>';
        return $html;
    }
    
    //文章举报
    public static function article_report(){
        $post_id = get_the_id();
        
        if(!qk_get_option('report_open')) return;
        
        echo '
            <div class="article-report">
                <div class="report-btn" @click="report"><i class="ri-error-warning-line"></i> 举报</div>
            </div>
        ';
    }
    
    //文章底部 功能块
    public static function article_footer(){
        $post_id = get_the_id();
        //if(get_post_type($post_id) !== 'post') return;
        
        $favorites = Post::get_post_favorites($post_id);
        
        $vote = Post::get_post_vote($post_id);
        $comment_count = get_comments_number();
        
        $links = self::get_share_links();
        // print_r($links);

        echo '
            <div class="article-footer">
                <div class="tip mobile-show">有帮助？快来评价一下吧~</div>
                <div class="qk-flex fixed">
                    <div class="like qk-flex'.($vote['is_like'] ? ' active' : '').'" num="'.$vote['like'].'">
                        <div class="box" @click="vote(\'like\',$event)">
                            <i class="ri-thumb-up-line"></i>
                        </div>
                    </div>
                    <div class="comment qk-flex" num="'.$comment_count.'" @click="goComment">
                        <div class="box">
                            <i class="ri-chat-4-line"></i>
                        </div>
                    </div>
                    <div class="collect qk-flex'.($favorites['is_favorite'] ? ' active' : '').'" num="'.$favorites['count'].'">
                        <div class="box" @click="collect">
                            <i class="ri-star-smile-line"></i>
                        </div>
                    </div>
                    <div class="share qk-flex">
                        <div class="box">
                            <i class="ri-share-forward-line"></i>
                        </div>
                        <span class="text">分享</span>
                        <div class="share-dropdown-menu">  
                            <div class="social-share">  
                                <a rel="nofollow" class="share-btn qq" target="_blank" title="QQ好友" href="'.$links['qq'].'">  
                                    <span class="icon qq">  
                                        <i class="ri-qq-fill"></i>  
                                    </span>  
                                    <span>QQ好友</span>  
                                </a>  
                                <a rel="nofollow" class="share-btn qzone" target="_blank" title="QQ空间" href="'.$links['qzone'].'">  
                                    <span class="icon qzone">  
                                        <i class="ri-star-smile-fill"></i>  
                                    </span>  
                                    <span>QQ空间</span>  
                                </a>  
                                <a rel="nofollow" class="share-btn weibo" target="_blank" title="微博" href="'.$links['weibo'].'">  
                                    <span class="icon weibo">  
                                        <i class="ri-weibo-fill"></i>  
                                    </span>  
                                    <span>微博</span>  
                                </a>  
                                <!--<a rel="nofollow" class="share-btn poster" poster-share="1997" title="海报分享" href="javascript:;">  
                                    <span class="icon poster">  
                                        <i class="ri-image-fill"></i>  
                                    </span>  
                                    <span>海报分享</span>  
                                </a>  
                                <a rel="nofollow" class="share-btn copy" data-clipboard-text="https://www.zibll.com/1997.html" data-clipboard-tag="链接" title="复制链接" href="javascript:;">  
                                    <span class="icon link">  
                                        <i class="ri-link-m"></i>  
                                    </span>  
                                    <span>复制链接</span>
                                </a>-->
                            </div>  
                        </div>
                    </div>
                    <div class="report qk-flex">
                        <div class="box" @click="report">
                            <i class="ri-error-warning-line"></i>
                        </div>
                        <span class="text">举报</span>
                    </div>
                </div>
            </div>
        ';
    }
    
    //文章上一篇 和 下一篇
    public static function posts_prevnext(){
        
        $next_open = qk_get_option('single_next_open');

        if($next_open != '' && $next_open != 1) return;
        
        $post_id = get_the_id();

        if(get_post_type($post_id) !== 'post') return;
        
        $prev_post = get_previous_post($post_id);
        $next_post = get_next_post($post_id);
        
        $args = array( 'number' => 1, 'orderby' => 'rand', 'post_status' => 'publish' );

        //如果没有上一篇或者下一篇，则显示随机文章
        if(empty($prev_post)){
            $rand_posts = get_posts( $args );
            $prev_post = $rand_posts[0];
         
        }

        if(empty($next_post)){
            $rand_posts = get_posts( $args );
            $next_post = $rand_posts[0];
        }
        
        if(!empty($next_post) && !empty($prev_post)){
            echo '
                <div class="post-prev-next mg-b">
                    <div class="post-prev box qk-radius">
                        <p><i class="ri-arrow-left-s-line"></i>上一篇</p>
                        <a href="'.get_permalink($prev_post->ID).'">
                            <div class="title text-ellipsis">'.$prev_post->post_title.'</div>
                        </a>
                    </div>
                    <div class="post-next box qk-radius">
                        <p>下一篇<i class="ri-arrow-right-s-line"></i></p>
                        <a href="'.get_permalink($next_post->ID).'">
                            <div class="title text-ellipsis">'.$next_post->post_title.'</div>
                        </a>
                    </div>
                </div>
                <!--#上一篇 or 下一篇-->
            ';
        }
        
        //print_r($prev_post);
        //print_r($next_post);
    }
    
    //文章相关推荐
    public static function posts_related(){
        
        $related_open = qk_get_option('single_related_open');

        if($related_open != '' && $related_open != 1) return;
        
        $post_id = get_the_id();

        if(get_post_type($post_id) !== 'post') return;
        
        $count = qk_get_option('single_related_count');
        
        $posts = post::get_posts_related($post_id,$count);
        
        $html = '';
        
        if(isset($posts['data']) && !empty($posts['data'])){
            
            $title = qk_get_option('single_related_title');
            $html .= '
                <div class="post-related mg-b">
                    <div class="widget-title">'.$title.'</div>
                        <div id="swiper-scroll" class="post-related-list qk-radius box">
            ';
            foreach ($posts['data'] as $v) {
                $thumb = qk_get_thumb(array(
                    'url' => $v['thumb'],
                    'width' => 210,
                    'height' => 147,
                    'ratio' => 2
                ));
                
                $html .= '
                    <div class="post-item carousel__slide qk-radius">
                        <div class="post-thumbnail">
                            <a href="#" rel="nofollow" class="thumb-link">
                               '.qk_get_img(array('src'=>$thumb,'class'=>array('post-thumb','w-h'),'alt'=>$v['title'])).'
                            </a>
                        </div>
                        <a  href="'.$v['link'].'" class="post-info no-hover ">
                            <h2 class="text-ellipsis">'.$v['title'].'</h2>
                        </a>
                    </div>
                ';
            }
            $html .= '
                    </div>
                </div>
                <!--#相关-->
            ';
        }
        
        echo $html;
    }
    
    //视频页面顶部
    public static function video_single_top() {
        $post_id = get_the_id();
        if(get_post_type($post_id) !== 'video') return;
        
        //获取post meta
        $post_meta = Post::get_post_meta($post_id);
        $thumb = Post::get_post_thumb($post_id);
        
        $thumb = qk_get_thumb(array(
            'url' => $thumb,
            'width' => 180,
            'height' => 250,
            'ratio' => 2
        ));
        
        $video_meta = get_post_meta((int)$post_id, 'single_video_metabox', true );
        $video_list = !empty($video_meta['group']) ? $video_meta['group'] : array();
        
        echo '
            <div class="qk-video-single-header">
                <div class="video-info-blurbg-wrap" style="top: -80px;">
                    <div class="video-info-blurbg" style="background-image: url('.$thumb.');"></div>
                </div>
                <div class="wrapper">
                    <div class="video-info">
                        <div class="video-info-left" style="width:15%;    min-width: 100px;">
                            <div class="qk-radius post-thumbnail" style=" padding-top: 140.25%; ">
                                <img src="'.$thumb.'" alt="「'.get_the_title().'」封面" class="thumb-link" >
                            </div>
                        </div>
                        <div class="video-info-right">
                            <div class="right-top">
                                <h1>'.get_the_title().'</h1>
                                <div class="video-meta">
                                    <a href="'.$post_meta['author']['link'].'" class="post-user">
                                        '.qk_get_avatar(array('src'=>$post_meta['author']['avatar'],'alt'=>$post_meta['author']['name'].'的头像')).'
                                        <span class="user-name">'.$post_meta['author']['name'].'</span>
                                    </a>
                                    <div class="video-count">
                                        &nbsp;&nbsp;·&nbsp;&nbsp;
                                        '.$post_meta['date'].'
                                    </div>
                                </div>
                                <p class="text-ellipsis" style="--line-clamp: 2; ">'.get_post_field('post_excerpt').'</p>
                            </div>
                            <div class="right-bottom">
                                <div class="action-buttons">
                                    <a class="buy-button button no-hover" href="'.get_permalink($video_list[0]['id']).'">立即播放</a>
                                    <button class="vip-button button" onclick="createModal(\'vip\',{size:720})">VIP 免费观看</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="tabs" class="tabs">
                <div class="box">
                    <ul class="tabs-nav wrapper">
                        <li class="active">视频介绍</li>
                        <li>视频目录</li>
                        <li>视频讨论</li>
                        <div class="active-bar"></div>
                    </ul>
                </div>
                <div class="tabs-content">
            ';
            
    }
    
    public static function video_single_bottom() {
        $post_id = get_the_id();
        if(get_post_type($post_id) !== 'video') return;
        echo '</div></div>';
    }
    
    public static function play_single_top(){
        $post_id = get_the_id();
        $post_style = self::get_single_post_settings($post_id,'single_post_style');
        $post_type = get_post_type($post_id);
        if($post_type !== 'episode' && $post_style !== 'post-style-video') return;

        $video_count = \Qk\Modules\Common\Player::get_video_count($post_id);
        
        echo '<div class="qk-play-single-header wrapper">
            <div id="qk-player-wrap" class="qk-player-wrap" ref="player">
                <div class="qk-play-left">
                    <div class="qk-player-box">
                        <div id="moeplayer"></div>
                        <div class="player-popup" v-if="(!videos.length || !allowList[videoIndex]) && !preview">
                            <div class="mask-body">
                                <div class="video-role-box w-h" v-if="allowList[videoIndex] === false && videos.length" v-cloak>
                                    <div class="video-role-info" v-if="user.type == \'credit\'">
                                        <div class="role-tip-title">本视频需支付积分，请购买后观看视频</div>
                                        <div class="video-pay-price">
                                            总价 <span class="total-price" v-text="user.total_value"></span>
                                            <span class="unit-price">单价{{user.value}}</span>
                                        </div>
                                        <div class="video-action-button">
                                            <button @click="pay()">单集购买</button>
                                            <button @click="pay(true)">全部购买</button>
                                        </div>
                                    </div>
                                    <div class="video-role-info" v-if="user.type == \'money\'">
                                        <div class="role-tip-title">本视频需付费，请购买后观看视频</div>
                                        <div class="video-pay-price">
                                            总价 ￥<span class="total-price" v-text="user.total_value"></span>
                                            <span class="unit-price">单价￥{{user.value}}</span>
                                        </div>
                                        <div class="video-action-button">
                                            <button @click="pay()">单集购买</button>
                                            <button @click="pay(true)">全部购买</button>
                                        </div>
                                    </div>
                                    <div class="video-role-info" v-if="user.type == \'login\'">
                                        <div class="role-tip-title">本视频需登录后免费观看</div>
                                        <div class="video-action-button">
                                            <button @click="login()">登录/注册</button>
                                        </div>
                                    </div>
                                    <div class="video-role-info" v-if="user.type == \'password\'">
                                        <div class="role-tip-title">本视频需输入密码解锁观看</div>
                                        <div class="video-action-button">
                                            <button @click="pay()">输入密码</button>
                                        </div>
                                    </div>
                                    <div class="video-role-info" v-if="user.type == \'comment\'">
                                        <div class="role-tip-title">本视频需评论后免费观看</div>
                                        <p>您需要在视频最下面评论并刷新后，免费观看视频</p>
                                    </div>
                                    <div class="video-role-info" v-if="user.type == \'roles\'">
                                        <div class="role-tip-title">本视频只允许以下等级用户观看</div>
                                        <div class="video-roles">
                                            <ul class="roles-list">
                                                <li class="" v-for="(item,index) in user.roles">
                                                    <div class="lv-icon" v-if="item.image"><img :src="item.image" alt="item.name"></div>
                                                    <div class="lv-name" v-if="!item.image" v-text="item.name"></div>
                                                </li>
                                            </ul>
                                        </div>
                                        <p>你当前未达到以上等级，无法观看</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mask-bottom">
                                <div class="mask-item mask-item-left"></div>
                                <div class="mask-item mask-item-right"></div>
                            </div>
                        </div>
                    </div>
                </div>
                '.( $video_count > 1 ? '<div class="qk-play-right">
                         <div class="video-info-wrap">
                        '.( $post_type == 'episode' ? '<div class="video-info box">
                            <div class="video-info-left">
                                <div class="qk-radius post-thumbnail" style=" padding-top: 140.25%; ">
                                    <img  v-if="data.post" :src="data.post.thumb" :alt="data.post.title" class="thumb-link" v-cloak>
                                </div>
                            </div>
                            <div class="video-info-right"  v-if="data.post">
                                <h2 v-text="data.post.title"></h2>
                                <p class="text-ellipsis" v-text="data.post.desc"></p>
                                <div class="video-meta">
                                    <!--<div class="re role">免费</div>-->
                                    <a :href="data.post.user.link" class="qk-flex post-user">
                                        <div v-html="data.post.user.avatar_html"></div>
                                        <span class="user-name" v-text="data.post.user.name"></span>
                                    </a>
                                </div>
                            </div>
                        </div>':'').'
                        <div class="play-list-box box">
                            <div class="play-list-title">
                                <div class="list-left">
                                    <h4>视频目录</h4>
                                    <span class="progress" v-cloak v-if="videoList.length">({{videoIndex + 1}}/{{videoList.length}})</span>
                                </div>
                                <div class="list-right" v-cloak>
                                    <div class="list-mode-btn" @click="changeListStyle()">
                                        <i class="ri-list-check" v-if="!listStyle"></i>
                                        <i class="ri-grid-fill" v-else></i>
                                    </div>
                                </div>
                            </div>
                            <div class="play-list-wrap">
                                <div v-for="(item,i) in videos" class="chapter-item" v-cloak>
                                    <div class="chapter-title" v-if="item.chapter_title">{{item.chapter_title}}</div>
                                    <ul class="chapter-video-list" :class="listStyle ? \'number-list\' : \'\'">
                                        <li v-for="(video,index) in item.video_list" :class="{ \'active\': videoIndex == getEpisodeNumber(i, index) - 1 }">
                                            <a :href="video.link" @click.prevent="switchVideo(video,getEpisodeNumber(i, index) - 1)">
                                                <div class="video-thumb" v-if="!listStyle">
                                                    <div class="thumb">
                                                        <img :src="video.thumb" class="w-h" :alt="video.title">
                                                    </div>
                                                </div>
                                                <div class="video-title" v-if="!listStyle">
                                                    <div class="title text-ellipsis">
                                                        <div class="playing" v-if="videoIndex == getEpisodeNumber(i, index) - 1"><i></i><i></i><i></i><i></i></div>
                                                         第{{ getEpisodeNumber(i, index) }}集 - {{video.title}}
                                                    </div>
                                                    <div class="meta"></div>
                                                </div>
                                                <div v-else class="number">{{ getEpisodeNumber(i, index) }}<div class="playing" v-if="videoIndex == getEpisodeNumber(i, index) - 1"><i></i><i></i><i></i><i></i></div></div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>':'').'
            </div>
        </div>';
    }
    
    // 获取分享连接
    public static function get_share_links(){
        $metas = \Qk\Modules\Common\Seo::single_meta(0);
        
        $have_pic = !empty($metas['image']) ? true : false;

        return array(
            'title'=>$metas['title'],
            'weibo'=>esc_url('http://service.weibo.com/share/share.php?url='.$metas['url'].'&sharesource=weibo&title='.wptexturize(urlencode($metas['title'])).($have_pic ? '&pic='.$metas['image'] : '')),
            'qq'=>esc_url('http://connect.qq.com/widget/shareqq/index.html?url='.$metas['url'].'&sharesource=qzone&title='.wptexturize(urlencode($metas['title'])).($have_pic ? '&pics='.$metas['image'] : '').($metas['description'] ? '&summary='.wptexturize(urlencode($metas['description'])) : '')),
            'qzone'=>esc_url('https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url='.$metas['url'].'&sharesource=qzone&title='.wptexturize(urlencode($metas['title'])).($have_pic ? '&pics='.$metas['image'] : '').($metas['description'] ? '&summary='.wptexturize(urlencode($metas['description'])) : '')),
            'weixin'=>''
        );
        
    }
}
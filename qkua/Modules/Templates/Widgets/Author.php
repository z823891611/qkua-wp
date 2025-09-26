<?php namespace Qk\Modules\Templates\Widgets;

use Qk\Modules\Common\User;

class Author {

    //小工具slug
	protected $widget_slug = 'qk_widget_author';

    //短代码名
	protected static $shortcode = 'qk_widget_author';

    //默认设置
	protected static $defaults = array();

    
	public function __construct() {

    \CSF::createWidget( $this->widget_slug, array(
        'title'       => 'Qk-作者面板',
        'classname'   => $this->widget_slug,
        'description' => '“作者面板”小工具（只在内页生效）',
      ));
	}
	
	
	/**
     * 显示小工具
     *
     * @param [type] $args
     * @param [type] $instance
     *
     * @return void
     * @version 1.0.0
     * @since 2023
     */
	public static function widget( $args, $instance ) {
	    
	    //非文章页面不显示
	    if(!is_single()) return;
        $post_id = get_the_id();

		$user_id = get_post_field('post_author', $post_id );
		if(!$user_id) return;
		
		$author_data = User::get_author_info($user_id);
        
        $stats_count = User::get_user_stats_count($user_id);
        $followers_count = User::get_user_followers_stats_count($user_id); //获取关注数计数
        
        $html = '<div class="author-widget">
            <div class="author-widget-content">
                <div class="author-cover" style="background-image: url('.$author_data['cover'].');">
                    <div class="bg-cover"></div>
                </div>
                <div class="author-info">
                    <a href="'.$author_data['link'].'" class="user-link">
                        '.$author_data['avatar_html'].'
                        <div class="author-name">'.$author_data['name'].'</div>
                    </a>
                    <div class="author-desc">'.$author_data['desc'].'</div>
                    <div class="author-count qk-flex">
                        <div><p>文章</p><span>'.$stats_count['posts_count'].'</span></div>
                        <div><p>评论</p><span>'.$stats_count['comments_count'].'</span></div>
                        <div><p>关注</p><span>'.$followers_count['follow'].'</span></div>
                        <div><p>粉丝</p><span>'.$followers_count['fans'].'</span></div>
                    </div>
                </div>
            </div>
            <div class="author-widget-footer">
                <button class="follow qk-flex" @click="onFollow()" v-if="!is_follow"><i class="ri-heart-add-line"></i><span>关注</span></button>
                <button class="no-follow qk-flex" @click="onFollow()" v-else v-cloak><i class="ri-heart-fill"></i><span>已关注</span></button>
                <button class="letter qk-flex" @click="whisper()"><i class="ri-chat-3-line"></i>私信</button>
            </div>
        </div>';
        
      // 如果 $widget 是空的， 重建缓存
        if ( empty( $widget )) {
            $widget = '';
            
            $widget .= !$instance['mobile_show'] ? str_replace('class="','class="mobile-hidden ',$args['before_widget']) : $args['before_widget'];
            $widget .= '<div class="widget-box">';
            $widget .= !empty( $instance['title'] ) ? $args['before_title']. esc_attr( $instance['title'] ) .$args['after_title'] : '';
            $widget .= $html;
            $widget .= '</div>';
            $widget .= $args['after_widget'];
            
            
            // if(QK_OPEN_CACHE){
            // 	wp_cache_set( $args['cache_id'], $widget, 'widget', WEEK_IN_SECONDS );
            // }
            
        }
        
        echo $widget;
    }
	
}
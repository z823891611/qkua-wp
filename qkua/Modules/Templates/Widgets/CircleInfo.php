<?php namespace Qk\Modules\Templates\Widgets;
use Qk\Modules\Common\Circle;

class CircleInfo {

    //小工具slug
    protected $widget_slug = 'qk_widget_circleInfo';

    //短代码名
    protected static $shortcode = 'qk_widget_circleInfo';
    
    public function __construct() {

        \CSF::createWidget( $this->widget_slug, array(
            'title'       => 'Qk-圈子信息',
            'classname'   => $this->widget_slug,
            'description' => '“圈子信息”小工具（只在圈子页面生效）',
            'fields'      => array(
                array(
                    'id'      => 'mobile_show',
                    'type'    => 'select',
                    'title'   => '移动端是否可见',
                    'options' => array(
                        1     => '显示', 
                        0     => '隐藏',
                    ),
                    'default' => 0,
                ),
            )
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
        
        if(!is_tax('circle_cat')) return;
        
        $circle_id = get_queried_object_id();
        
        $circle = Circle::get_circle_data($circle_id);

        $html = '<div class="circle-info-widget">
            <div class="circle-info-inner">
                <div class="cover" style="background-image: url('.$circle['cover'].');">
                    <div class="bg-cover"></div>
                </div>
                <div class="circle-info">
                    <div class="circle-info-top">
                        <div class="circle-image">
                            <img src="'.$circle['icon'].'" width="46" height="46" class="circle-image-face w-h">
                        </div>
                        <div class="circle-title">
                            <h2 class="circle-name">'.$circle['name'].'</h2>
                            <div class="circle-info-tag">
                                <div class="tag-item official bg-text">
                                    <i class="ri-star-smile-fill"></i>
                                    <span>官方</span>
                                </div>
                                <a class="tag-item" href="'.$circle['admin']['link'].'">
                                    <img src="'.$circle['admin']['avatar'].'" width="22px" height="22px">
                                    <span>'.$circle['admin']['name'].'</span>
                                    <span> 创建</span>
                                </a>
                            </div>
                        </div>
                        
                    </div>
                    <div class="circle-info-bottom">
                        '.($circle['desc'] ? '<p class="circle-desc">'.$circle['desc'].'</p>':'').'
                        <div class="circle-statistics">
                            <div>
                                <span><i class="ri-group-line"></i></span>
                                <span>'.$circle['user_count'].'人</span>
                            </div>
                            <div>
                                <span><i class="ri-article-line"></i></span>
                                <span>'.$circle['post_count'].'篇</span>
                            </div>
                            <div>
                                <span><i class="ri-eye-2-line"></i></span>
                                <span>'.$circle['views'].'次</span>
                            </div>
                            <div>
                                <span><i class="ri-earth-line"></i></span>
                                <span>公开</span>
                            </div>
                        </div>
                        <!--<div class="circle-active">
                            <div class="text">活跃成员</div>
                            <div class="active-user">
                                <div class="user-avatar">
                                    <img src="https://www.qkua.com/wp-content/uploads/thumb/2023/10/fill_w192_h192_g0_mark_13be265126f956_1_avatar.png" alt="丸辣的头像" class="avatar-face w-h">
                                </div>
                                <div class="user-avatar">
                                    <img src="https://www.qkua.com/wp-content/uploads/thumb/2023/10/fill_w192_h192_g0_mark_13be265126f956_1_avatar.png" alt="丸辣的头像" class="avatar-face w-h">
                                </div>
                                <div class="user-avatar">
                                    <img src="https://www.qkua.com/wp-content/uploads/thumb/2023/10/fill_w192_h192_g0_mark_13be265126f956_1_avatar.png" alt="丸辣的头像" class="avatar-face w-h">
                                </div>
                            </div>
                        </div>-->
                    </div>
                </div>
            </div>
        </div>
        
        ';
        
       // 如果 $widget 是空的， 重建缓存
        if ( empty( $widget )) {
            $widget = '';
            
            $widget .= !$instance['mobile_show'] ? str_replace('class="','class="mobile-hidden ',$args['before_widget']) : $args['before_widget'];
            $widget .= '<div class="widget-box">';
            $widget .= $html;
            $widget .= '</div>';
            $widget .= $args['after_widget'];
            
            
            // if(QK_OPEN_CACHE){
            //     wp_cache_set( $args['cache_id'], $widget, 'widget', WEEK_IN_SECONDS );
            // }
            
        }
        
        echo $widget;
    }
}
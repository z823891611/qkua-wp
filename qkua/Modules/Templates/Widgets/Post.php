<?php namespace Qk\Modules\Templates\Widgets;

class Post {

    //小工具slug
    protected $widget_slug = 'qk_widget_post';

    //短代码名
    protected static $shortcode = 'qk_widget_post';
    
    public function __construct() {

        \CSF::createWidget( $this->widget_slug, array(
            'title'       => 'Qk-文章聚合排行',
            'classname'   => $this->widget_slug,
            'description' => '“文章聚合”小工具（只在内页生效）',
            'fields'      => array(
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'title'   => '标题',
                ),
                array(
                    'id'          => 'post_cat',
                    'type'        => 'select',
                    'title'       => '选择分类',
                    'chosen'     => true,
                    'multiple'   => true,
                    'placeholder' => '请选择调用分类',
                    'options'     => 'categories',
                    'query_args'  => array(
                        'taxonomy'  => array('video_cat','category'),
                    ),
                ),
                //排序方式
                array(
                    'id'         => 'post_order',
                    'type'       => 'select',
                    'title'      => '排序方式',
                    'options'    => array(
                        'new'      => '最新文章',
                        'modified' => '修改时间',
                        'rand'   => '随机文章',
                        'meta_value_num'    => '浏览最多文章',
                        'comment_count' => '评论最多文章'
                    ),
                    'default'     => 'new',
                ),
                array(
                    'id'    => 'count',
                    'type'  => 'spinner',
                    'title' => '显示数量',
                    'min'     => 0,
                    'max'     => 30,
                    'unit'    => '篇',
                    'default' => 6,
                ),
                array(
                    'id'          => 'list_style',
                    'type'        => 'radio',
                    'title'       => '列表样式',
                    'options'     => array(
                        1   => '无图', //文章
                        2   => '小图', //视频
                    ),
                    'default'     => 1,
                    'inline'      => true,
                ),
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
        
        if((int)$instance['count'] < 0 || (int)$instance['count'] > 30) return;
        
        $query_args = array(
            //'post_type'=>'post',
            'posts_per_page'=>$instance['count'] ? (int)$instance['count'] : 6,
            'orderby'=>$instance['post_order'],
            'post__not_in'=>get_option("sticky_posts"),
            'no_found_rows'=>true,
            'post_status'=>'publish'
        );
        
        if($query_args['orderby'] === 'meta_value_num'){
            $query_args['meta_key'] = 'views';
        }
        
        //多少天内
        if($instance[ 'days' ]){
            $query_args['date_query'] = array(
                array(
                    'after'     => wp_date('Y-m-d',wp_strtotime("-".$instance[ 'days' ]." days")),//7天的时间
                    'inclusive' => true,
                )
            );
        }
        
        //分类
        if(!empty($instance['post_cat']) && is_array($instance['post_cat'])){
            $tax_array = $instance['post_cat'];
            
            $query_args['tax_query'] = array(
                'relation' => 'OR',
            );
            
            foreach ($tax_array as $k => $v) {
                $term = get_term( $v );

                array_push($query_args['tax_query'], array(
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => $v,
                    'operator'         => 'IN',
                    'include_children' => false,
                ));
            }
        }
        
        $the_query = new \WP_Query( $query_args );

        if ( $the_query->have_posts() ) {
            
            $html = '<ul class="widget-post-list">';
            $i = 0;
            
            while ( $the_query->have_posts() ) {
                
                $the_query->the_post();
                
                $i++;
                $post_id = $the_query->post->ID;
                $link = get_permalink();
                $title = get_the_title();
                $view = (int)get_post_meta($post_id,'views',true);
                $view = qk_number_format($view);
                
                if(!$instance['list_style'] || $instance['list_style'] == 1) {
                    $html .= '
                        <li class="widget-post widget-post-none">
                            <div class="title"> 
                                <span class="post-index">'.$i.'</span>
                                <a class="link" href="'.$link.'">'.$title.'</a>
                            </div>
                            <span class="views">'.$view.'</span>
                        </li>
                    ';
                }elseif ($instance['list_style'] == 2) {
                    $thumb = qk_get_thumb(array(
                        'url'=>\Qk\Modules\Common\Post::get_post_thumb($post_id),
                        'width'=>200,
                        'height'=>100
                    ));
                    
                    $html .= '
                        <li class="widget-post widget-post-small">
                            <div class="widget-post-thumb">
                                <a ref="nofollow" class="link" href="'.$link.'">
                                    '.qk_get_img(array(
                                        'class'=>array('qk-radius','w-h'),
                                        'src'=>$thumb,
                                        'alt'=>$title
                                    )).'
                                </a>
                            </div>
                            <div class="widget-post-info">
                                <div class="title"> 
                                    <a class="text-ellipsis" href="'.$link.'"><h2>'.$title.'</h2></a>
                                </div>
                                <div class="meta">
                                    '.qk_time_ago(get_the_date('Y-m-d G:i:s')).'
                                    <span class="views">'.$view.' 浏览</span>
                                </div>
                            </div>
                        </li>
                    ';
                }
            }
            
            $html .= '</ul>';
        }else{
            $html = '暂无数据';
        }
        
        wp_reset_postdata();
        
       // 如果 $widget 是空的， 重建缓存
        if ( empty( $widget )) {
            $widget = '';
            
            $widget .= !$instance['mobile_show'] ? str_replace('class="','class="mobile-hidden ',$args['before_widget']) : $args['before_widget'];
            $widget .= '<div class="widget-box">';
            $widget .= !empty( $instance['title'] ) ? $args['before_title']. esc_attr( $instance['title'] ) .$args['after_title'] : '';
            $widget .= '<div class="post-widget"> '.$html.'</div>';
            $widget .= '</div>';
            $widget .= $args['after_widget'];
            
            
            // if(QK_OPEN_CACHE){
            //     wp_cache_set( $args['cache_id'], $widget, 'widget', WEEK_IN_SECONDS );
            // }
            
        }
        
        echo $widget;
    }
}
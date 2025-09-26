<?php namespace Qk\Modules\Templates\Widgets;

class Topic {

    //小工具slug
    protected $widget_slug = 'qk_widget_topic';

    //短代码名
    protected static $shortcode = 'qk_widget_topic';
    
    public function __construct() {

        \CSF::createWidget( $this->widget_slug, array(
            'title'       => 'Qk-热门话题',
            'classname'   => $this->widget_slug,
            'description' => '“热门话题”小工具',
            'fields'      => array(
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'title'   => '标题',
                ),
                //排序方式
                array(
                    'id'         => 'orderby',
                    'type'       => 'select',
                    'title'      => '排序方式',
                    'options'    => array(
                        'weight' => '热门权重',
                        'term_id'      => '最新',
                        'rand'   => '随机',
                        'meta_value_num'    => '浏览',
                        'count' => '参与度'
                    ),
                    'default'     => 'new',
                    'desc'       => '如果选择的是热门权重，需要在社区圈子开启权重计算'
                ),
                array(
                    'id'    => 'count',
                    'type'  => 'spinner',
                    'title' => '显示数量',
                    'min'     => 0,
                    'max'     => 30,
                    'unit'    => '个',
                    'default' => 6,
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
        
        /***
         * 'name'：按术语名称排序（默认值）
            'slug'：按术语别名（slug）排序
            'term_group'：按术语分组排序
            'term_id'：按术语ID排序
            'description'：按术语描述排序
            'count'：按术语关联的对象数量排序
         * */
        
        // 构建参数数组
        $query_args = array(
            'taxonomy' => 'topic', // 自定义分类法的名称
            'orderby' => $instance['orderby'], // 按照数值进行排序
            'order' => 'DESC', // 降序排列
            // 浏览量自定义字段的名称
            'number' => $instance['count'],
            'hide_empty' => false,
        );
        
        if($query_args['orderby'] === 'meta_value_num'){
            $query_args['meta_key'] = 'views';
        }
        
        if($query_args['orderby'] === 'term_id'){
            $query_args['order'] = 'DESC';
        }
        
        if($query_args['orderby'] === 'weight'){
            $query_args['orderby'] = 'meta_value_num';
            $query_args['meta_key'] = 'qk_hot_weight';
        }
        
        // 获取符合条件的分类
        $topics = get_terms($query_args);
        
        $html = '<ul class="widget-topic-list">';

        // 遍历分类并输出
        if (!empty($topics) && !is_wp_error($topics)) {
            foreach ($topics as $topic) {
                $weight = get_term_meta($topic->term_id, 'qk_hot_weight', true);
                
                // 获取分类的文章数量
                $post_count = $topic->count;
                
                //$img = qk_get_thumb(array('url'=>get_term_meta($term->term_id,'qk_tax_img',true),'width'=>150,'height'=>150)) ?? '';
                //$cover = qk_get_thumb(array('url'=>get_term_meta($term->term_id,'qk_tax_cover',true),'width'=>1200,'height'=>300)) ?? '';
        
                //echo '<a href="' . get_term_link($topic) . '">' . $topic->name . '</a> - 浏览量：' . $views . ' - 文章数量：' . $post_count . '<br>';
                $html .= '<li class="widget-topic">
                            <div class="hashtag-icon">
                                <i class="ri-hashtag"></i>
                            </div>
                            <a class="topic-info" href="' . get_term_link($topic) . '">
                                <h2 class="title">' . $topic->name . '</h2>
                                <div>
                                    <span>'.$weight.' 热度</span>
                                    <span>'.$post_count.' 动态</span>
                                    <!--<span>1 万圈友</span>-->
                                </div>
                            </a>
                        </li>';
            }
        }else{
            $html .= '暂无数据';
        }
        
        $html .= '</ul>';
        
       // 如果 $widget 是空的， 重建缓存
        if ( empty( $widget )) {
            $widget = '';
            
            $widget .= !$instance['mobile_show'] ? str_replace('class="','class="mobile-hidden ',$args['before_widget']) : $args['before_widget'];
            $widget .= '<div class="widget-box">';
            $widget .= !empty( $instance['title'] ) ? $args['before_title']. esc_attr( $instance['title'] ) .$args['after_title'] : '';
            $widget .= '<div class="topic-widget"> '.$html.'</div>';
            $widget .= '</div>';
            $widget .= $args['after_widget'];
            
            
            // if(QK_OPEN_CACHE){
            //     wp_cache_set( $args['cache_id'], $widget, 'widget', WEEK_IN_SECONDS );
            // }
            
        }
        
        echo $widget;
    }
}
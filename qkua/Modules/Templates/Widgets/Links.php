<?php namespace Qk\Modules\Templates\Widgets;

class Links {

    //小工具slug
	protected $widget_slug = 'qk_widget_links';

    //短代码名
	protected static $shortcode = 'qk_widget_links';

    //默认设置
	protected static $defaults = array();

    
	public function __construct() {

        \CSF::createWidget( $this->widget_slug, array(
            'title'       => 'Qk-连接组',
            'classname'   => $this->widget_slug,
            //'description' => '“文章聚合”小工具（只在内页生效）',
            'fields'      => array(
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'title'   => '标题',
                    'default' => '联系与合作',
                ),
                array(
                    'id'     => 'links_arg',
                    'type'   => 'group',
                    'title'  => '连接组',
                    'button_title' => '新增连接',
                    'fields' => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => '连接名称'
                        ),
                        array(
                            'id'    => 'link',
                            'type'  => 'text',
                            'title' => '连接地址'
                        ),
                    ),
                    'default'   => array(
                        array(
                            'name' => '',
                            'link' => '',
                        ),
                    )
                ),
                array(
                    'id'      => 'mobile_show',
                    'type'    => 'select',
                    'title'   => '移动端是否可见',
                    'options' => array(
                        1     => '显示', 
                        0     => '隐藏',
                    ),
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
	    
	    //print_r($instance);

        $html = '<div class="links-widget"><ul class="links-list">';
        
        if(!empty( $instance['links_arg'] )){
            foreach($instance['links_arg'] as $k => $v){
                $html .= '
                    <li class="list-item">
                        <a target="__blank" class="link-block" href="'.$v['link'].'">
                            '.$v['name'].'
                        </a>
                    </li>
                ';
            }
        }
        
        $html .= '</ul></div>';
        
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
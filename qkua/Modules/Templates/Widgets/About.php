<?php namespace Qk\Modules\Templates\Widgets;

class About {

    //小工具slug
	protected $widget_slug = 'qk_widget_about';

    //短代码名
	protected static $shortcode = 'qk_widget_about';

    //默认设置
	protected static $defaults = array();

    
	public function __construct() {

        \CSF::createWidget( $this->widget_slug, array(
            'title'       => 'Qk-关于我们',
            'classname'   => $this->widget_slug,
            //'description' => '“文章聚合”小工具（只在内页生效）',
            'fields'      => array(
                array(
                    'id'      => 'title',
                    'type'    => 'text',
                    'title'   => '标题',
                    'default' => '关于我们',
                ),
                array(
                    'id'    => 'about_logo',
                    'type'  => 'upload',
                    'title' => 'LOGO',
                    'preview' => true,
                    'library' => 'image',
                ),
                array(
                    'id'    => 'about_text_logo',
                    'type'  => 'text',
                    'title' => '文字LOGO',
                    'help' => '在不使用图片logo显示文字logo',
                ),
                array(
                    'id'      => 'about_desc',
                    'type'    => 'textarea',
                    'title'   => '一段描述自己',
                    'default' => '使用Qk主题作为强力驱动',
                ),
                array(
                    'id'      => 'about_link',
                    'type'    => 'text',
                    'title'   => '点击进入的页面',
                    'help'    => '直接复制“关于我们”页面的连接到此处',
                ),
                array(
                    'id'      => 'about_contact',
                    'type'    => 'accordion',
                    'title'   => '联系方式',
                    'accordions'      => array(
                        array(
                            'title'     => '联系方式',
                            'fields'    => array(
                                array(
                                    'id'      => 'weixin',
                                    'title'   => '微信二维码',
                                    'type'    => 'upload',
                                    'preview' => true,
                                    'library' => 'image',
                                    'default' => QK_THEME_URI.'/Assets/fontend/images/contact/weixin.svg',
                                ),
                                array(
                                    'id'      => 'qq',
                                    'type'    => 'text',
                                    'title'   => 'QQ',
                                    'default' => '946046483',
                                ),
                                array(
                                    'id'      => 'email',
                                    'type'    => 'text',
                                    'title'   => '邮箱',
                                    'default' => '946046483@qq.com',
                                ),
                                array(
                                    'id'      => 'weibo',
                                    'type'    => 'text',
                                    'title'   => '微博',
                                    'default' => 'https://weibo.com/',
                                ),
                            ),
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
     * @since 2018
     */
	public static function widget( $args, $instance ) {
	    
	    //print_r($instance);
	    
	    if(!empty( $instance['about_contact'] )){
	        
	        $contact = '<ul class="contact-list">';
	        
            foreach($instance['about_contact'] as $k => $v){
                
                if($v){
                    
                    $alt = '联系其他';
                    switch ($k){
                        case "qq":
                            $alt = '联系QQ';
                            $v = "http://wpa.qq.com/msgrd?v=3&uin=$v&site=qq&menu=yes";
                            break;
                        case "email":
                            $alt = '联系邮箱';
                            $v = "mailto:$v";
                            break;
                        case "weixin":
                            $alt = '联系微信';
                            break;
                    }
                    
                    $contact .= '
                        <li class="list-item">
                            <a target="__blank" class="link-block" href="'.$v.'">
                                <img src="'.QK_THEME_URI.'/Assets/fontend/images/contact/'.$k.'.svg" alt="'.$alt.'"/>
                            </a>
                        </li>
                    ';
                }
            }
            
            $contact .= '</ul>';
        }

        $html = '
            <div class="about-widget">
                <a class="about-logo" href="'.$instance['about_link'].'">'.( $instance['about_logo'] ? '<img src="'.$instance['about_logo'].'">' : '<h2>'.$instance['about_text_logo'].'</h2>').'</a>
                <p>'.$instance['about_desc'].'</p>
                '.$contact.'
            </div>
        ';
        
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
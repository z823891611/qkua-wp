<?php namespace Qk\Modules\Templates;
use Qk\Modules\Templates\Modules\Sliders;
/******存档模块*******/

class Circle{

    public function init(){ 
        //分类页顶部
        //add_action('qk_archive_circle_content_before',array($this,'swiper_carousel'),10);
        add_action('qk_archive_circle_top',array($this,'swiper_carousel'),10);
        
        add_action('qk_single_content_before',array($this,'play_single_top'),10);
        
        //话题
        add_action('qk_archive_topic_top',array($this,'archive_top'),10);
        
    }
    
    /**
     * 存档页面顶部
     *
     * @return string
     */
    public function swiper_carousel(){
        
        if(!is_post_type_archive('circle') && !apply_filters('qk_is_page', 'circle')) return;
        
        $data = qk_get_option('circle_home_slider');
        
        if(!isset($data['open']) || !$data['open']) return;
        
        $layout = qk_get_option('circle_home_layout');
        
        $post_data = Sliders::slider_list($data);
        if(count($post_data) < 3) return;
        
        $is_mobile = wp_is_mobile();
        
        $slider_height = $is_mobile && !empty($data['mobile_height']) ? $data['mobile_height'] : $data['height'];
        $slider_height = !empty($slider_height) ? $slider_height : 295;
        
        //$widget_width =  !empty($layout['sidebar_open']) ? $layout['sidebar_width'] : 0;
        
        $width = (int)$layout['wrapper_width'];// - (int)$widget_width;
        
        $html = '';

        foreach ($post_data as $key => $value) {
            $thumb = qk_get_thumb(array(
                'url' => $value['thumb'],
                'width'=> $width,
                'height'=> round(($slider_height * 0.7),6),
                'ratio'=>1.4
            ));
            
            $html .= '
                <div class="carousel-item" :class="getCarouselItemClasses('.$key.')">
                    <a href="'.$value['link'].'" target="_blank">
                        '.qk_get_img(array('src'=>$thumb,'class'=>array('qk-radius','w-h'),'alt'=>$value['title'])).'
                    </a>
                </div>
            ';
        }
        
        //幻灯的设置项
        $settings = array(
            'length'=> count($post_data),
            'autoPlay'=> (int)$data['speed'],
        );
        $settings = json_encode($settings,true);
        
        echo '<div class="swiper-carousel wrapper">
            <div class="stack-carousel" style="padding-top:'.round($slider_height / $width * 100,6).'%">
                <div class="carousel-container" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave" ref="carouselContainer" carousel-data=\''.$settings.'\' v-cloak>
                    '.$html.'
                </div>
            </div>
            <ul class="carousel-dots" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
                <li class="carousel-dot" v-for="item in '.count($post_data).'" :class="{active:currentIndex== (item - 1)}" @click="click((item - 1))"></li>
            </ul>
        </div>';
    }
    
    public function play_single_top() {
        $post_id = get_the_id();
        //$post_type = get_post_type($post_id);
        if(empty(get_post_meta($post_id,'qk_circle_video',true))) return;
        
        echo '<div class="qk-play-single-header">
            <div id="qk-player-wrap" class="qk-player-wrap" ref="player">
                <div class="qk-play-left">
                    <div class="qk-player-box">
                        <div id="moeplayer"></div>
                        <div class="player-popup" v-if="!videoList.length">
                            <div class="mask-body"></div>
                        </div>
                    </div>
                </div>
           </div>
        </div>';
    }
    
     /**
     * 存档页面顶部
     *
     * @return string
     */
    public function archive_top($topic){
        $weight = get_term_meta($topic['id'], 'qk_hot_weight', true);
    ?>
        <div class="qk-tax-header">
            <div class="wrapper">
                <div class="tax-info mg-b qk-radius box">
                    <div class="topic-cover" style="background-image: url(<?php echo $topic['cover'];?>);"></div>
                    <div class="topic-info">
                        <div class="topic-info-left">
                            <div class="topic-user">
                                <a href="https://www.qkua.com/users/1" class="no-hover">
                                    <?php echo $topic['admin']['avatar_html'];?>
                                    <span class="user-name"><?php echo $topic['admin']['name'];?></span>
                                </a>
                                <span>发起话题</span>
                            </div>
                            <h1>
                                <i class="ri-hashtag"></i><?php echo $topic['name'];?><i class="ri-hashtag"></i>
                                <?php if($weight){ ?>
                                    <span class="hot">
                                        <i class="ri-fire-fill"></i>
                                        <span><?php echo $weight;?></span>
                                    </span>
                                <?php } ?>
                            </h1>
                            <?php if($topic['desc']){ ?>
                                <p><?php echo $topic['desc'];?></p>
                            <?php } ?>
                        </div>
                        <div class="topic-info-right">
                            <?php echo qk_get_img(array('src'=>$circle['icon'],'class'=>array('circle-image-face','w-h'),'alt'=>$circle['name']));?>
                        </div>
                    </div>
                    <div class="topic-info-bottom">
                        <div class="topic-statistics">
                            <span>帖数 <?php echo $topic['post_count'];?></span>
                            <span>浏览 <?php echo $topic['views'];?></span>
                        </div>
                        <div class="topic-button">
                            <button class="button follow bg-text">关注</button>
                            <a class="" href="<?php echo qk_get_custom_page_url('moment').'?topics='.$topic['name'] ?>"><button class="button follow">参与话题</button></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
}
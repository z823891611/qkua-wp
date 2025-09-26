<?php namespace Qk\Modules\Common;
use Qk\Modules\Templates\Single;
use Qk\Modules\Common\User;

/**
 * qkua player播放器
 *
 * @version 1.0.0
 * @since 2023
 */
class Player {
    
	public function init() {
        add_action('template_redirect',array(__CLASS__,'shortcode_head'));
    }
    
    /**
     * 如果文章中存在视频段代码，则加载JS
     *
     * @return void
     * @version 1.0.0
     * @since 2023
     */
    public static function shortcode_head(){
        //if(is_singular()){
            global $post; //|| apply_filters('qk_is_page', 'circle') || is_tax('circle_cat') || is_tax('topic')
            if (in_array($post->post_type,array('episode')) || Single::get_single_post_settings($post->ID,'single_post_style') === 'post-style-video' || get_post_meta($post->ID,'qk_circle_video',true)) {

                wp_enqueue_script('player-hls',QK_THEME_URI.'/Assets/fontend/moeplayer/hls.min.js', array(), QK_VERSION, true );
                wp_enqueue_script('player-lottie',QK_THEME_URI.'/Assets/fontend/moeplayer/lottie.min.js', array(), QK_VERSION, true );
                wp_enqueue_script('player-CommentCoreLibrary',QK_THEME_URI.'/Assets/fontend/moeplayer/CommentCoreLibrary.min.js', array(), QK_VERSION, true );
                
                wp_enqueue_script('player',QK_THEME_URI.'/Assets/fontend/moeplayer/MoePlayer.js', array(), QK_VERSION, true );
                wp_enqueue_style('player-css',QK_THEME_URI.'/Assets/fontend/moeplayer/index.css', array(), QK_VERSION, 'all' );
            }
        //}
    }
    
    //获取视频数量计数
    public static function get_video_count($post_id) {
        $parent_id = get_post_field('post_parent', $post_id);
        
        if($parent_id) {
            $post_id = $parent_id;
        }
        
        if(!$parent_id &&  get_post_type($post_id) == 'post') {
            $video_list = get_post_meta($post_id, 'qk_single_post_video_group', true );
            if(empty($video_list)) return 0;
            
            return count($video_list);
            
        }else if(!$parent_id && get_post_type($post_id) == 'episode') {
            return 1;
        }else{
            $video_meta = get_post_meta((int)$post_id, 'single_video_metabox', true );
        
            if(empty($video_meta)) return 0;
            
            $video_list = !empty($video_meta['group']) ? $video_meta['group'] : 0;
            
            return count($video_list);
        }
    }

    public static function get_video_list($post_id) {
        
        $parent_id = get_post_field('post_parent', $post_id);
        $user_id = get_current_user_id();
        
        $current_post_id = $post_id;
        
        if($parent_id) {
            $post_id = $parent_id;
        }
        
        if(!$parent_id &&  get_post_type($post_id) == 'post') {
            $video_list = get_post_meta($post_id, 'qk_single_post_video_group', true );
            if(empty($video_list)) return array();
            
        }else if(!$parent_id &&  get_post_type($post_id) == 'episode') {
            $episode_meta = get_post_meta($post_id, 'single_episode_metabox', true );
            $episodes = !empty($episode_meta['video'])? $episode_meta['video'] :array();
            $thumb_url = Post::get_post_thumb($item['id'],true);
            $thumb = qk_get_thumb(array('url'=>$thumb,'width'=>106,'height'=>60,'ratio'=>1));
            
            $video_list = array(
                array(
                    'chapter_title' => '',
                    'chapter_desc' => '',
                    'type' => 'episode',
                    'id' => $post_id,
                    'title' => get_the_title($post_id),
                    'pic'  => $thumb_url,
                    'thumb' => $thumb,
                    'url' => $episodes['url'],
                    'preview_url' => $episodes['preview_url'],
                )
            );
            
        }else{
            $video_meta = get_post_meta((int)$post_id, 'single_video_metabox', true );
        
            if(empty($video_meta)) return array();
            
            $video_list = !empty($video_meta['group']) ? $video_meta['group'] : array();
        }
        
        $allowList = array();
        $index = 0;
        $free_count = 0;
        $free_video = false;
        
        foreach ($video_list as $key => &$value) {
            
            if($value['type'] == 'chapter') continue;
            $can = apply_filters('qk_check_user_can_video_allow', (!empty($value['id'])  ? $value['id'] : $post_id),$user_id,$index,$current_post_id);
            
            $value['url'] = $can['allow'] ? $value['url'] : ''; 

            $allowList[] = $can['allow'];
            
            if(isset($can['free_count'])) {
                $free_count = $can['free_count'];
                $free_video = true;
            }
            
            $index++;
        }
        
        $list = self::groupByChapter($video_list);
        
        $can['allowList'] = $allowList;
        $can['free_count'] = $free_count;
        $can['free_video'] = $free_video;
        
        if($parent_id) {
            
            $post_author = get_post_field('post_author',$post_id);
            $thumb_url = Post::get_post_thumb($parent_id);
            $thumb = '';
            if($thumb_url){
                $thumb = qk_get_thumb(array('url'=>$thumb_url,'width'=>180,'height'=>250,'ratio'=>2));
            }
            
            $post = array(
                'id'=>$parent_id,
                'title'=> get_the_title($parent_id),
                'link'=> get_permalink($parent_id),
                'thumb'=> $thumb,
                'desc' => qk_get_desc($parent_id,150),
                'user' => User::get_user_public_data($post_author),
            );
        }
        
        $data = array(
            'id' => $post_id,
            'current_user' => $can,
            'list' => $list,
        );
        
        if(isset($post)) {
            $data['post'] = $post;
        }
        
        return $data;
    }
    
    /**
     * 将原始数组按照章节进行分组，并返回新的数组
     * 
     * @param array $array 原始数组
     * @return array 新的数组
     */
    public static function groupByChapter($array) {
        $newArray = array(); // 初始化新的数组
        $chapterIndex = -1; // 初始化章节索引
    
        // 遍历原始数组
        foreach ($array as $key => &$item) {
            if ($item['type'] == 'chapter') { // 如果元素的类型为章节，则创建新的章节，并将其添加到新的数组中
                $chapterIndex++;
                $newArray[$chapterIndex] = array(
                    //'type' => 'chapter',
                    'chapter_title' => $item['chapter_title'],
                    'chapter_desc' => $item['chapter_desc'],
                    'video_list' => array()
                );
            } else {
                if($chapterIndex == -1) { // 如果第一个元素的类型不是章节，则创建一个空章节并将其添加到新的数组中
                    $chapterIndex++;
                    $newArray[$chapterIndex] = array(
                        //'type' => 'chapter',
                        'chapter_title' => '',
                        'chapter_desc' => '',
                        'video_list' => array()
                    );
                
                }
                if(!empty($item['id'])) {
                    $item['link'] = get_permalink($item['id']);
                    
                    // $thumb_id = get_post_thumbnail_id($item['id']);
                    // $thumb_url = wp_get_attachment_image_src($thumb_id,'full');
                    
                    // $item['pic'] = $thumb_url[0]?:'';
                }
                
                
                
                unset($item['type']);
                unset($item['chapter_title']);
                unset($item['chapter_desc']);
                
                $item['pic'] = $item['thumb'];
                
                if(!isset($item['thumb']) || empty($item['thumb'])) {
                    $item['thumb'] = Post::get_post_thumb($item['id']);
                }
                
                $item['thumb'] = qk_get_thumb(array('url'=>$item['thumb'],'width'=>106,'height'=>60,'ratio'=>1));
                
                // 将元素添加到当前章节的视频列表中
                $newArray[$chapterIndex]['video_list'][] = $item;
            }
        }
    
        return $newArray; // 返回新的数组
    }
    
}
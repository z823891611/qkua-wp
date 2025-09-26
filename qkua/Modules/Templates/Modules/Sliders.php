<?php namespace Qk\Modules\Templates\Modules;
//幻灯模块

use Qk\Modules\Templates\Modules\Posts;

class Sliders{

    public function init($data,$i){
        return self::slider($data,$i);
    }

    /**
     * 幻灯模块
     *
     * @param array $data 设置项参数
     *
     * @return string 幻灯的html
     * 
     * @version 1.0.0
     * @since 2023
     */
    public static function slider($data,$i){
        
        // $data = array(
        //     'grid-column' => 1, //列数
        //     'grid-row' => 1, //行数
        //     'grid-column-start' => 1, 
        //     'grid-column-end' => 1 + 1, //需加1
        //     'grid-row-start' => 1, 
        //     'grid-row-end' => 1 + 1, //需加1
        // );
        
        $slider_type = $data['slider_type'];
        
        $post_data = self::$slider_type($data);
        
        if(!$post_data) return;
        
        // print_r($post_data);
        //大幻灯占位个数
        $slider_num =  ($data['slider_grid_column_end'] - $data['slider_grid_column_start']) * ($data['slider_grid_row_end'] - $data['slider_grid_row_start']);
        //print_r($slider_num);
        //小item文章个数
        $item_num = round(($data['slider_grid_column'] * $data['slider_grid_row']) - $slider_num,6);
        //print_r($item_num);
        //获取小幻灯文章
        $last_arr = array_slice($post_data,-$item_num,$item_num);
        
        $css = '<style>
            .grid-container.slider_'.$i.' {
                grid-gap: '.$data['slider_row_gap'].'px '.$data['slider_column_gap'].'px;
                grid-template-columns: repeat('.$data['slider_grid_column'].', 1fr)!important;
            }
            .slider_'.$i.' .slider-container {
                grid-column: '.$data['slider_grid_column_start'].'/'.$data['slider_grid_column_end'].';
                grid-row: '.$data['slider_grid_row_start'].'/'.($data['slider_grid_row_end']).';
            }
        </style>';
        
        //每个卡片宽度
        $widget_width = round(($data['width'] - $data['slider_column_gap'] * ($data['slider_grid_column'] - 1)) / $data['slider_grid_column'],6);
        
        
        //大幻灯宽度
        $slider_width = ($widget_width * ($data['slider_grid_column_end'] - $data['slider_grid_column_start'])) + ($data['slider_column_gap'] * ($data['slider_grid_column_end'] - $data['slider_grid_column_start'] - 1));
        //获取后几篇文章
        //$height = round(($data['slider_height'] - $data['slider_row_gap'])/$data['slider_grid_row'],6);
        //小幻灯高度
        $height = round(($data['slider_height'] - $data['slider_row_gap']  * ($data['slider_grid_row'] - 1) )/$data['slider_grid_row'],6);
        
        //大幻灯高度
        
        $slider_height = ($height * ($data['slider_grid_row_end'] - $data['slider_grid_row_start'])) + ($data['slider_row_gap'] * ($data['slider_grid_row_end'] - $data['slider_grid_row_start'] - 1));

        $item = '';
        
        foreach ($last_arr as $k => $v) {
            $item .= '<div class="slider-item-card">
                    <div class="item-card-image" style="padding-top:'.round($height/$widget_width*100,6).'%">
                        <a href="'.$v['link'].'" target="_blank" class="slider-item-thumb">
                            '.qk_get_img(array('src'=>$v['thumb'],'class'=>array('slider-cover','w-h','qk-radius'),'alt'=>$v['title'])).'
                            <div class="slider-info-box">
                                <h2 class="text-ellipsis">'.$v['title'].'</h2>
                                <div class="slider-user">
                                    '.qk_get_avatar(array('src'=>$v['user_avatar'])).'
                                    <span class="user-name">'.$v['user_name'].'</span>
                                    <span>'.$v['date'].'</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>';
        }
        
        //获取前几篇文章
        $_i = 0;
        $count_post = count($post_data);
        $html_img = '';
        $html_href = '';
        
        foreach($post_data as $k => $v){
            $_i++;
            if($_i > ($count_post - $item_num) ) break;
            
            $html_img .= '<a href="'.$v['link'].'" class="carousel__slide">
                    '.qk_get_img(array('src'=>$v['thumb'],'class'=>array('slider-img','w-h','qk-radius'),'alt'=>$v['title']),false).'
                </a>';
                
            if($data['slider_show_title']) {
            $html_href .= '<a href="'.$v['link'].'" rel="noopener" target="_blank">
                    <span>'.$v['title'].'</span>
                </a>';
            }
        }
        
        
        return $css.
        ($data['title']?'<div class="modules-top">
            <h2 class="module-title">'.$data['title'].'</h2>
        </div>':'').'
        <div class="qk-grid grid-container slider_'.$i.'">
            <div class="slider-container" style="padding-top:'.round($slider_height / $slider_width*100,6).'%">
                <div class="slider-body">
                    <div class="carousel-area">
                        '.$html_img.'
                    </div>
                    <div class="carousel-footer">
                        '.($data['slider_show_mask']?'<div class="carousel-mask"></div>':'').'
                        <div class="carousel-tool">
                            '.$html_href.'
                        </div>
                    </div>
                </div>
            </div>
            '.$item.'
        </div>';
    }
    
    //文章调用
    public static function slider_posts($data){
        $_data = array();
        
        $_data['post_order'] = $data['slider_post_order'];
        $_data['post_cat'] = $data['slider_post_cat'];
        $_data['post_count'] = $data['slider_post_count'];

        $post_data = Posts::get_post_data($_data);
        
        return isset($post_data['data']) && $post_data['data'] ? $post_data['data'] : array();
    }
    
    //自定义内容调用
    public static function slider_list($data){
        $list_data = self::list_array($data['slider_list']);
        
        $arg = array();

        foreach ($list_data as $k => $v) {

            $arr = array(
                'link'=>'',
                'title'=>'',
                'thumb'=>'',
                'desc'=>'',
                'user_avatar'=>'',
                'date'=>'',
                'user_name'=>'',
                'id'=>'',
                // 'cats'=>array(
                //     'title'=>'',
                //     'link'=>'',
                //     'color'=>''
                // )
            );

            if(is_numeric($v['id'])){
                
                //作者信息
                $user_id = get_post_field('post_author',$v['id']);
                $user_info = get_userdata($user_id);
                if(!isset($user_info->display_name)) continue;
                
                //文章信息
                $arr['link'] = get_permalink($v['id']);
                $arr['title'] = get_the_title($v['id']);
                
                $arr['des'] = qk_get_desc($v['id'],200);
                $arr['date'] = get_the_date( 'Y-n-j H:s:i',$v['id'] );

                $arr['user_name'] = $user_info->display_name;
                $arr['user_avatar'] = get_avatar_url($user_id,array('size'=>50));

                $arr['thumb'] = $v['thumb'] === '0' ? \Qk\Modules\Common\Post::get_post_thumb($v['id']) : $v['thumb'];

                // $cats = get_the_category($v['id']);
                // if(!empty($cats)){
                //     $arr['cat'] = array(
                //         'title'=>$cats[0]->name,
                //         'link'=>get_category_link( $cats[0]->term_id ),
                //     );
                // }

            }else{
                $arr['thumb'] = $v['thumb'];
                $arr['link'] = $v['id'];
                $arr['title'] = $v['title'];
            }

            $arr['id']=$v['id'];

            $arg[] = $arr;

        }

        return $arg;
    }
    
    public static function list_array($str){
        $str = trim($str, " \t\n\r\0\x0B\xC2\xA0");
        $str = explode(PHP_EOL, $str );
        $arg = array();

        foreach ($str as $k => $v) {
            $_v = explode('|', $v);
            $arg[] = array(
                'id'=>isset($_v[0]) ? trim($_v[0]) : '',
                'thumb'=>isset($_v[1]) ? trim($_v[1]) : '',
                'title'=>isset($_v[2]) ? trim($_v[2]) : '',
            );
        }

        return $arg;
    }
}
<?php
use Qk\Modules\Templates\Archive;
/**
 * 分类存档页面
 */

$paged = get_query_var('paged') ? get_query_var('paged') : 1;

//获取分类数据
$term = get_queried_object();

if(!isset($term->term_id)){
    wp_safe_redirect(home_url().'/404');
    exit;
}

$default_settings = array(
    'post_type'=>'post-2',
    '_post_type'=>'video',
    'post_order'=>'new',
    'post_row_count'=>1,
    'post_count'=>6,
    'post_thumb_ratio'=>'1/0.618',
    'waterfall_show' => false, //开启瀑布流
    // 'post_open_type'=>1,
    // 'post_meta'=>array('user','date','views','like','cats','des'),
);

$settings = get_term_meta($term->term_id,'qk_tax_group',true);
$settings = is_array($settings) ? $settings : array();
//如果设置项为空，则使用默认设置
$settings = array_merge($default_settings, $settings);

$settings['post_paged'] = isset($settings['post_paged']) ? $settings['post_paged'] : $paged;

//当前分类id加入设置项中
$settings['video_cat'] = array($term->term_id);

//标签筛选
if(isset($_GET['post_tag']) && !empty($_GET['post_tag'])){
    $settings['post_tag'] = array($_GET['post_tag']);
}

if(!empty($_GET)){
    $tags = array();
    foreach ($_GET as $k_k => $v_k) {
        if(strpos($k_k,'tags') !== false){
            $tags[] = $v_k;
        }
    }

    if(!empty($tags)){
        $settings['tags'] = $tags;
    }
}

//自定义字段筛选
$filters = Qk\Modules\Templates\Archive::get_fliter_data($term->term_id);

if(isset($filters['fliter_group']) && $filters['fliter_group']) {
    $metas = array();
    
    $fliter_group = array_filter($filters['fliter_group'], function($group) {
        return $group['type'] == 'metas';
    });
    
    foreach($fliter_group as $group) {
        $meta_key = $group['meta_key'];
        if(isset($_GET[$meta_key]) && !empty($_GET[$meta_key])){
            $metas[$meta_key] = $_GET[$meta_key];
        }
    }
    
    $settings['metas'] = $metas;
}

//排序筛选
if(isset($_GET['post_order']) && !empty($_GET['post_order'])){
    $settings['post_order'] = $_GET['post_order'];
}

//隐藏侧边栏
$settings['show_sidebar'] = get_term_meta($term->term_id,'qk_show_sidebar',true);

//计算宽度
$settings['width'] = qk_get_page_width($settings['show_sidebar']);

//获取文章列表数据
$modules =  new Qk\Modules\Templates\Modules\Posts;
$data = $modules->init($settings,1,true);

//加载方式
$pagenav_type = get_term_meta($term->term_id,'qk_tax_pagination_type',true);
$pagenav_type = $pagenav_type ? $pagenav_type : 'page';

//设置项传入JS
wp_localize_script( 'vue', 'qk_cat',array(
    'param'=>$settings
));
?>

<?php 
    if($settings['waterfall_show'] == true){ 
        echo '<style>
            .'.$settings['post_type'].' ul.qk-waterfall > li{
                width:'.((floor((1/$settings['post_row_count'])*10000)/10000)*100).'%;
            }
        </style>';
    }else {
        echo '<style>
            .'.$settings['post_type'].' ul.qk-grid{
                grid-template-columns: repeat('.$settings['post_row_count'].', minmax(0, 1fr));
            }
        </style>';
    } 
?>
<div class="archive-row">
    <div class="<?php echo $settings['post_type'] ?>" id="post-list">
        <?php if($data['data']) { ?>
        <ul class="qk-grid <?php echo $settings['waterfall_show'] == true ? ' qk-waterfall' : '' ?>"><?php echo $data['data'];?></ul>
        <?php }else{ echo qk_get_empty('暂无内容','empty.svg'); }?>
    </div>
</div>

<?php 

    echo qk_ajax_pagenav( array( 'paged' => $paged, 'pages' => $data['pages'] ), 'post', $pagenav_type );


<?php
use Qk\Modules\Templates\Archive;
/**
 * 分类存档页面
 */

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$key = get_search_query();

$settings = array(
    'post_type'=>'post-1',
    'post_order'=>'new',
    'post_row_count'=>4,
    'post_count'=>16,
    'post_thumb_ratio'=>'1/0.618',
    'waterfall_show' => false, //开启瀑布流
    // 'post_open_type'=>1,
    'search'=>$key,
    'post_meta'=>array('user','like','cats','desc'),
);

$settings['post_paged'] = isset($settings['post_paged']) ? $settings['post_paged'] : $paged;

//当前分类id加入设置项中
//$settings['post_cat'] = array($term->term_id);

//排序筛选
if(isset($_GET['post_order']) && !empty($_GET['post_order'])){
    $settings['post_order'] = $_GET['post_order'];
}

//隐藏侧边栏
$settings['show_sidebar'] = false;

//计算宽度
$settings['width'] = qk_get_page_width($settings['show_sidebar']);

//获取文章列表数据
$modules =  new Qk\Modules\Templates\Modules\Posts;
$data = $modules->init($settings,1,true);

//加载方式
$pagenav_type = 'page';

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


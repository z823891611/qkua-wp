<?php
use B2\Modules\Templates\Archive;
/**
 * 分类存档页面
 */

$user_id =  get_query_var('author');

$paged = get_query_var('paged') ? get_query_var('paged') : 1;

$settings = array(
    'post_type'=>'post-1',
    'post_order'=>'new',
    'post_row_count'=>5,
    'post_count'=>10,
    'post_thumb_ratio'=>'1.7/1',
    'post_open_type'=>1,
    'post_meta'=>array('date','views','like','cats','edit'),
    'author__in' => array($user_id),
    'post_paged'=>$paged,
    'post_status'=>array('publish','pending','draft'),
    'post_ignore_sticky_posts'=>1,
    //'waterfall_show' => true
);

    
//获取文章列表数据
$modules =  new Qk\Modules\Templates\Modules\Posts;
$data = $modules->init($settings,1,true);

//$size = $modules::get_thumb_size($settings,$settings['post_row_count']);

//print_r($data);
//设置项传入JS
wp_localize_script( 'vue', 'qk_cat',array(
    'param'=>$settings
));

if($data['data']){
?>
<div id="post-list">
    <ul class="qk-grid"><?php echo $data['data']; ?></ul>
</div>

<?php
}else{
    echo qk_get_empty('暂无内容','empty.svg');
}
echo qk_ajax_pagenav( array( 'paged' => $paged, 'pages' => $data['pages'] ), 'post', 'page' );
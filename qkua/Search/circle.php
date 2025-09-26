<?php
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$key = get_search_query();

$args = array(
    'size'=>5,
    'paged'=>$paged,
    'search'=>$key,
);

$data = \Qk\Modules\Common\Circle::get_moment_list($args);

?>

<div class="circle-content-wrap">
    <div class="circle-moment-list" ref="momentList">
        <?php 
            if(!empty($data['data'])) {
                echo implode("", $data['data']);
            }else{
                echo qk_get_empty('暂无内容','empty.svg'); 
            }
        ?>
    </div>
    <?php
        echo qk_ajax_pagenav( array( 'paged' => $paged, 'pages' => $data['pages'] ), 'circle', 'page'); 
    ?>
</div>

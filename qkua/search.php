<?php
/**
 * 搜索页面
 */
get_header();

$show_sidebar = false; //后续搜索模块设置

$search_type = qk_get_search_type();

$key = get_search_query();

$type = isset($_GET['type']) && $_GET['type'] ? $_GET['type'] : 'post';


global $wp;
$url = QK_HOME_URI.'/'.$wp->request;
$request = http_build_query($_REQUEST);
$url = $request ? $url . '?' . $request : $url;

?>

<?php do_action('qk_search_top'); ?>

<div class="qk-single-content wrapper <?php echo $tax; echo $show_sidebar ? ' single-sidebar-show' : ' single-sidebar-hidden'; ?> ">
    <div id="primary-home" class="content-area">
        <div class="tabs">
            <ul class="tabs-nav box qk-radius" style=" padding: 16px; height: auto; ">
                <?php foreach ($search_type as $k => $v) { ?>
                
                    <li class="<?php echo ($type == $k ? 'active' : '');?>"><a href="<?php echo add_query_arg('type',$k,$url)?>"><?php echo $v;?></a></li>
                    
                <?php } ?>
                <div class="active-bar"></div>
            </ul>
        </div>
        <!--<div class="archive-row">-->
        <!--    <div class="search-content-wrap">-->
        <!--        <div class="search-item">-->
        <!--            <div class="section-title">相关用户</div>-->
        <!--            <div class="user-list">-->
        <!--                <div class="list-item"></div>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--    </div>-->
        <!--</div>-->
        <?php get_template_part( 'Search/'.$type ); ?>
    </div>

    <?php 
        if($show_sidebar) get_sidebar(); 
    ?>

</div>
<?php
get_footer();
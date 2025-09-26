<?php
use Qk\Modules\Common\Circle;


$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$circle = array(
    'id' => 0,
    'showEditor'=>true,
);
$circle_id = 0;
if($post_id){
    $circle_id = Circle::get_circle_id_by_post_id($post_id);
    $user_id = get_current_user_id();
    $manage_role = apply_filters('qk_check_manage_moment_role',array('user_id'=>$user_id,'post_id'=>$post_id,'circle_id'=>(int)$circle_id));
    if(empty($manage_role['can_edit'])){
        wp_safe_redirect(home_url().'/moment');
        exit;
    }
    
    $circle_info = Circle::get_circle_data($circle_id);
    
    if(!isset($circle_info['error'])) {
        $circle = wp_parse_args($circle_info, $circle);
        $circle['can_edit'] = $manage_role['can_edit'];
    }
}

get_header();

wp_localize_script( 'qk-circle', 'qk_circle',$circle);

?>
<div class="qk-single-content wrapper" style="--wrapper-width: 930px;">
    <div id="moment" class="content-area moment-page">
        <main id="main" class="site-main">
            <?php get_template_part( 'TempParts/Circle/circle-editor');?>
        </main>
    </div>
</div>
<style>
.moment .sidebar-menu {
    display: none;
}

.moment .header #top-menu {
    display: none;
}

.moment .header .center-entry {
    display: none !important;
}

.moment .header .right-entry > * {
    display: none !important;
}

.moment .header .right-entry > .menu-user-box {
    display: block !important;
}

.moment .header .right-entry > .menu-publish-box {
    display: block !important;
}

.moment .header .header-top {
    box-shadow: none;
}

.moment .footer .box:before {
    display:none;
}

.moment .footer {
    display: none;
}

@media screen and (min-width: 768px) {
    .moment .sidebar-menu {
        display: none;
    }
}

.circle-editor-simple {
    display: none;
}

.circle-editor .editor-content .editor-textarea {
    min-height: 244px;
}

</style>
<?php
get_footer();
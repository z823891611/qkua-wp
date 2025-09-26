<?php
use Qk\Modules\Common\Circle;

$tax = get_queried_object();

$left_sidebar = Circle::get_show_left_sidebar($tax);

if(empty($left_sidebar)) return;

$circle_home = get_post_type_archive_link('circle');
//global $wp;
  // 获取当前页面的URL
//$current_url = home_url(add_query_arg(array(), $wp->request));

// $sidebar = qk_get_option('circle_left_sidebar');
// $sidebar = !empty($sidebar) ? $sidebar : array();

// $sidebar_list = array();

// foreach ($sidebar as $value){
//     if($value['type'] == 'circle'){
//         if(!empty($value['circle_cat'] && is_array($value['circle_cat']))){
//             foreach ($value['circle_cat'] as $v) {
//                 $circle = Circle::get_circle_data($v);
//                 $sidebar_list[] = array(
//                     'name' => $circle['name'],
//                     'img_icon' => $circle['icon'],
//                     'link' => $circle['link'],
//                     'current' => $circle['id'] == $circle_id ? 'active': '',
//                 );
//             }
        
//         }
//     }else {
//         $value['current'] = !empty($value['link']) && substr($current_url, -strlen($value['link'])) === $value['link'] ? 'active': '';
//         $sidebar_list[] = $value;
//     }
// }

$tabs = Circle::get_tabbar($tax);
$default_index = Circle::get_default_tabbar_index($tax);

$args = isset($tabs[$default_index]) ? $tabs[$default_index] : array();

?>
<aside id="secondary-left" class="widget-area widget-area-left">
    <section class="widget-channel-menu">
        <div class="circle-channel-menu-widget circle-sidebar-menu" ref="channelMenu" data-index="<?php echo $default_index;?>">
             <div class="circle-channel-inner box qk-radius mg-b">
                <ul class="menu-list">
                    <?php foreach ($tabs as $key => $value) :?>
                        <li class="menu-item" :class="{active:index == <?php echo $key ?> }" @click="changeTab(<?php echo $key ?>,'<?php echo $value['tab_type'] ?>')">
                            <span class="menu-icon">
                                <i class="<?php echo $value['icon'] ?>"></i>
                            </span>
                            <span><?php echo $value['name'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="circle-create box" @click="createCircle">
                <i class="ri-add-fill"></i>
            </div>
        </div>
    </section>
</aside>
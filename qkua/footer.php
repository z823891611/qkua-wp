<?php
    $footer_link_cat = qk_get_option('footer_link_cat');
    $footer_beian = qk_get_option('footer_beian');
    $gongan = qk_get_option('footer_gongan');
	$gongan_code = (int) filter_var($gongan, FILTER_SANITIZE_NUMBER_INT);
    $footer_image = qk_get_option('footer_image');
?>
        </div>
        <?php do_action('qk_content_after'); ?>
    </div>
    <footer class="footer">
        <div class="box">
            <div class="wrapper">
                <?php if(is_active_sidebar( 'footer-sidebar' )){ ?>
                <div class="footer-widget">
                    <?php dynamic_sidebar( 'footer-sidebar' ); ?>
                    <?php if(!empty($footer_image)){ ?>
                        <div class="footer-widget-item">
                            <div class="w-h" style=" display: flex; align-items: center; justify-content: center; ">
                                <?php foreach ($footer_image as $value) { ?>
                                    <div style=" margin-left: 16px; ">
                                        <div style=" width: 90px; ">
                                            <img src="<?php echo $value['image'] ?>" alt="<?php echo $value['text'] ?>">
                                        </div>
                                        <p style=" font-size: 13px; color: var(--color-text-regular); text-align: center; margin-top: 4px; "><?php echo $value['text'] ?></p>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <?php } ?>
                <?php do_action('qk_footer_top'); ?>
                <div class="footer-nav">
                    <?php if(!empty($footer_link_cat)){
                        foreach ($footer_link_cat as $value) {
                            $term = get_term_by('id', $value, 'link_category');
                            $bookmarks = get_bookmarks(array(
                                'category' => $term->term_id,
                                'orderby' => 'link_rating',
                                'order' => 'DESC'
                            ));
                            
                            if ( !empty($bookmarks)) {
                                echo '<ul class="footer-links">
                                        <li>'.$term->name.'：</li>';
                                foreach ($bookmarks as $bookmark) {
                                    echo '<li><a target="_blank" href="' . $bookmark->link_url . '">' . $bookmark->link_name . '</a></li>';
                                }
                                echo '</ul>';
                            }
                             
                            unset($bookmarks);
                        }
                    }?>
                </div>
                <div class="footer-bottom">
                     <div class="footer-bottom-left"><?php echo 'Copyright &copy; '.date('Y').'<a href="'.QK_HOME_URI.'" rel="home">&nbsp;'.get_bloginfo('name').'</a>'; ?>
                        <?php if($footer_beian){
                            echo '&nbsp;·&nbsp;<a rel="nofollow" target="__blank" href="https://beian.miit.gov.cn">'.$footer_beian.'</a>';
                        }?>
                        
                        <?php if($gongan){
                            echo '&nbsp;·&nbsp;<a rel="nofollow" target="__blank" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode='.$gongan_code.'">公安：'.$gongan.'</a>';
                        }?>
                    </div>
                    <div class="footer-bottom-rigth">
                        <?php
							echo sprintf('查询 %s 次，',get_num_queries());
							echo sprintf('耗时 %s 秒',timer_stop(0,4));
						?>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <?php do_action('qk_footer_after'); ?>
</div>

<?php wp_footer(); ?>

<?php
// if (current_user_can('administrator')){
//     // 执行数据库查询
//     global $wpdb;
//     // 初始化查询语句分组数组和颜色数组
//     $query_groups = array();
//     $colors = array('#FFC107', '#FF5722', '#9C27B0', '#2196F3', '#4CAF50');
//     $color_index = 0;

//     // 将相同的查询语句分为同一组
//     foreach ($wpdb->queries as $query) {
//         $query_sql = $query[0];
//         if (!isset($query_groups[$query_sql])) {
//             $query_groups[$query_sql] = array();
//         }
//         $query_groups[$query_sql][] = $query;
//     }
//     // 按数组长度排序查询语句组
//     uasort($query_groups, function($a, $b) {
//         return count($b) - count($a);
//     });
    
//     echo '<div class="wrapper">';
//     // 输出每个查询语句组及其执行时间
//     foreach ($query_groups as $query_sql => $queries) {
//         $group_color = $colors[$color_index % count($colors)];
//         echo '<div style="background-color: #f5f5f5; padding: 10px; margin-bottom: 10px;">';
//         echo '<strong>重复查询次数：</strong>' . count($queries) . '<br><br>';
//         foreach ($queries as $query) {
//             echo '<strong>查询语句：</strong>' . $query_sql . '<br><br>';
//             $query_time = $query[1];
//             echo '<strong>查询执行时间：</strong>' . $query_time . ' 秒<br><br>';
//             echo '<strong>位置：</strong>' . $query[2] . ' <br><br>';
//         }
//         echo '</div>';
//         $color_index++;
//     }
//     echo '</div>';
// }
?>

</body>
</html>
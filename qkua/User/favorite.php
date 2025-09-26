<?php
$user_id = get_query_var('author');

$post_types = qk_get_post_types();

?>
<div id="favorite-page" class="favorite-page w-h" ref="favoritePage">
    <div id="tabs" class="tabs">
        <ul class="tabs-nav box">
            <?php foreach ($post_types as $key => $value): ?>
                <li <?php if($key == 'post'){echo 'class="active"';}?>><?php echo $value; ?></li>
            <?php endforeach; ?>
            <div class="active-bar"></div>
        </ul>
        <div class="tabs-content">
            <div class="post-list post-2" v-show="data">
                <ul class="qk-grid"></ul>
            </div>
            <div class="loading empty qk-radius box" v-if="!data && loading && !isDataEmpty"></div>
            <template v-if="!data && isDataEmpty">
                <?php echo qk_get_empty('暂无内容','empty.svg'); ?>
            </template>
        </div>
    </div>
    <?php echo qk_ajax_pagenav( array( 'paged' => 1, 'pages' => 1), 'posts', 'page','change'); ?>
</div>
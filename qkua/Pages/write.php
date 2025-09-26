<?php
/**
 * 写文章、投稿页面
 */

//是否关闭投稿
$allow = qk_get_option('write_allow');
if(!$allow){
    wp_safe_redirect(home_url().'/404');
    exit;
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : '';
$user_id = get_current_user_id();

if($post_id){
    if((get_post_field( 'post_author', $post_id ) != $user_id || get_post_type($post_id) != 'post') && !user_can($user_id, 'administrator' ) && !user_can( $user_id, 'editor' )){
        wp_safe_redirect(home_url().'/write');
        exit;
    }
}

get_header();

//允许投稿分类
$cats = qk_get_option('write_cats');
$cats = $cats ? $cats : array();

//获取所有分类
$cats_arr = get_categories(array(
    'orderby' => 'name',
    'order'   => 'ASC',
    'include'    => $cats,
    'hide_empty'   => false,
));

$write_cats = [];
if(!empty($cats_arr)){
    foreach ($cats_arr as $k => $v) {
        $write_cats[] = array(
            'id'=>$v->term_id,
            'name'=>$v->name
        );
    }
}

//获取所有标签
$tags = get_tags(array('orderby' => 'count','order'=>'desc','hide_empty' => false, 'number'=>20,'public'=> true));
$write_tags = array();

if($tags){
    foreach ($tags as $k => $v) {
        $write_tags[] = array(
            'id' => $v->term_id,
            'name' => esc_attr($v->name),
        );
    }
}

/****编辑文章****/
$edit_cats = array();
$edit_role = array(
    'key'=>'none',
    'num'=>'',
    'roles'=>array()
);
$edit_thumb = '';
$edit_tags = array();
$content = '';
$title = '';

if($post_id){

    //分类
    $categorys = get_the_category($post_id);//$post->ID
    foreach($categorys as $cat){
        $edit_cats[] = $cat->term_id;
    }
    
    //隐藏内容权限
    $roles = get_post_meta($post_id,'qk_post_roles',true);
    $roles = $roles ? $roles : array();
    $role  = get_post_meta($post_id,'qk_post_content_hide_role',true);
    $role  = $role ? $role : 'none';
    
    $num = '';
    if(in_array($role,array('money','credit'))) {
        $num = get_post_meta($post_id,'qk_post_price',true);
    }
    
    //权限
    $edit_role = array(
        'key' => $role,
        'num' => $num,
        'roles'=>$roles
    );
    
    //特色图
    $edit_thumb = wp_get_attachment_url(get_post_thumbnail_id($post_id));
    
    //标签
    $tags = get_the_tags($post_id);
    if($tags){
        foreach($tags as $tag) {
            $edit_tags[] = $tag->name;
        }
    }

    $excerpt = get_post_field('post_excerpt', $post_id);
    $content = '';
    
    $content = preg_replace( '/<!-- \/?wp:(.*?) -->/', '', get_post_field('post_content', $post_id) );
    $content = wpautop($content);
    
    $title = get_post_field('post_title', $post_id);
}

wp_localize_script( 'qk-write', 'qk_write_data', array(
    'cats' => $write_cats,
    'tags' => $write_tags,
    'edit_cats' => $edit_cats,
    'edit_role' => $edit_role,
    'edit_thumb' => $edit_thumb,
    'edit_tags' => $edit_tags,
    //'edit_content' => $content,
    'edit_title' => $title,
    'post_id' => $post_id,
));
?>
<div class="qk-single-content wrapper" style="--wrapper-width: 930px;">
    <div id="write" class="content-area write-page" style="width: 100%; ">
        <main id="main" class="site-main box qk-radius">
            <div id="write-head">
                <div class="write-title">
                    <textarea id="write-textarea" rows="1" placeholder="请在这里输入标题" class="write-textarea" style="overflow-x: hidden; overflow-wrap: break-word; height: 58px;" v-model="postData.writeTitle"></textarea>
                </div>
            </div>
            <div id="write-editor-box" style=" transition: .3s; ">
                <?php 
                    $options = qk_get_option();
                    $defaults = array(
                        'media_buttons'  => false,
                        'default_editor' => 'tinymce',
                        'quicktags'      => false,
            			'editor_height'       => (wp_is_mobile() ? 460 : 486),
            			'editor_class' => 'article-content',
            			'tinymce'             => array(
            				'resize'                  => false,
            				'wp_autoresize_on'        => 1,
            				'body_class' => 'article-content '.qk_getcookie('theme_mode'),
            				'content_style' => 'body{--radius:'.$options['radius'].'px;--color-primary:'.$options['theme_color'].';--theme-color:'.$options['theme_color'].';--bg-color:'.$options['bg_color'].';--gap:'.$options['qk_gap'].'px;--bg-text-color:'.qk_hex2rgb($options['theme_color']).';--sidebar_width:300px;position: inherit!important;background-color: var(--bg-main-color);margin:0;};',
            			)
                    );
                    wp_editor($content, 'post_content', $defaults);
                    
                 ?>
            </div>
            <div id="post-setting" class="post-setting">
                <div class="setting-title">发布设置</div>
                <div class="mg-b">
                    <div class="widget-title">增加文章封面</div>
                    <div class="write-thumb qk-radius">
                        <label class="w-h" @click.stop="writeHead.showImgUploadBox" v-if="!writeHead.postData.thumb" v-cloak>
                            <i class="el-icon-camera-solid"></i>
                            <div><?php echo __('添加特色图','qk'); ?></div>
                        </label>
                        <img class="w-h" :src="writeHead.postData.thumb" v-if="writeHead.postData.thumb" @click.stop="writeHead.showImgUploadBox"/>
                        <div class="WriteCoverV2-buttonGroup"  v-if="writeHead.postData.thumb">
                            <span @click.stop="writeHead.showImgUploadBox">更换</span>
                            <span @click.stop="writeHead.postData.thumb = ''">删除</span>
                        </div>
                    </div>
                </div>
                <div class="mg-b">
                    <div class="widget-title">文章分类</div>
                    <div class="write-select-box mg-b">
                        <p>请选择文章分类</p>
                        <el-select v-model="cats" multiple placeholder="请选择分类">
                            <el-option v-for="item in catsOptions" :key="item.id" :label="item.name" :value="item.id"> </el-option>
                        </el-select>
                    </div>
                    <div class="widget-title">文章标签</div>
                    <div class="write-select-box">
                        <p>标签能让更多小伙伴看到你的作品</p>
                        <el-select v-model="tags" allow-create filterable multiple placeholder="请输入或选择标签">
                            <el-option v-for="item in tagsOptions" :key="item.id" :label="item.name" :value="item.name"> </el-option>
                        </el-select>
                    </div>
                </div>
                <div class="mg-b">
                    <div class="widget-title">内容权限</div>
                    <div class="write-select-box">
                        <p>如果您在文章中插入了隐藏内容，需要在此处设置查看权限，方可正常隐藏。</p>
                        <el-select v-model="role.key" placeholder="请选择">
                            <el-option v-for="item in roleOptions" :key="item.value" :label="item.label" :value="item.value"> </el-option>
                        </el-select>
                    </div>
                    <div class="write-role-settings mg-t" v-if="role.key == 'credit' || role.key == 'money'" v-cloak>
                        <span>需要支付的{{role.key == 'credit' ? '积分' : '费用'}}：</span>
                        <el-input-number v-model="role.num" size="small" controls-position="right" :min="1"></el-input-number>
                    </div>
                </div>
            </div>
        </main>
        <div class="write-footer">
            <div class="wrapper">
                <div class="write-footer-info">
                    <div class="left-info">
                        <a href="#">回到顶部</a>
                        <!--<span class="tw-ml-4">共 0 字</span>-->
                    </div>
                    <div class="button-submit">
                        <button class="draft" onclick="writeSetting.submit('draft')"><?php echo __('保存草稿','b2'); ?></button>
                        <button class="publish" onclick="writeSetting.submit('publish')"><?php echo __('提交发布','b2'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
get_footer();
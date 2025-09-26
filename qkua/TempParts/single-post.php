<?php
/*****post文章*******/
$style = Qk\Modules\Templates\Single::get_single_post_settings(get_the_id(),'single_post_style');
$style = $style ? $style : 'post-style-1';

get_template_part( 'TempParts/Single/content',$style);

// $views = (int)get_post_meta(get_the_id(),'views',true);
// update_post_meta(get_the_id(),'views',$views+1);
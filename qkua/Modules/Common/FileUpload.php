<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\Module;

use Qk\Modules\Settings\Module as SettingsModule;
use Grafika\Gd\Editor;
use Grafika\Color;

class FileUpload{
    
    public static $editor;
    public static $upload_dir;
    public static $allow_webp;
    
    public function init(){
        //add_filter('sanitize_file_name', array($this,'rename_filename'),10);

        // //本地裁剪
        self::$upload_dir = wp_upload_dir();
        
        //图片处理库Grafika https://segmentfault.com/a/1190000007411281
        self::$editor = new Editor();
        
        self::$allow_webp = 0;
        
    }
    
    /**
     * 图片裁剪，并储存到本地
     *
     * @param array $arg 
     *
     * @return string 图片URL
     */
    public static function thumb($arg){
        //url:图片路径,
        //type:编辑形式,
        //fill:固定长宽剧中裁剪，
        //fit:等比缩放，
        //exact:固定尺寸（可能造成变形），
        //exactW:等宽缩放（宽度固定，高度自动），
        //exactH:等高缩放（高度固定，宽度自动）
        //smart:智能裁剪，
        //gif 是否移除gif动画效果
        $r = apply_filters('qk_thumb_arg',wp_parse_args($arg,array(
            'url'=>'',
            'type'=>'fill',
            'width'=>'500',
            'height'=>'500',
            'gif'=>0,
            'webp'=>false,
            'ratio'=>1.2, 
            'custom'=>false 
        )));
        
        if($r['custom']){
            return apply_filters('qk_thumb_custom',$r['url'],$r);
        }

        if($r['height'] === '100%'){
            $r['type'] = 'exactW';
            unset($r['height']);
        }

        if($r['width'] === '100%'){
            $r['type'] = 'exactH';
            unset($r['width']);
        }

        if(true){
            if(isset($r['width'])){
                $r['width'] = ceil($r['width']*$r['ratio']);
            }
            if(isset($r['height'])){
                $r['height'] = ceil($r['height']*$r['ratio']);
            }
        }
        
        //检查图片是为空
        if(empty($r['url'])){
            if(!isset($r['default'])) {
                return apply_filters('qk_get_default_img',qk_get_default_img(),$r);
            }
            
            return $r['url'];
        }
        
        //如果不是本地文件，直接返回
        if(strpos($r['url'],QK_HOME_URI) === false){

            //如果使用的是相对地址
            if(strpos($r['url'],'//') === false){
                $r['url'] = self::$upload_dir['baseurl'].'/'.$r['url'];
            }

            return apply_filters('qk_thumb_no_local',$r['url'],$r);
        }
        
        //是否允许自动裁剪缩略图
        if(!qk_get_option('media_image_crop')) {
            return $r['url'];
        }

        //检查是否为裁剪过的图片
        if(strpos($r['url'],'_mark_') !== false){
            return $r['url'];
        }

        if(strpos($r['url'],'.gif') !== false){
            return $r['url'];
        }

        //如果不裁剪，返回原图
        if($r['type'] == 'default') return $r['url'];

        //获取原始图片的物理地址
        $rel_file_path = str_replace(self::$upload_dir['baseurl'],'',$r['url']);
        $rel_file_path = str_replace(array('/','\\'),QK_DS,$rel_file_path);

        $basedir = str_replace(array('/','\\'),QK_DS,self::$upload_dir['basedir']);

		$rel_file_path = $basedir.$rel_file_path;
		
        if(!is_file($rel_file_path)){
            return $r['url'];
        }

        list($width, $height, $type, $attr) = getimagesize($rel_file_path);

        if((isset($r['width']) && $width < $r['width']) || (isset($r['height']) && $height < $r['height'])) return $r['url'];
        
        $basename = basename($rel_file_path);

        $rel_file = str_replace($basedir.QK_DS,'',$rel_file_path);

        $r['height'] = isset($r['height']) ? $r['height'] : null;

		$file_path = str_replace($basename,'',$rel_file);
		
        $thumb_dir = $basedir.QK_DS.'thumb'.QK_DS.$file_path.$r['type'].'_w'.$r['width'].'_h'.$r['height'].'_g'.$r['gif'].'_mark_'.$basename;

        //如果存在直接返回
        if(is_file($thumb_dir)){
            $basedir = str_replace(array('/','\\'),'/',$basedir);
            $thumb_dir = str_replace(array('/','\\'),'/',$thumb_dir);
            return apply_filters('qk_get_thumb',str_replace($basedir,self::$upload_dir['baseurl'],$thumb_dir));
        }
        
        try {
            self::$editor->open($image , $rel_file_path);
            
            switch ($r['type']) {
                case 'fit':
                    self::$editor->resizeFit($image , $r['width'] , $r['height']);
                    break;
                case 'exact':
                    self::$editor->resizeExact($image , $r['width'] , $r['height']);
                    break;
                case 'exactW':
                    self::$editor->resizeExactWidth($image , $r['width']);
                    break;
                case 'exactH':
                    self::$editor->resizeExactHeight($image , $r['height']);
                    break;
                case 'smart':
                    self::$editor->crop( $image, $r['width'], $r['height'], 'smart' );
                    break;
                default:
                    self::$editor->resizeFill($image , $r['width'],$r['height']);
                    break;
            }

            if($r['gif']){
                self::$editor->flatten( $image );
            }

            if(self::$editor->save($image , $thumb_dir,null,85,true)){
                
                if(self::$allow_webp){
                    $thumb = str_replace(substr(strrchr($thumb_dir, '.'), 1),'webp',$thumb_dir);
                    self::$editor->save($image , $thumb,null,85,true);
                }
                

                $basedir = str_replace(array('/','\\'),'/',$basedir);
                $thumb_dir = str_replace(array('/','\\'),'/',$thumb_dir);
                return apply_filters('qk_get_thumb',str_replace($basedir,self::$upload_dir['baseurl'],$thumb_dir));
            }

            return apply_filters('qk_thumb_default_image',qk_get_default_img(),$r);
        } catch (\Throwable $th) {
            return $r['url'];
        }

        return $r['url'];
    }
    
    /**
     * 模块文件上传
     *
     * @param object $request restapi object
     *
     * @return void
     * @author 青青草原上
     * @version 1.0.0
     * @since 2023
     */
    public static function module_file_upload($request){
        
        $user_id = get_current_user_id();
        //模块类型
        $module_type_list = apply_filters('qk_module_type',array('home','header','footer','post','single','widget'));
        
        
        if(!user_can( $user_id, 'manage_options' )) return array('error'=>__('只能管理员操作','qk'));
        
        if(!isset($request['type'])) return array('error'=>__('请设置一个type','qk'));

        if(!in_array($request['type'],$module_type_list)) return array('error'=>__('不支持这个type','qk'));

        //文件体积检查close
        if(!isset($_FILES['file']['size'])){
            return array('error'=>sprintf(__('文件损坏，请重新选择（%s）','qk'),$_FILES['file']['name']));
        }
        
        //文件格式检查
        if(!strpos($_FILES['file']['name'],'.zip')){
            return array('error'=>'请上传zip格式的安装包');
        }
        
        //安装路径
        $path = QK_MODULES_URI.$request['type'] . '/';
        
        //判断文件是否存在，不存在创建一个
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        //先清空目录
        self::del_directory_file($path);
        
        
        //把上传的文件移动到新位置。 如果成功该函数返回 TRUE，如果失败则返回 FALSE。
        if (move_uploaded_file($_FILES['file']['tmp_name'], $path.$_FILES['file']['name'])) {
            
            $file = $path.$_FILES['file']['name'];

            $zip = new \ZipArchive();
            $openRes = $zip->open($file);
            
            if ($openRes === TRUE) {
                //解压文件目录
                $zip->extractTo($path);
                $zip->close();
                unlink($file); //删除本地安装包
            }
            
            //文件注释信息
            $module_info = Module::module_exist_info($request['type']);
            
            if(isset($module_info['error'])){
                //先清空目录
                self::del_directory_file($path);
                return $module_info;
            }
            
            //如果没有设置模块名称则删除文件
            if ($module_info['ModuleName'] == '模块名称') {
                
                 //先清空目录
                self::del_directory_file($path);
                return array('error'=>'请上传zip格式的安装包');
            }
            
            //启用插件
            SettingsModule::close($request['type']);
                
            return $module_info['ModuleName'].'模块上传成功!';
        }else{
            return array('error'=>'安装包上传失败！');
        }
        
    }
    
    //清空目录https://www.php.cn/php-ask-462993.html
    public static function del_directory_file($path) {
        if (is_dir($path)) {
            //扫描一个目录内的所有目录和文件并返回数组
            $dirs = scandir($path);
            foreach ($dirs as $dir) {
                //排除目录中的当前目录(.)和上一级目录(..)
                if ($dir != '.' && $dir != '..') {
                    //如果是目录则递归子目录，继续操作
                    $sonDir = $path.'/'.$dir;
                    if (is_dir($sonDir)) {
                        //递归删除
                        self::del_directory_file($sonDir);
                        //目录内的子目录和文件删除后删除空目录
                        @rmdir($sonDir);
                    } else {
                        //如果是文件直接删除
                        @unlink($sonDir);
                    }
                }
            }
            //@rmdir($path);
        }
    }
    
    /**
     * 上传文件重命名
     *
     * @param string $filename 文件名
     *
     * @return string 文件名
     * @author 青青草原上
     * @version 1.0.0
     * @since 2023
     */
    public static function rename_filename($filename,$type,$post_id){
        $info = pathinfo($filename);
        $ext = empty($info['extension']) ? '' : '.' . $info['extension'];
        $name = basename($filename, $ext);
        $current_user_id = get_current_user_id();
        return $current_user_id.str_shuffle(uniqid()).'_'.$post_id.'_'.$type. $ext;
    }
    
    /**
     * 图片与视频与文件上传
     *
     * @param object $request restapi object
     *
     * @return void
     * @author 青青草原上
     * @version 1.0.0
     * @since 2023
     */
    public static function file_upload($request){
        
        if(!qk_get_option('media_upload_allow')) {
            return array('error'=>'上传功能已关闭，请联系管理员');
        }

        $user_id = get_current_user_id();
  
        if(!$user_id){
            return array('error'=>'请先登录');
        }

        if(!$request['post_id'] || !is_numeric($request['post_id'])){
            return array('error'=>'缺少文章ID');
        }

        if(!isset($request['type'])) return array('error'=>'请设置一个type');

        if(!in_array($request['type'],apply_filters('qk_file_type',array('comment','post','avatar','cover','circle','qrcode','verify')))) return array('error'=>'不支持这个type');

        //文件体积检查
        if(!isset($_FILES['file']['size'])){
            return array('error'=>sprintf('文件损坏，请重新选择（%s）',$_FILES['file']['name']));
        }

        $size = 0;
        $mime = '';
        
        $upload_size = qk_get_option('media_upload_size');
        $upload_size = is_array($upload_size) ? $upload_size : array(); 
        
        if(strpos($_FILES['file']['type'],'image') !== false){
            $mime = 'image';
            $size = !empty($upload_size['image']) ? $upload_size['image'] : 3;
            $text = '图片';
        }elseif(strpos($_FILES['file']['type'],'video') !== false){
            $mime = 'video';
            $size = !empty($upload_size['video']) ? $upload_size['video'] : 50;
            $text = '视频';
        }else{
            $mime = 'file';
            $size = !empty($upload_size['file']) ? $upload_size['file'] : 30;
            $text = '文件';
        }

        //检查上传权限
        $role = apply_filters('qk_check_user_can_media_upload',$user_id,$mime);
        
        //如果用户上传的是头像怎不 检查
        if(!$role && !in_array($request['type'],array('verify','avatar'))) return array('error'=>sprintf('您无权上传%s',$text));

        if($_FILES['file']['size'] > $size*1048576){
            return array('error'=>sprintf('%s必须小于%sM，请重新选择',$text,$size));
        }
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        
        if(!isset($request['file_name'])){
            $_FILES['file']['name'] = self::rename_filename($_FILES['file']['name'],$request['type'],$request['post_id']);
        }else{
            $_FILES['file']['name'] = $request['file_name'];
        }
        
        $attachment_id = media_handle_upload( 'file',$request['post_id'] );

        if ( is_wp_error( $attachment_id ) ) {
            return array('error'=>sprintf('上传失败(%s)：',$_FILES['file']['name']).$attachment_id->get_error_message());
        }else{

            if(isset($request['set_poster']) && get_post_field('post_author',absint($request['set_poster'])) == $user_id){
                set_post_thumbnail(absint($request['set_poster']),$attachment_id);
            }
            
            return array('id'=> $attachment_id,'url'=>wp_get_attachment_url($attachment_id));
        }

        // require( ABSPATH . 'wp-admin/includes/file.php' );

        // if(!isset($request['file_name'])){
        //     $_FILES['file']['name'] = self::rename_filename($_FILES['file']['name'],$request['type'],$request['post_id']);
        // }else{
        //     $_FILES['file']['name'] = $request['file_name'];
        // }
        
        // $file_return = wp_handle_upload( $_FILES['file'], array('test_form' => false,'action' => 'plupload_image_upload'));

        // if( isset( $file_return['error'] ) || isset( $file_return['upload_error_handler'] ) ) {

        //     return array('error'=>$file_return['error']);
        // } else {
            
        //     $attachment_id = wp_insert_attachment( array(
        //         'post_mime_type' => $file_return['type'],
        //         'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file_return['file'] ) ),
        //         'post_content' => '',
        //         'post_status' => 'inherit',
        //         'guid' => $file_return['url']
        //     ), $file_return['file'],$request['post_id']);

        //     if(!is_wp_error($attachment_id)) {
        //         require(ABSPATH . 'wp-admin/includes/image.php');
        //         require( ABSPATH . 'wp-admin/includes/media.php' );
        //         $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_return['file'] );

        //         wp_update_attachment_metadata( $attachment_id, $attachment_data );

        //         if(isset($request['set_poster']) && get_post_field('post_author',$request['set_poster']) == $user_id){
        //             set_post_thumbnail(($request['set_poster']),$attachment_id);
        //         }
        //     }
            
        //     return array('id'=>$attachment_id,'url'=>wp_get_attachment_url($attachment_id));
        // }
    
        // return array('error'=>sprintf('上传失败(%s)',$_FILES['file']['name']));
    }
}
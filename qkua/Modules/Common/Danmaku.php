<?php namespace Qk\Modules\Common;


class Danmaku{

    // public $v;
    // public static $upload_dir;

    public function init(){
        
    }
    
    //发送弹幕
    public static function send_danmaku($data){

        if(!$data || !is_array($data))return array('error'=>__('参数错误'));
        
        extract($data);
        
        $current_user_id = get_current_user_id();
        //if(!$current_user_id) return array('error'=>__('请先登录'));
        
        $text = str_replace(array('{{','}}'),'',$text);

        $text = sanitize_textarea_field($text);

        if($text == ''){
            return array('error'=>__('消息不可为空'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_danmaku';
        $res = $wpdb->insert($table_name, array(
            'cid' => sanitize_textarea_field(esc_sql($cid)),
            'post_id' => (int)$post_id,
            'user_id' => (int)$user_id,
            'date'  => current_time('mysql'),
            'mode'  => (int)$mode,
            'size'  => sanitize_textarea_field(esc_sql($size)),
            'stime' => (int)$stime,
            'color' => $color,
            'text' => $text,
            'date' => current_time('mysql')
        ));
        
        if($res){
            return true;
        }
        
        return false;
    }
    
    //获取弹幕
    public static function get_danmaku($cid){
        if(empty($cid)) return array('error'=>__('参数错误'));
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_danmaku';
        
        $res = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE `cid` = %s ORDER BY `date` DESC",$cid),
            ARRAY_A
        );
        
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE `cid` = %s",$cid));
        
        return array(
            'cid' => $cid,
            'danmaku' => $res,
            'count' => $count
        );
        
    }
    
}
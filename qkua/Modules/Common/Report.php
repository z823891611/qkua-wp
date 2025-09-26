<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\User;
/**
 * 投诉与举报
 * 
 * */

class Report {
    public function init(){

    }
    
    public static function report($data){

        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>'请先登录');
        
        $reported_id = !empty($data['reported_id']) ? (int)$data['reported_id'] : 0;
        
        $title = get_the_title($post_id);
        
        if(!get_post_status( $reported_id )) return array('error'=>'举报的文章不存在');

        $data['content'] = str_replace(array('{{','}}'),'',$data['content']);

        $content = sanitize_textarea_field($data['content']);
        
        if($data['type'] === '') return array('error'=>'请选择举报原因');
        
        $types = self::get_report_types();
        
        if(!isset($types[$data['type']])) return array('error'=>'投诉类型错误');

        // if(!is_email($data['email'])){
        //     return array('error'=>'请填写正确的邮箱地址');
        // }

        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_report';
        
        $data = array(
            'user_id' => $user_id,
            'reported_id' => $reported_id,
            'reported_type' => 'post',
            'content' => $content,
            'type' => $types[$data['type']],
            'date' => current_time('mysql'),
        );
    
        $format = array(
            '%d', // user_id
            '%d', // reported_id
            '%s', // reported_type
            '%s', // content
            '%s', // type
            '%s', // date
        );
        
        if($wpdb->insert($table_name, $data, $format)){

            do_action('qk_report_insert_data',$data);
            
            return $wpdb->insert_id;
        }

        return false;
    }
    
    public static function get_report_types() {
        
        $types = qk_get_option('report_types');
        $types = !empty($types) ? array_column($types, 'type') : array();
        
        return $types;
    }
    
}
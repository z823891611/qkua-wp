<?php namespace Qk\Modules\Common;

class CircleRelate{
    public static function update_data($new_data){
        
        $format = array(
            'id'=>'%d',
            'user_id'=>'%d',
            'circle_id'=>'%d',
            'circle_role'=>'%s',
            'join_date'=>'%s',
            'end_date'=>'%s',
            'circle_key'=>'%s',
            'circle_value'=>'%s'
        );
        //error_log(var_export($new_data, true), 3, QK_THEME_DIR . '/error.log');
        $format_new_data = array();
        foreach ($new_data as $k => $v) {
            $format_new_data[] = $format[$k];
        }
        
        // 根据数据是否存在执行插入或更新操作
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';
        $exists = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND circle_id = %d", $new_data['user_id'], $new_data['circle_id']));
        
        if(empty($exists)){
            // 数据不存在，执行插入操作
            if($wpdb->insert($table_name, $new_data,$format_new_data)){
                return true;
            }
        }else{
            if($result = $wpdb->update(
                $table_name,
                $new_data,
                array(
                    'user_id' => $new_data['user_id'],
                    'circle_id' => $new_data['circle_id']
                ),$format_new_data
            )){
                return true;
            }
        }
        
        return false;
    }
    
    public static function get_data($args){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';

        $where = '';

        if(isset($args['id']) && $args['id'] !== ''){
            $where .= $wpdb->prepare(' AND `id`=%d',$args['id']);
        }

        if(isset($args['user_id']) && $args['user_id'] !== ''){
            $where .= $wpdb->prepare(' AND `user_id`=%d',$args['user_id']);
        }

        if(isset($args['circle_id']) && $args['circle_id'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_id`=%d',$args['circle_id']);
        }

        if(isset($args['circle_role']) && $args['circle_role'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_role`=%s',$args['circle_role']);
        }

        if(isset($args['circle_key']) && $args['circle_key'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_key`=%s',$args['circle_key']);
        }

        if(isset($args['circle_value']) && $args['circle_value'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_value`=%s',$args['circle_value']);
        }
        
        if(isset($args['count']) && (int)$args['count'] < 50){
            $where .= ' ORDER BY id DESC LIMIT '.(int)$args['count'];
        }else{
            $where .= ' ORDER BY id DESC LIMIT 1';
        }

        if(!$where) return array();
        
        //$cache_key = self::get_cache_key($arg);

        $where = substr($where,4);

        // $cache = wp_cache_get( $cache_key, 'b2_circle_data');

        // if($cache_key && $cache){
        //     return $cache;
        // }

        $res = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE $where"
        ,ARRAY_A);

        // if($cache_key){
        //     $time = 10 * MINUTE_IN_SECONDS;
        //     wp_cache_set($cache_key,$res,'b2_circle_data',$time);
        // }

        return $res;
    }
    
    public static function delete_data($args){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';

        $arr = array();

        if(isset($args['id'])){
            $arr['id'] = (int)$args['id'];
        }

        if(isset($args['user_id']) && $args['user_id'] !== ''){
            $arr['user_id'] = (int)$args['user_id'];
        }

        if(isset($args['circle_id']) && $args['circle_id'] !== ''){
            $arr['circle_id'] = (int)$args['circle_id'];
        }

        //self::flash_cache($arr);

        return $wpdb->delete( $table_name, $arr );
    }
    
    public static function get_count($arg){

        //$cache_key = self::get_cache_key($arg);

        // $cache = wp_cache_get( $cache_key, 'qk_circle_count');
     
        // if($cache_key && $cache){
        //     return (int)filter_var($cache, FILTER_SANITIZE_NUMBER_INT);
        // }

        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_circle_related';
        $where = '';
        
        if(isset($arg['user_id']) && $arg['user_id'] !== ''){
            $where .= $wpdb->prepare(' AND `user_id`=%d',$arg['user_id']);
        }

        if(isset($arg['circle_id']) && $arg['circle_id'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_id`=%d',$arg['circle_id']);
        }

        if(isset($arg['circle_role']) && $arg['circle_role'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_role`=%s',$arg['circle_role']);
        }

        if(isset($arg['circle_key']) && $arg['circle_key'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_key`=%s',$arg['circle_key']);
        }

        if(isset($arg['circle_value']) && $arg['circle_value'] !== ''){
            $where .= $wpdb->prepare(' AND `circle_value`=%s',$arg['circle_value']);
        }

        if(!$where) return 0;

        $where = substr($where,4);

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where");
        
        // if($cache_key){

        //     $time = 10 * MINUTE_IN_SECONDS;
        //     wp_cache_set($cache_key,'i_'.$count,'b2_circle_count',$time);
        // }
        
        return (int)$count;
    }
}
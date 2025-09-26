<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\Record;
use Qk\Modules\Common\User;
/**
 * 签到系统类
 * 
 **/

class Signin {
    
    public function init(){

    }
    
    /**
     * 用户签到
     *
     * @return array 返回签到结果，包括成功或失败的状态和相应的信息
     */
    public static function user_signin() {
        
        // //是否启用连续签到
        // $open = qk_get_option('signin_consecutive_open');
        
        // 获取当前用户ID
        $user_id = get_current_user_id();
        // 如果用户未登录，则返回错误信息
        if(!$user_id) return array('error'=>'请先登录');
    
        // 获取当前日期和时间
        $current_time = wp_strtotime(current_time('mysql'));
        
        $date = wp_date('Y-m-d', $current_time);
        $time = wp_date('H:i:s', $current_time);
    
        // 如果用户已经签到过，则返回错误信息
        if(self::has_signed_in($user_id, $date)) return  array('error' => '您今日已经签到过了，明日再来吧。');
    
        // 向数据库插入签到记录
        if (self::insert_signin_record($user_id, $date, $time)) {
            // 更新用户的连续签到天数
            $consecutive_days = self::update_consecutive_days($user_id, $date);
            
            $value = apply_filters('qk_update_signin',$user_id, $consecutive_days, $date);
            
            if(isset($value['error'])) return $value;
            
            //签到成功HOOK
            do_action('qk_user_signin', $user_id, $value);
    
            return array('success' => true, 'message' => '签到成功！','value' => $value,'consecutiveDays' => $consecutive_days);
        }
    
        return array('error' => '签到失败，请重试。');
    }
    
    /**
     * 检查用户是否已签到
     *
     * @param int $user_id 用户ID
     * @param string $date 日期
     *
     * @return bool 返回用户是否已签到的布尔值
     */
    public static function has_signed_in($user_id, $date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_sign_in';
    
        // 查询数据库，统计指定用户和日期的记录数量
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND sign_in_date = %s AND value != %s", $user_id, $date,'')
        );
    
        // 如果记录数量大于0，则表示用户已签到
        return $count > 0;
    }
    
    /**
     * 插入签到记录
     *
     * @param int $user_id 用户ID
     * @param string $date 日期
     * @param string $time 时间
     *
     * @return bool 返回插入结果的布尔值
     */
    public static function insert_signin_record($user_id, $date, $time) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_sign_in';
        
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND sign_in_date = %s", $user_id, $date)
        );
        
        if($count > 0) {
            return $count > 0;
        }
        
        // 向数据库插入一条新的签到记录
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'sign_in_date' => $date,
                'sign_in_time' => $time,
            ),
            array('%d', '%s', '%s')
        );
    
        // 返回插入结果
        return $result;
    }
    
    /**
     * 更新用户的连续签到天数
     *
     * @param int $user_id 用户ID
     * @param string $date 日期
     * 
     * @return int|array 返回用户的连续签到天数，或者错误信息的数组
     */
    public static function update_consecutive_days($user_id,$date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_sign_in';
    
        // 初始连续签到天数为1
        $consecutive_days = 1;
    
        // 获取昨天的日期
        $previous_date = wp_date('Y-m-d', wp_strtotime($date . '-1 day'));
    
        // 检查用户昨天是否已经签到
        if (self::has_signed_in($user_id, $previous_date)) {
            // 如果用户昨天已签到，则从数据库中获取昨天的连续签到天数并加1
            $consecutive_days = $wpdb->get_var(
                $wpdb->prepare("SELECT consecutive_days FROM $table_name WHERE user_id = %d AND sign_in_date = %s", $user_id, $previous_date)
            );
            $consecutive_days++;
        }
    
        // 更新用户当天的连续签到天数
        $wpdb->update(
            $table_name,
            array('consecutive_days' => $consecutive_days),
            array('user_id' => $user_id, 'sign_in_date' => wp_date('Y-m-d',wp_strtotime($date))),
            array('%d'),
            array('%d', '%s')
        );
        
        //则返回连续签到天数
        return $consecutive_days;
    }
    
    /**
     * 获取用户签到信息
     *
     * @param string $date 签到日期，格式为 Y-m，默认为当前月份
     * @return array 包含签到信息的数组
     */
    public static function get_sign_in_info( $date ) {
        
        global $wpdb;
        
        $sign_in_date = !empty($date) ? sanitize_text_field( $date ) : wp_date( 'Y-m' );
    
        // 获取当前用户 ID
        $user_id = get_current_user_id();
    
        // 获取当前月份的签到信息
        $sign_ins = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}qk_sign_in WHERE user_id = %d AND sign_in_date LIKE %s AND value != %s ORDER BY sign_in_date",
            $user_id,
            $sign_in_date . '%',
            ''
        ), ARRAY_A);
        
        // 错误处理
        if ( $wpdb->last_error ) {
            return array( 'error' => $wpdb->last_error );
        }

        // 计算签到奖励和特殊提示
        $value = array( 'exp' => 3000, 'gift' => array( '辣条', '辣条' ) );
        $special_text = '';
        if ( $consecutive_days == 3 ) {
            $special_text = '再签到3天可以获得666银瓜子';
        }
        
        $sign_days = count( $sign_ins );
        
        // 构造响应数据
        $response = array(
            // 'text' => implode( ',', $value['gift'] ) . '用户经验,' . $value['exp'] . '点',
            // 'specialText' => $special_text,
            'isCheckIn' => false,
            'allDays' => wp_date( 't', wp_strtotime( $sign_in_date . '-01' ) ),
            'curYear' => wp_date( 'Y', wp_strtotime( $sign_in_date . '-01' ) ),
            'curMonth' => wp_date( 'n', wp_strtotime( $sign_in_date . '-01' ) ),
            'curDay' => wp_date( 'j' ),
            'curDate' => wp_date( 'Y-m-d' ),
            'signDays' => $sign_days, // 本月签到天数
            'consecutiveDays' => isset( $sign_ins[ $sign_days - 1 ])? $sign_ins[ $sign_days - 1 ]['consecutive_days'] : 1, //连续签到天数
            'signDaysList' => array(),
            'signBonusDaysList' => array(),
        );
        
        if($sign_ins) {
            
            $response['signDaysList'] = array_map( function( $sign_in ) { //wp_list_pluck
                return (int)wp_date( 'j', wp_strtotime( $sign_in['sign_in_date']));
            }, $sign_ins );
            
            $response['signBonusDaysList'] = array_map( function( $sign_in ) {
                return maybe_unserialize( $sign_in['value']);
            }, $sign_ins );
            
            if(in_array( $response['curDay'] ,$response['signDaysList'])) {
                $response['isCheckIn'] = true;
            }
            
            $consecutive_days = $sign_ins[ $had_sign_days - 1 ]->consecutive_days + 1;
        }
        
        return array( 'data' => $response );
    }
    
    
    //处理补签请求
    function handle_supplement_request() {
        $user_id = get_current_user_id();
        $date = $_POST['date'];
    
        if (has_signed_in($user_id, $date)) {
            $response = array('success' => false, 'message' => '您已经签到过了。');
        } else {
            if (insert_supplement_record($user_id, $date)) {
                $response = array('success' => true, 'message' => '补签成功！');
            } else {
                $response = array('success' => false, 'message' => '补签失败，请重试。');
            }
        }
    
        wp_send_json($response);
    }
    
    // 插入补签记录
    function insert_supplement_record($user_id, $date) {
        global $wpdb;
        $table_name = SIGN_IN_TABLE;
    
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'sign_in_date' => $date,
                'is_supplement' => 1,
                'supplement_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s')
        );
    
        return $result;
    }
    
    // 8. 获取用户的签到记录
    function get_user_signin_records($user_id) {
        global $wpdb;
        $table_name = SIGN_IN_TABLE;
    
        $records = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY sign_in_date ASC", $user_id)
        );
    
        return $records;
    }
    
    function get_signin_ranking() {
        global $wpdb;
        $table_name = SIGN_IN_TABLE;
    
        $query = "SELECT user_id, COUNT(*) AS total_signins, MAX(consecutive_days) AS max_consecutive_days, SUM(bonus_points) AS total_bonus_points
            FROM $table_name
            GROUP BY user_id
            ORDER BY total_signins DESC, max_consecutive_days DESC, total_bonus_points DESC";
        $ranking = $wpdb->get_results($query);
    
        return $ranking;
    }
}
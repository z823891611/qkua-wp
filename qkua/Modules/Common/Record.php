<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\User;
/**
 * 用户数字变化记录操作
 * 
 * */

class Record {
    public function init(){
        // self::update_data(array(
        //         'user_id' => 1,
        //         'record_type' => 'credit',
        //         'value' => 49,
        //         'type' => 'sign_in',
        //         'type_text' => '签到奖励',
        //     )
        // );
        
        //购买者数字记录（余额）
        add_filter('qk_balance_pay_after',array($this,'balance_pay_after_record'),5, 2);

        //购买者数字记录（积分）
        add_filter('qk_credit_pay_after',array($this,'credit_pay_after_record'),5, 2);
    }
    
    public static function update_data($new_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_change_record'; 
        
        if(!isset($new_data['record_type'])) return false;
        
        if(!isset($new_data['total'])){
            
            //余额记录
            if($new_data['record_type'] === 'money'){
                
                $new_data['total'] = User::money_change($new_data['user_id'],$new_data['value']);
                
            }else if($new_data['record_type'] === 'credit'){
                
                $new_data['total'] = User::credit_change($new_data['user_id'],$new_data['value']);
                
            }else if($new_data['record_type'] === 'exp') {
                
                $new_data['total'] = User::exp_change($new_data['user_id'],$new_data['value']);
                
            }else if($new_data['record_type'] === 'commission') {
                
                $new_data['total'] = User::commission_change($new_data['user_id'],$new_data['value']);
                
            }else{
                return false; 
            }

            if($new_data['total'] < 0) return false;
        }
        
        $arr = array(
            'sign_in' => '签到奖励',
            'task' => '任务奖励',
            'recharge' => '充值',
            'sell' => '出售',
            'admin' => '管理员操作'
        );
        
        $default = array(
            'user_id' => 0,
            'record_type' => '',
            'value' => 0,
            'total' => 0,
            'type' => '',
            'type_text' => '',
            'content' => '',
            'date' => current_time('mysql'),
            'status' => '',
            'record_key'=>'',
            'record_value'=>''
        );

        $args = wp_parse_args( $new_data,$default);
        
        $format = array(
            '%d', // user_id
            '%s', // record_type
            '%s', // value
            '%s', // total
            '%s', // type
            '%s', // type_text
            '%s', // content
            '%s',  // date
            '%s', // status
            '%s', // record_key
            '%s', // record_value
        );
        
        if($wpdb->insert($table_name, $args, $format)){

            do_action('qk_record_insert_data',$data);
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function get_record_list($args) {
        $current_user_id = get_current_user_id();
        $paged = isset($args['paged']) ? (int)$args['paged'] : 1;

        if(!$current_user_id) return array('error'=>'请先登录');
        
        if(!isset($args['type']) || !in_array($args['type'],array('exp','money','credit','commission'))) return array('error'=>'类型错误');
        
        $user_id = $current_user_id;
        
        $size = 10;
        $offset = ($paged-1)*$size;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_change_record';
        
        //获取订单数据
        $records = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE user_id = %d
                AND record_type = %s
                ORDER BY date DESC 
                LIMIT %d,%d
            ",
                $user_id,
                $args['type'],
                $offset,
                $size
            ),ARRAY_A);
        
        $count = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*) FROM $table_name
                WHERE user_id = %d
                AND record_type = %s
            ",
                $user_id,
                $args['type']
            ));
            
        return array(
            'pages' => ceil($count/$size),
            'count' => $count,
            'data' => $records
        );
    }
    
    /**
     * 购买者数字记录（余额）
     * $data 订单数据
     * $balance 支付用户的总金钱余额
     */
    public function balance_pay_after_record($data,$balance){
        $array = array(
            'video'=>array(
                'type' => 'video',
                'type_text'=>'购买视频',
            ),
            'xiazai'=>array(
                'type' => 'xiazai',
                'type_text'=>'购买下载资源',
            ),
            'post_neigou'=>array(
                'type' => 'post_neigou',
                'type_text'=>'购买隐藏内容',
            ),
            'vip_goumai'=>array(
                'type' => 'vip_goumai',
                'type_text'=>'开通VIP会员',
            ),
            'credit_chongzhi'=>array(
                'type' => 'credit_chongzhi',
                'type_text'=>'购买积分',
            ),
            'product'=>array(
                'type' => 'product',
                'type_text'=>'购买产品',
            ),
            'join_circle'=>array(
                'type' => 'circle',
                'type_text'=>'支付入圈',
            ),
        );
        
        if(!isset($array[$data['order_type']])) return $data;
        
        self::update_data(array(
                'user_id' => $data['user_id'],
                'record_type' => 'money',
                'value' => -$data['order_total'],
                'total'=> $balance,
                'type' => $array[$data['order_type']]['type'],
                'type_text' => $array[$data['order_type']]['type_text']
            )
        );
        
        return apply_filters('qk_balance_pay_after_record',$data);
    }
    
    /**
     * 给购买者数字记录(积分)
     * $data 订单数据
     * $balance 支付用户的总积分余额
     */
    public function credit_pay_after_record($data,$credit){
        
        $array = array(
            'video'=>array(
                'type' => 'video',
                'type_text'=>'购买视频',
            ),
            'xiazai'=>array(
                'type' => 'xiazai',
                'type_text'=>'购买下载资源',
            ),
            'post_neigou'=>array(
                'type' => 'post_neigou',
                'type_text'=>'购买隐藏内容',
            ),
            'join_circle'=>array(
                'type' => 'circle',
                'type_text'=>'支付入圈',
            ),
        );
        
        if(!isset($array[$data['order_type']])) return $data;
        
        self::update_data(array(
                'user_id' => $data['user_id'],
                'record_type' => 'credit',
                'value' => -$data['order_total'],
                'total'=> $credit,
                'type' => $array[$data['order_type']]['type'],
                'type_text' => $array[$data['order_type']]['type_text']
            )
        );
        
        return apply_filters('qk_credit_pay_after_record',$data);
    }
}
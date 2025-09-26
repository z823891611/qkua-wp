<?php namespace Qk\Modules\Common;
use Qk\Modules\Common\User;
use Qk\Modules\Common\Orders;
use Qk\Modules\Common\Record;
use Qk\Modules\Common\Message;

class Distribution{
    
    public function init(){
        add_filter('qk_order_notify_return', array(__CLASS__,'distribution_action'),0,1);
    }

    public static function distribution_action($order){
        if(empty($order)) return $order;

        if($order['pay_type'] === 'credit') return $order;
        
        //是否开启推广分销
        $distribution_open = qk_get_option('distribution_open');

        if(!$distribution_open) return $order;
        
        if(empty($order['user_id']))  return $order;
        
        // $distribution_user = get_user_meta($order['user_id'],'qk_referrer_id',true);
        // if(!$distribution_user) return $order;
        
        $commission = self::calculate_commission($order);
        
        if(!empty($commission) && isset($commission['total'])){
            add_filter('qk_author_record_data',function ($data) use ($commission){
                
                $data['value'] = bcsub($data['value'],$commission['total'],2);
                $data['content'] = '扣除推广消费 ￥'.$commission['total']; 
                return $data;
            });
        }
    }
    
    /**
     * 计算订单的返佣金额
     *
     * @param int $order 订单数据
     * @return float 返佣金额
     */
    public static function calculate_commission($order) {
        return apply_filters('qk_calculate_commission', $order);
    }
    
    public static function distribution_commission_action($user_id,$add_money,$order,$lv = 'lv1',$ratio) {
        //作者id
        $author_id = get_post_field('post_author', $order['post_id']);
        
        if($author_id){
            
        }
        
        $money = get_user_meta($user_id,'qk_commission_money',true);
        $total_money = bcadd($money,$add_money,2);

        update_user_meta($user_id,'qk_commission_money',$total_money);
        
        $commission = get_user_meta($user_id,'qk_commission',true);
        $commission = !empty($commission) ? $commission : array();
        
        $commission[$lv] = isset($commission[$lv]) ? bcadd($commission[$lv],$add_money,2) : $add_money;
        update_user_meta($user_id,'qk_commission',$commission);
        
        $array = array(
            'product'=>'购买了产品：${post}',
            'video'=>'购买了视频：${post}',
            'xiazai'=>'购买了下载资源：${post}',
            'post_neigou'=>'购买了隐藏内容：${post}',
            
            'vip_goumai'=>'购买了VIP会员',
            'credit_chongzhi'=>'购买了积分',
            'join_circle'=>'支付入圈',
        );
        
        $message_data = array(
            'sender_id' => $order['user_id'],
            'receiver_id' => $user_id,
            'title' => '推广佣金到账提醒',
            'content' => sprintf('您的关联客户%s',$array[$order['order_type']]),
            'type' => 'distribution',
            'post_id' => !empty($order['chapter_id']) ? $order['chapter_id'] : $order['post_id'],
            'mark' => array(
                'meta' => array(
                    array(
                        'key'=> '订单总额',
                        'value'=> '￥'.$order['order_total'],
                    ),
                    array(
                        'key'=> '佣金收益',
                        'value'=> '￥'.$add_money,
                    ),
                    array(
                        'key'=> '关联层级',
                        'value'=> ($lv == 'lv1' ? '一级' : ($lv === 'lv2' ? '二级' : '三级')).' ('.($ratio * 100).'%)',
                    )
                )
                
            )
        );
        
        Message::update_message($message_data);
        
        Record::update_data(array(
            'user_id' => $user_id,
            'record_type' => 'commission',
            'value' => $add_money,
            'total' => $total_money, 
            'type' => 'commission_'.$lv,
            'type_text' => sprintf('%s佣金收益',$lv == 'lv1' ? '一级' : ($lv === 'lv2' ? '二级' : '三级')),
            'content' => $order['order_id']
        ));
    }
    
    /**
     * 当前用户是否有分销的权限
     *
     * @param [type] $user_id 推广人
     * @param integer $money
     *
     * @return void
     * @version 1.2.1
     * @since 2023/12/29 15:30
     */
    public static function user_can_distribution($user_id){
        
        //是否开启推广分销
        $distribution_open = qk_get_option('distribution_open');

        if(!$distribution_open) return false;

        $user_distribution = self::get_user_distribution($user_id);
        
        if(empty($user_distribution)) return false;
        
        if(empty($user_distribution['types'])) return false;
        if(empty($user_distribution['lv1_ratio'])) return false;
        
        return $user_distribution;
    }
    
     /**
     * 获取推广人分销返佣比例
     *
     * @param [type] $user_id 推广人
     * @param integer $money
     *
     * @return void
     * @version 1.2.1
     * @since 2023/12/29 15:30
     */
    public static function get_user_distribution($user_id){
        
        $user_vip = get_user_meta($user_id,'qk_vip',true);
        
        $distribution = qk_get_option('distribution');
        
        if(isset($distribution[$user_vip])) {
            return $distribution[$user_vip];
        }else if(isset($distribution['lv'])){
            return $distribution['lv'];
        }else{
            return array();
        }
        
    }
    
    public static function get_user_commission($user_id){
        
        $commission = array(
            'lv1' => 0,
            'lv2' => 0,
            'lv3' => 0,
            'total' => 0
        );
        
        $money = get_user_meta($user_id,'qk_commission_money',true);
        $money = $money ? $money : 0;

        //提现
        $withdrawal_money = get_user_meta($user_id,'qk_withdrawal_money',true);
        $withdrawal_money = $withdrawal_money ? $withdrawal_money : 0;
        
        $_commission = get_user_meta($user_id,'qk_commission',true);
        $_commission = !empty($_commission) ? $_commission : array();
        // 将默认数组和提取出的参数合并
        $commission = array_merge($commission, array_intersect_key($_commission, $commission));
        $commission['total']  = array_sum($commission);
        
        return array(
            'money' => $money,
            'withdrawn' => $withdrawal_money,
            'data' => $commission,
        );
        
    }
    
    public static function get_user_partner($paged){

        $current_user_id = get_current_user_id();

        if(!$current_user_id) return array('error'=>__('请先登录','qk'));

        $size = 21;

        $offset = ($paged -1)*$size;

        $args = array(
            'number' => $size,
            'offset'=>$offset,
            'order' => 'desc',
            'orderby'=>'user_registered',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'qk_referrer_id',
                    'value' => $current_user_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'qk_lv2_referrer_id',
                    'value' => $current_user_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'qk_lv3_referrer_id',
                    'value' => $current_user_id,
                    'compare' => '='
                )
            )
        );

        $user_query = new \WP_User_Query( $args );
        $authors = $user_query->get_results();

        $data = array();

        if(!empty($authors)){

            foreach ($authors as $k => $v) {
                $public_data = User::get_user_public_data($v->ID,true);

                $lv1 = (int)get_user_meta($v->ID,'qk_referrer_id',true);
                $lv2 = (int)get_user_meta($v->ID,'qk_lv2_referrer_id',true);
                $lv3 = (int)get_user_meta($v->ID,'qk_lv3_referrer_id',true);

                if($lv1 === $current_user_id){
                    $public_data['partner_lv'] = '一级关联';
                }

                if($lv2 === $current_user_id){
                    $public_data['partner_lv'] = '二级关联';
                }

                if($lv3 === $current_user_id){
                    $public_data['partner_lv'] = '三级关联';
                }

                $data[] = $public_data;
            }
        }

        return array(
            'data'=>$data,
            'pages'=>ceil($user_query->get_total()/$size)
        );
    }
    
    public static function get_user_rebate_orders($paged){
        $current_user_id = get_current_user_id();

        if(!$current_user_id) return array('error'=>__('请先登录','qk'));

        $size = 20;

        $offset = ($paged -1)*$size;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_change_record';
        
        $data = $wpdb->get_results(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE `user_id` = %d
                AND `record_type`=%s
                ORDER BY id DESC
                LIMIT %d,%d
            ",
                $current_user_id,
                'commission',
                $offset,
                $size
            )
        ,ARRAY_A);
        
        $count = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*) FROM $table_name
                WHERE `user_id` = %d
                AND `record_type`=%s
            ",
                $current_user_id,
                'commission',
            ));
            
        return array(
            'pages' => ceil($count/$size),
            'count' => $count,
            'data' => $data
        );
    }
}
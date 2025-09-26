<?php 
namespace Qk\Modules\Settings;
use Qk\Modules\Common\Orders as CommonOrders;

use \WP_List_Table;

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
* 消息管理表格
*
* @version 1.0.3
* @since 2023
*/
class OrdersListTable extends WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'ajax' => false  
        ));
    }
    
    function get_count($type,$key=''){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';
        
        $where = '';
        
        if($type == 'order_type'){
            $order_type = $key;
            $order_state = isset($_GET["order_state"]) ? esc_sql($_GET["order_state"]) : '';
            
            if($order_state !== ''){
                $where = $wpdb->prepare('WHERE order_state = %d', $order_state);
            }
            
            if($order_type) {
                
                if($where){
                    $where .= $wpdb->prepare(' AND order_type = %s', $order_type);
                }else{
                    $where .= $wpdb->prepare('WHERE order_type = %s', $order_type);
                }
            }
        }else{
            if($key !== ''){
                $where .= $wpdb->prepare('WHERE order_state = %d', $key);
            }
        }
        
        $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name $where");
            
        $rowcount = $wpdb->get_var($query);
    
        return $rowcount ? $rowcount : 0;
    }
    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            //选择框
            'id' => 'ID',
            'order_id' => '订单号',
            'user_id' => '购买用户',
            'post_id' => '商品',
            'order_type' => '订单类型',
            'order_commodity'=>'商品类型',
            'order_state'=>'订单状态',
            'order_date' => '订单时间',
            'order_count' => '订单数量',
            'order_price'=>'产品单价',
            'order_total'=>'订单总价',
            'money_type'=>'货币类型',
            'pay_type'=>'支付渠道',
            // 'tracking_number'=>'运单信息',
            // 'order_content'=>'买家留言',
            // 'order_address'=>'订单地址',
            'ip_address'=>'IP地址'
        );
        return $columns;
    }
    
    //自定义导航点击连接选项orderby = title参数
    function get_sortable_columns(){
        $sortable_columns = array(
            'ID' => array('ID', false),
            'order_id' => array('order_id',false),
            'user_id' => array('user_id',false),
            'post_id' => array('post_id',false),
            'order_type' => array('order_type',false),
            'order_commodity'=>array('order_commodity',false),
            'order_state'=>array('order_state',false),
            'order_date' => array('order_date',false),
            'order_count' => array('order_count',false),
            'order_price'=>array('order_price',false),
            'order_total'=>array('order_total',false),
            'money_type'=>array('money_type',false),
            'pay_type'=>array('pay_type',false),
            // 'tracking_number'=>array('tracking_number',false),
            // 'order_content'=>array('order_content',false),
            // 'order_address'=>array('order_address',false),
            'ip_address'=>array('ip_address',false)
        );
        return $sortable_columns;
    }
    
     //应用全部导航左边选择框
    function display_tablenav( $which ) {
    ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <?php if ( $this->has_items() ): ?>
                <div class="alignleft actions bulkactions">
                    <?php $this->bulk_actions( $which ); ?>
                </div>
            <?php endif;
                $this->extra_tablenav( $which );
                $this->pagination( $which );
            ?>

            <br class="clear" />
        </div>
    <?php
    }
    
    //选择框选项
    function get_bulk_actions() {
        $actions = array(
            'delete' => '删除',
            'edit'=>'编辑'
        );
        return $actions;
    }
    
    //checkbox勾选框
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/$this->_args['singular'], //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/$item->id //The value of the checkbox should be the record's id
        );
    }
    
    //默认列内容处理
    function column_default($item, $column_name){
        switch ($column_name) {
            case 'user_id':
                $user_data = get_userdata($item->$column_name);
                if($user_data){
                    $avatar = get_avatar_url($item->$column_name,array('size'=>160));
                    return '<div style=" display: inline-flex; align-items: center; ">
                                <img src="'.$avatar.'" style=" width: 24px; height: 24px; margin-right: 5px;border-radius: 50%; ">
                                <a href="'.add_query_arg('user_id',$item->$column_name,remove_query_arg(array('paged'),admin_url('admin.php?'.$_SERVER['QUERY_STRING']))).'">'.$user_data->display_name.'</a>
                            </div>';
                }else {
                    return '游客';
                }
            case 'post_id':
                $title = CommonOrders::get_order_product(get_object_vars($item));
                
                return '<a href="'.$title['link'].'" target="_blank">'.$title['name'].'</a>';
                
            case 'order_type':
                return CommonOrders::get_order_type($item->$column_name);
            case 'order_commodity':
                return ['虚拟物品','实物'][$item->$column_name];
            case 'order_state':
                $arr = array(
                    0=>'<span style="color:#333">待支付</span>',
                    1=>'<span style="color:red">已付款未发货</span>',
                    2=>'<span style="color:blue">已发货</span>',
                    3=>'<span style="color:green">已完成</span>',
                    4=>'<span style="color:#333">已退款</span>',
                    5=>'<span style="color:#999">已删除</span>',
                );
                return $arr[$item->$column_name];
            case 'money_type':
                return ['金额','积分'][$item->$column_name];
            case 'pay_type':
                $types = array(
                    'xunhu'=>'迅虎支付',
                    'xunhu_hupijiao'=>'迅虎虎皮椒支付',
                    'balance'=>'余额支付',
                    'credit'=>'积分支付',
                    'card'=>'卡密支付',
                    'yipay'=>'易支付OR码支付',
                    'alipay'=>'支付宝官方',
                    'wecatpay'=>'微信官方',
                    
                );
                return  $types[$item->$column_name];
            case 'tracking_number':
                return '没有运单信息';
            default:
                return $item->$column_name;
        }
    }
    
    function column_ID($item){

        //Build row actions
        $actions = array(
            'delete' => sprintf('<a onclick="return confirm(\'您确定删除该这条消息吗?\')" href="?page=%s&action=%s&id=%s">删除</a>', $_REQUEST['page'], 'delete', $item->id),
        );
    
        //Return the title contents
        return sprintf(
            '%1$s%2$s',
            /*$1%s*/$item->id,
            /*$2%s*/$this->row_actions($actions)
        );
    }
    
    //action动作处理函数 删除
    function process_bulk_action(){
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';
        
        $doaction = $this->current_action();
        
        if($doaction){
            //Detect when a bulk action is being triggered...
            if (in_array($doaction, ['delete'])) {
                //wp_die('Items deleted (or they would be if we had items to delete)!');
                $ids = isset($_REQUEST['id']) ? (array)$_REQUEST['id'] : '';
    
                if(is_array($ids)){
                    
                    foreach ($ids as $id) {
                        $wpdb->query(
                            $wpdb->prepare( 
                                "DELETE FROM $table_name WHERE id = %d",
                                $id
                            )
                        );
                    }
                }
                
                $sendback = remove_query_arg( array( 'action', 'action2','status'), wp_get_referer());
        	    echo '<script> location.replace("'.$sendback.'"); </script>';
        	    exit();
            }
        }
    }
    
    //初始化列表
    function prepare_items(){
        
        // //执行动作处理函数
        $this->process_bulk_action();
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        
        global $wpdb; 
        $table_name = $wpdb->prefix . 'qk_order';
        $query = "SELECT * FROM $table_name" ;

        //搜索
        $s = isset($_GET["s"]) ? esc_sql($_GET["s"]) : '';
        if(!empty($s)){
            
            $query.= $wpdb->prepare("
                WHERE order_id LIKE %s
                OR user_id LIKE %s
                ",
                '%'.$s.'%','%'.$s.'%'
            );
        }
        
        //状态筛选
        $status = isset($_GET["order_state"]) ? esc_sql($_GET["order_state"]) : '';
        if ($status !== '' && $status != 'all') {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            
            $query.= $wpdb->prepare(" $w `order_state` = %s",$status);

        }
        
        //类型筛选
        $message_type = isset($_GET["order_type"]) ? esc_sql($_GET["order_type"]) : '';
        if (!empty($message_type)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `order_type` = %s",$message_type);
        }
        
        //状态筛选
        $user_id = isset($_GET["user_id"]) ? esc_sql($_GET["user_id"]) : '';
        if (!empty($user_id)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `user_id` = %s",$user_id);
        }
        
        //排序
        $orderby = isset($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'id';
        $order = isset($_GET["order"]) ? esc_sql($_GET["order"]) : 'DESC';
        if (!empty($orderby) & !empty($order)) {
            $query.=' ORDER BY ' . $orderby . ' ' . $order;
        }

        $totalitems = $wpdb->query($query);
        
        //每页显示数量
        $perpage = 20;
        
        //页数
        $paged = isset($_GET["paged"]) ? esc_sql($_GET["paged"]) : '';

        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        
        //总页数
        $totalpages = ceil($totalitems / $perpage);

        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }

        $current_page = $this->get_pagenum();
        //$data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ));

        $this->items = $wpdb->get_results($query);
    }
    
}
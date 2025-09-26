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
class WithdrawalListTable extends WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'ajax' => false  
        ));
    }
    
    function get_count($type){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_change_record';
        
        $where = '';
        
        if($type !== 'all'){
             $where .= $wpdb->prepare(" AND `status` = %d",$type);
        }
        
        $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE type = 'withdrawal' $where");
            
        $rowcount = $wpdb->get_var($query);
    
        return $rowcount ? $rowcount : 0;
    }
    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            //选择框
            'id' => 'ID',
            'status' => '提现状态',
            'user_id' => '提现用户',
            'record_type' => '提现类型',
            'value' => '申请金额',
            'record_value' => '手续费',
            'record_key' => '实付金额',
            'date' => '提现时间',
        );
        return $columns;
    }
    
    //自定义导航点击连接选项orderby = title参数
    function get_sortable_columns(){
        $sortable_columns = array(
            'ID' => array('ID', false),
            'user_id' => array('user_id',false),
            'record_type' => array('type',false),
            'record_key' => array('record_key',false),
            'value'=>array('value',false),
            'date' => array('date',false),
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
            //'edit'=>'编辑'
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
                }
            case 'record_type':
                $arr = array(
                    'money'=>'<span style="color:#333">余额提现</span>',
                    'commission'=>'<span style="color:green">佣金提现</span>',
                );
                return $arr[$item->$column_name];
            case 'record_value':
                return '￥'.$item->$column_name;
            case 'value':
                return '￥'.-$item->$column_name;
            case 'record_key':
                return '<b style="color:green;font-size: 16px;">￥'.$item->$column_name.'</b>';
            case 'status':
                $arr = array(
                    0=>'<span style="color:#333">待审核</span>',
                    1=>'<span style="color:green">已提现</span>',
                );
                
                if($item->$column_name == 0) {
                    return sprintf('<a class="button" href="admin.php?page=withdrawal_edit_page&action=%s&id=%s">立即处理</a>','edit', $item->id);
                }
                
                return $arr[$item->$column_name];
            default:
                return $item->$column_name;
        }
    }
    
    function column_ID($item){

        //Build row actions
        $actions = array(
            'delete' => sprintf('<a onclick="return confirm(\'您确定删除该这条消息吗?\')" href="?page=%s&action=%s&id=%s">删除</a>', $_REQUEST['page'], 'delete', $item->id),
            //'edit' => sprintf('<a class="button" href="admin.php?page=withdrawal_edit_page&action=%s&id=%s"">编辑</a>','edit', $item->id),
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
        $table_name = $wpdb->prefix . 'qk_change_record';
        
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
        $table_name = $wpdb->prefix . 'qk_change_record';
        $query = "SELECT * FROM $table_name" ;
        
        $query.= $wpdb->prepare(" WHERE type = %s",'withdrawal');

        //搜索
        $s = isset($_GET["s"]) ? esc_sql($_GET["s"]) : '';
        if(!empty($s)){
            $query.= $wpdb->prepare(" AND user_id LIKE %s",'%'.$s.'%');
        }
        
        //状态筛选
        $status = isset($_GET["status"]) ? esc_sql($_GET["status"]) : '';
        if ($status !== '' && $status != 'all') {
            $query.= $wpdb->prepare(" AND `status` = %d",$status);
        }
        
        //类型筛选
        $type = isset($_GET["record_type"]) ? esc_sql($_GET["record_type"]) : '';
        if (!empty($type)) {
            $query.= $wpdb->prepare(" AND `record_type` = %s",$type);
        }
        
        $user_id = isset($_GET["user_id"]) ? esc_sql($_GET["user_id"]) : '';
        if (!empty($user_id)) {
            $query.= $wpdb->prepare(" AND `user_id` = %s",$user_id);
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
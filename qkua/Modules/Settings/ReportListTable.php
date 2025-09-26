<?php 
namespace Qk\Modules\Settings;

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
class ReportListTable extends WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'ajax' => false  
        ));
    }
    
    function get_count($type){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_report';
        
        $where = '';
        
        if($type !== 'all'){
             $where .= $wpdb->prepare(" WHERE `status` = %d",$type);
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
            'status' => '投诉状态',
            'user_id' => '投诉者',
            'type' => '投诉原因',
            'content' => '投诉描述',
            'reported_id' => '投诉对象',
            'reported_type' => '对象类型',
            'date' => '投诉时间',
            'mark' => '其他',
        );
        return $columns;
    }
    
    //自定义导航点击连接选项orderby = title参数
    function get_sortable_columns(){
        $sortable_columns = array(
            'id' => array('ID', false),
            'user_id' => array('user_id',false),
            'reported_id' => array('reported_id',false),
            'type' => array('type',false),
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
            case 'reported_type':
                $arr = array(
                    'post'=>'文章帖子',
                    'user'=>'用户',
                );
                return $arr[$item->$column_name];
            case 'reported_id':
                
                if($item->reported_type == 'post'){
                    return '<a target="_blank" href="'.get_permalink($item->$column_name).'">'.get_the_title($item->$column_name).'</a>';
                }
            case 'status':
                $arr = array(
                    0=>'<span style="color:#333">待处理</span>',
                    1=>'<span style="color:green">已处理</span>',
                );
                
                if($item->$column_name == 0) {
                    return sprintf('<a class="button" onclick="return confirm(\'确定已处理完成吗?\')" href="?page=%s&action=%s&id=%s">立即处理</a>',$_REQUEST['page'],'update', $item->id);
                }
                
                return $arr[$item->$column_name];
            default:
                return $item->$column_name;
        }
    }
    
    function column_ID($item){

        //Build row actions
        $actions = array(
            'delete' => sprintf('<a onclick="return confirm(\'您确定删除该这条举报消息吗?\')" href="?page=%s&action=%s&id=%s">删除</a>', $_REQUEST['page'], 'delete', $item->id),
            //'edit' => sprintf('<a class="button" href="admin.php?page=withdrawal_edit_page&action=%s&id=%s"">编辑','edit', $item->id),
        );
        
        //Return the title contents
        return sprintf(
            '%1$s%2$s',
            /*$1%s*/$item->id,
            /*$2%s*/$this->row_actions($actions)
        );
    }
    
    // function delete_coupons($ids){
        
    // }
    
    //action动作处理函数 删除
    function process_bulk_action(){
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_report';
        
        $doaction = $this->current_action();
        
        if($doaction){

            //Detect when a bulk action is being triggered...
            if (in_array($doaction, ['delete','update'])) {
                //wp_die('Items deleted (or they would be if we had items to delete)!');
                $ids = isset($_REQUEST['id']) ? (array)$_REQUEST['id'] : '';
    
                if(is_array($ids)){
                    
                    foreach ($ids as $id) {
                        if($doaction == 'delete'){
                            $wpdb->query(
                                $wpdb->prepare( 
                                    "DELETE FROM $table_name WHERE id = %d",
                                    $id
                                )
                            );
                        }
                        
                        else if($doaction == 'update'){
                            $wpdb->update(
                                $table_name, 
                                array( 
                                    'status' => 1,
                                ), 
                                array( 'id' => $id ),
                                array( 
                                    '%d',
                                    '%d'
                                ), 
                                array( '%d' ) 
                            );
                        }
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
        $table_name = $wpdb->prefix . 'qk_report';
        $query = "SELECT * FROM $table_name" ;
        
        //搜索
        $s = isset($_GET["s"]) ? esc_sql($_GET["s"]) : '';
        if(!empty($s)){
            $query.= $wpdb->prepare("
                WHERE user_id LIKE %s
                OR title LIKE %s
                OR data LIKE %s
                OR type LIKE %s
                ",
                '%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%'
            );
        }
        
        //类型筛选
        $type = isset($_GET["type"]) ? esc_sql($_GET["type"]) : '';
        if (!empty($type)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `type` = %s",$type);
        }
        
        //状态筛选
        $status = isset($_GET["status"]) ? esc_sql($_GET["status"]) : '';
        if ((!empty($status) && ($status != 'all') || $status == '0')) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `status` = %s",$status);
        }
        
        //用户筛选
        $user_id = isset($_GET["user_id"]) ? esc_sql($_GET["user_id"]) : '';
        if (!empty($user_id)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `status` = %s",$user_id);
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
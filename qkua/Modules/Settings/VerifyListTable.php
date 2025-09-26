<?php 
namespace Qk\Modules\Settings;
use Qk\Modules\Settings\Verify;

use \WP_List_Table;

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
* 认证管理
*
* @version 1.0.3
* @since 2024/5/15
*/
class VerifyListTable extends WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'ajax' => false  
        ));
    }
    
    function get_count($type){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_verify';
        
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
            'status' => '认证状态',
            'user_id' => '认证用户',
            'type' => '认证类型',
            'title' => '认证名称',
            'money' => '认证金额费用',
            'credit' => '认证积分费用',
            'verified' => '是否实名',
            'date' => '申请时间',
            'data' => '认证资料',
        );
        return $columns;
    }
    
    //自定义导航点击连接选项orderby = title参数
    function get_sortable_columns(){
        $sortable_columns = array(
            'ID' => array('ID', false),
            'user_id' => array('user_id',false),
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
                                <a target="_blank" href="'.get_author_posts_url($user_data->ID).'">'.trim(esc_attr($user_data->display_name)).'</a>
                            </div>';
                }
            case 'type':
                $verify_group = qk_get_option('verify_group');
                
                $verify_group = !empty($verify_group) ? $verify_group : array();
                $type = $item->$column_name;
                
                // 使用array_filter()来过滤数组
                $verify = array_filter($verify_group, function($verify) use ($type) {
                    return $verify['type'] == $type;
                });
                
                $verify = !empty($verify) ? array_values($verify) : array();
                
                return !empty($verify[0]) ? $verify[0]['name'] : '';
            case 'money':
            case 'credit':
                return $item->$column_name > 0 ? '<b style="color:green;">已支付('.$item->$column_name.')</b>' : $item->$column_name;
            case 'status':
                $arr = array(
                    0=>'<span style="color:#333">待审核</span>',
                    1=>'<span style="color:green">已认证</span>',
                    2=>'<span style="color:red">未通过</span>',
                );
                
                if($item->$column_name == 0) {
                    return sprintf('<a class="button" href="admin.php?page=%s&action=%s&id=%s">立即处理</a>', 'verify_edit_page','edit', $item->id);
                }
                
                return $arr[$item->$column_name];
            case 'data':
               return Verify::get_verify_data_html($item->$column_name);
            default:
                return $item->$column_name;
        }
    }
    
    function column_ID($item){

        //Build row actions
        $actions = array(
            'delete' => sprintf('<a onclick="return confirm(\'您确定删除该这条认证信息吗?\')" href="?page=%s&action=%s&id=%s">删除</a>', $_REQUEST['page'], 'delete', $item->id),
            'edit' => sprintf('<a href="admin.php?page=%s&action=%s&id=%s"">编辑</a>', 'verify_edit_page','edit', $item->id),
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
        $table_name = $wpdb->prefix . 'qk_verify';
        
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
        $table_name = $wpdb->prefix . 'qk_verify';
        $query = "SELECT * FROM $table_name" ;
        
        //搜索
        $s = isset($_GET["s"]) ? esc_sql($_GET["s"]) : '';
        if(!empty($s)){
            $query.= $wpdb->prepare("
                WHERE user_id LIKE %s
                OR content LIKE %s
                OR reported_id LIKE %s
                OR reported_type LIKE %s
                OR type LIKE %s
                ",
                '%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%'
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
        
        //类型筛选
        $reported_type = isset($_GET["reported_type"]) ? esc_sql($_GET["reported_type"]) : '';
        if (!empty($reported_type)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `status` = %s",$reported_type);
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
<?php 
namespace Qk\Modules\Settings;
use Qk\Modules\Common\User;

use \WP_List_Table;

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
* 邀请码表格
*
* @version 1.0.2
* @since 2023
*/
class CardListTable extends WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'ajax' => false  
        ));
    }
    
    function get_status_count($status){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_card';
    
        $card_type = isset($_GET["type"]) ? esc_sql($_GET["type"]) : '';
        $where = '';
    
        if($card_type && $card_type != 'all' && $status !== 'all') {
            $where = 'AND type = %s';
        }else if($card_type){
            $where = 'WHERE type = %s';
        }
    
        if($status === 'all'){
            $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name $where", $card_type);
        }else{
            $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s $where", $status, $card_type);
        }
    
        $rowcount = $wpdb->get_var($query);
    
        return $rowcount ? $rowcount : 0;
    }
    
    function get_type_count($type){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_card';
        if($type === 'all'){
            $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }else{
            $rowcount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE type = %s",$type));
        }
        return $rowcount ? $rowcount : 0;
    }
    
    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />',
            //选择框
            'id' => 'ID',
            'card_code' => '卡密兑换码',
            'type' => '卡密类型',
            'value' => '面值',
            'card_key' => '键',
            'card_value' => '值',
            'status' => '状态',
            'user_id' => '使用者',
        );
        return $columns;
    }
    
    //自定义导航点击连接选项orderby = title参数
    function get_sortable_columns(){
        $sortable_columns = array(
            'id' => array('id', false),
            'card_code' => array('card_code', false),
            'type' => array('type', false),
            'value' => array('value', false),
            'card_key' => array('card_key', false),
            'card_value' => array('card_value', false),
            'status' => array('status', false),
            'user_id' => array('user_id', false),
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
    
    function get_card_types($key,$val){
        if($key == 'type'){
            $arr = array(
                'money'=>'<span style="font-weight: bold;color:blue">充值卡</span>',
                'credit'=>'<span style="font-weight: bold;color:green">积分卡</span>',
                'invite'=>'<span style="font-weight: bold;color:#bdbaba">邀请码</span>',
                'vip'=>'<span style="font-weight: bold;color:red">会员卡</span>',
            );
        }
        
        return isset($arr[$val]) ? $arr[$val] : '';
    }
    
    //默认列内容处理
    function column_default($item, $column_name){
        switch ($column_name) {
            case 'status':
                 return $item->$column_name == 1 ? '<span class="red">已使用</span>' : '<span class="green">未使用</span>';
            case 'user_id':
                $user_data = get_userdata($item->$column_name);
                if($user_data){
                    return '<a href="'.get_author_posts_url($item->$column_name).'" target="_blank">'.$user_data->display_name.'</a>';
                }else {
                    return '暂无';
                }
            case 'value':
                if($item->type == 'money' || $item->type == 'credit' || $item->type == 'vip'){
                    return $item->$column_name.'元';
                }else {
                    return $item->$column_name.'积分';
                }
                
            case 'type':
                return $this->get_card_types('type',$item->$column_name);
            case 'card_key':
                $user_vip_group = User::get_user_roles();;
                $user_vip_group = is_array($user_vip_group) ? $user_vip_group : array();
                
                if(isset($user_vip_group[$item->$column_name])) {
                    return $user_vip_group[$item->$column_name]['name'];
                }
            case 'card_value':
                if($item->$column_name) {
                    return $item->$column_name.'天';
                }else if($item->$column_name == '0') {
                    return '永久';
                }
            default:
                return $item->$column_name;
        }
    }
    
    function column_ID($item){

        //Build row actions
        $actions = array(
            'delete' => sprintf('<a onclick="return confirm(\'您确定删除该这个卡密吗?\')" href="?page=%s&action=%s&id=%s">删除</a>', $_REQUEST['page'], 'delete', $item->id),
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
        $table_name = $wpdb->prefix . 'qk_card';
        
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
        $table_name = $wpdb->prefix . 'qk_card';
        $query = "SELECT * FROM $table_name" ;
        
        //搜索
        $s = isset($_GET["s"]) ? esc_sql($_GET["s"]) : '';
        if(!empty($s)){
            $query.= $wpdb->prepare("
                WHERE user_id LIKE %s
                OR card_code LIKE %s
                OR value LIKE %s
                OR card_key LIKE %s
                OR card_value LIKE %s
                ",
                '%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%'
            );
        }

        //类型筛选
        $card_type = isset($_GET["type"]) ? esc_sql($_GET["type"]) : '';
        if (!empty($card_type)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `type` = %s",$card_type);
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
        
        //排序
        $orderby = isset($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'id';
        $order = isset($_GET["order"]) ? esc_sql($_GET["order"]) : 'DESC';
        if (!empty($orderby) & !empty($order)) {
            $query.=' ORDER BY ' . $orderby . ' ' . $order;
        }

        $totalitems = $wpdb->query($query);
        
        //print_r($query);
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
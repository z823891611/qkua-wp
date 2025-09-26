<?php 
namespace Qk\Modules\Settings;
use Qk\Modules\Common\Message;
use Qk\Modules\Common\Comment;

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
class MessageListTable extends WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'ajax' => false  
        ));
    }
    
    function get_type_count($type){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_message';
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
            'ID' => 'ID',
            'sender_id' => '发送者',
            'receiver_id' => '接收者',
            'title' => '消息标题',
            'content' => '消息类容',
            'mark' => '其他',
            'date' => '发送时间',
        );
        return $columns;
    }
    
    //自定义导航点击连接选项orderby = title参数
    function get_sortable_columns(){
        $sortable_columns = array(
            'ID' => array('ID', false),
            'sender_id' => array('sender_id', false),
            'receiver_id' => array('receiver_id', false),
            'title' => array('title', false),
            'content' => array('content', false),
            'type' => array('type', false),
            'date' => array('date', false),
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
            /*$2%s*/$item->ID //The value of the checkbox should be the record's id
        );
    }
    
    function get_message_types($type = '') {
        $types = [
            'chat' => array('name' => '私聊','avatar' => QK_THEME_URI.'/Assets/fontend/images/vip.webp'),
            'vip' => array('name' => '会员通知','avatar' => QK_THEME_URI.'/Assets/fontend/images/vip.webp'),
            'wallet' => array('name' => '钱包通知','avatar'=> QK_THEME_URI.'/Assets/fontend/images/wallet.webp'),
            'serve' => array('name' => '服务通知','avatar'=> QK_THEME_URI.'/Assets/fontend/images/serve.webp'),
            'system' => array('name' => '系统通知','avatar'=>QK_THEME_URI.'/Assets/fontend/images/system.webp'),
            'follow' => array('name' => '新粉丝','avatar'=> QK_THEME_URI.'/Assets/fontend/images/follow.webp'),
            'like' => array('name' => '收到的赞','avatar'=> QK_THEME_URI.'/Assets/fontend/images/like.webp'),
            'comment' => array('name' => '互动消息','avatar'=> QK_THEME_URI.'/Assets/fontend/images/comment.webp'),
            'circle' => array('name' => '圈子消息','avatar'=> 'https://www.qkua.com/wp-content/uploads/2023/10/qkua.png'),
            'distribution' => array('name' => '推广返佣','avatar'=> QK_THEME_URI.'/Assets/fontend/images/wallet.webp'),
        ];
        
        return $type && isset($types[$type]) ? $types[$type] : $types;
    }
    
    //默认列内容处理
    function column_default($item, $column_name){
        switch ($column_name) {
            case 'sender_id':
            case 'receiver_id':
                $user_data = get_userdata($item->$column_name);
                if($user_data && ($item->type == 'chat' || $column_name == 'receiver_id')){
                    $avatar = get_avatar_url($item->$column_name,array('size'=>160));
                    return '<div style=" display: inline-flex; align-items: center; ">
                                <img src="'.$avatar.'" style=" width: 24px; height: 24px; margin-right: 5px;border-radius: 50%; ">
                                <a href="'.add_query_arg('user_id',$item->$column_name,remove_query_arg(array('paged'),admin_url('admin.php?'.$_SERVER['QUERY_STRING']))).'">'.$user_data->display_name.'</a>
                            </div>';
                }else {
                    $types = $this->get_message_types($item->type);
                    
                    if($item->$column_name != 10000001) {
                        return '<div style=" display: inline-flex; align-items: center; ">
                                    <img src="'.$types['avatar'].'" style=" width: 24px; height: 24px; margin-right: 5px;border-radius: 50%; ">
                                    <span>'.$types['name'].'</span>
                                </div>';
                    }else {
                        return '<span>所有人</span>';
                    }
                }
            case 'type':
                return $this->get_message_types($item->$column_name)['name'];
            case 'content':
                
                $user = '';
                if($item->sender_id) {
                    $user_data = get_userdata($item->sender_id);
                    $user = '<a href="'.get_author_posts_url($item->sender_id).'" target="_blank">'.$user_data->display_name.' </a>';
                }
                
                if($item->post_id) {
                    $post = array(
                        'title'=>get_the_title($item->post_id),
                        'link'=>get_permalink($item->post_id),
                    );

                    if($item->type == 'serve' || $item->type == 'distribution') {
                        
                        return $user . Message::replaceDynamicData($item->$column_name,array('post'=>'<a href="'.$post['link'].'" target="_blank">'.$post['title'].'</a>'));
                        
                    }else if ($item->type == 'like' || $item->type == 'comment' && !empty($item->mark)) {
                        
                        $mark = maybe_unserialize($item->mark);
                       
                        if(isset($mark[0])) {
                            $comment = get_comment($mark[0]);
                            if($item->type == 'comment') {
                                $item->$column_name = '评论了你的文章：<a href="'.$post['link'].'" target="_blank">'.$post['title'].'</a> : ' . Comment::comment_filters($comment->comment_content);
                            }else{ //评论点赞
                                $item->$column_name = $item->$column_name.'<p><code>'.Comment::comment_filters($comment->comment_content).'</code></p>';
                            }
                            
                            if(isset($mark[1])) {
                                $item->$column_name = '在文章 <a href="'.$post['link'].'" target="_blank">'.$post['title'].'</a> 中回复 <a href="'.get_author_posts_url($item->receiver_id).'">@'.get_userdata($item->receiver_id)->display_name.'</a> ：'.Comment::comment_filters($comment->comment_content).'<p><code>'.Comment::comment_filters(get_comment($mark[1])->comment_content).'</code></p>';
                            }
                        }else{
                            $item->$column_name = $item->$column_name . ' : <a href="'.$post['link'].'" target="_blank">'.$post['title'].'</a>';
                        }
                        
                        return $user . $item->$column_name;
                        
                    }
                    
                    
                }else if ($item->type == 'follow') {
                    
                    return $user . $item->$column_name;
                }else if ($item->type == 'chat'){
                    
                    if(!empty($item->mark) && $item->content == '[图片]') {
                        $mark = maybe_unserialize($item->mark);
                        
                        if(is_array($mark) && isset($mark['type']) && $mark['type'] == 'image') {
                            return '<a href="'.admin_url('upload.php?item='.$mark['id']).'" target="_blank"><img src="'.$mark['url'].'" style=" max-height: 120px;width: auto; "></a>';
                        }
                    }
                    
                    return Comment::comment_filters($item->$column_name);
                }
                
            case 'mark':
                $mark = maybe_unserialize($item->$column_name);
                if(isset($mark['meta'])) {
                    $text = '<span>';
                    foreach ($mark['meta'] as $value) {
                        $text .= $value['key'].'：'.$value['value'].'<br>';
                    }
                    $text .= '</span>';
                    
                    return  $text;
                }
            default:
                return $item->$column_name;
        }
    }
    
    function column_ID($item){

        //Build row actions
        $actions = array(
            'delete' => sprintf('<a onclick="return confirm(\'您确定删除该这条消息吗?\')" href="?page=%s&action=%s&id=%s">删除</a>', $_REQUEST['page'], 'delete', $item->ID),
        );
    
        //Return the title contents
        return sprintf(
            '%1$s%2$s',
            /*$1%s*/$item->ID,
            /*$2%s*/$this->row_actions($actions)
        );
    }
    
    //action动作处理函数 删除
    function process_bulk_action(){
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_message';
        
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
        $table_name = $wpdb->prefix . 'qk_message';
        $query = "SELECT * FROM $table_name" ;

        //搜索
        $s = isset($_GET["s"]) ? esc_sql($_GET["s"]) : '';
        if(!empty($s)){
            $query.= $wpdb->prepare("
                WHERE sender_id LIKE %s
                OR receiver_id LIKE %s
                OR title LIKE %s
                OR content LIKE %s
                OR type LIKE %s
                ",
                '%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%','%'.$s.'%'
            );
        }
        
        //类型筛选
        $message_type = isset($_GET["type"]) ? esc_sql($_GET["type"]) : '';
        if (!empty($message_type)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w `type` = %s",$message_type);
        }
        
        //状态筛选
        $user_id = isset($_GET["user_id"]) ? esc_sql($_GET["user_id"]) : '';
        if (!empty($user_id)) {
            $w = 'WHERE';
            if(strpos($query,'WHERE') !== false){
                $w = 'AND';
            }
            $query.= $wpdb->prepare(" $w ((type = %s AND (`sender_id` = %d OR `receiver_id` = %d)) OR (type != %s AND `receiver_id` = %d) OR `receiver_id` IN (10000001, 10000002, 10000003))",'chat',$user_id,$user_id,'chat',$user_id);
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
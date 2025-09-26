<?php
namespace Qk\Modules\Settings;

//数据图表设置

class Echarts{

    //设置主KEY
    public static $prefix = 'qk_main_options';

    public function init(){
        
        //加载图标用js
        add_action('admin_enqueue_scripts', array($this, 'load_enqueue_admin_script'));
        
        \CSF::createSection(self::$prefix, array(
            'id'    => 'qk_echarts_options',
            'title' => '数据统计',
            'icon'  => 'fas fa-chart-bar',
            'fields' => array(
                array(
                    'type'     => 'callback', //回调
                    'function' => array($this,'echarts_page_cb')
                ),
            )
        ));
    }
    
    public function echarts_page_top_cb() {
        // 获取待审文章数量
        $pending_posts = wp_count_posts('post')->pending;
        
        // 获取待审评论数量
        $pending_comments = wp_count_comments()->moderated;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_change_record';
        $t = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE type = %s AND status = %d",'withdrawal',0));
        
        $table_name = $wpdb->prefix . 'qk_report';
        $j = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %d",0));
        
        $table_name = $wpdb->prefix . 'qk_verify';
        $r = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %d",0));
        
        echo '
            <div class="echarts-top">
                <div class="left">
                    <div class="title">待处理事项</div>
                    <ul>
                        <li>
                            <a href="'.admin_url( 'edit.php?post_status=pending' ).'">
                                <div>待审文章</div>
                                <div>'.$pending_posts.'</div>
                            </a>
                        </li>
                        <li>
                            <a href="'.admin_url( 'edit-comments.php?comment_status=moderated' ).'">
                                <div>待审评论</div>
                                <div>'.$pending_comments.'</div>
                            </a>
                        </li>
                        <li>
                            <a href="'.admin_url( 'admin.php?page=withdrawal_list_page&status=0' ).'">
                                <div>待审提现</div>
                                <div>'.$t.'</div>
                            </a>
                        </li>
                        <li>
                            <a href="'.admin_url( 'admin.php?page=report_list_page&status=0' ).'">
                                <div>待审举报</div>
                                <div>'.$j.'</div>
                            </a>
                        </li>
                        <li>
                            <a href="'.admin_url( 'admin.php?page=verify_list_page&status=0' ).'">
                                <div>待审认证</div>
                                <div>'.$r.'</div>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="right">
                    <div class="title">快捷入口</div>
                    <ul>
                        <li>
                            <a href="'.admin_url( 'admin.php?page=qk_card_bulid' ).'">
                                <i class="fas fa-money-check"></i>
                                <div>生成卡密</div>
                            </a>
                        </li>
                        <li>
                            <a href="'.admin_url( 'admin.php?page=qk_message_push' ).'">
                                <i class="fas fa-comment-alt"></i>
                                <div>推送消息</div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        ';
    }
    
    /**
     * 数据统计页面
     *
     * @return void
     * 
     * @version 1.0.0
     * @since 2018
     */
    public function echarts_page_cb() {
        $this->echarts_page_top_cb();
        
        echo '<div class="data-container">';
            $this->echarts_order();
            $this->echarts_wp();
        echo '</div>';
    }
    
    public function echarts_order() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order';
        
        // 查询30天每日收入
        $thirtyDaysData = $wpdb->get_results("
            SELECT DATE(order_date) AS date, SUM(order_total) AS total
            FROM $table_name
            WHERE order_state != '0' AND order_state != '4' AND money_type = 0 AND pay_type != 'balance'
            AND order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(order_date)
        ", ARRAY_A);
        
        // 查询年每月收入
        $yearlyData = $wpdb->get_results("
            SELECT DATE_FORMAT(order_date, '%Y-%m') AS date, SUM(order_total) AS total 
            FROM $table_name 
            WHERE order_state != '0' AND order_state != '4' AND money_type = 0 AND order_type != 'balance' 
            AND YEAR(order_date) = YEAR(CURDATE()) 
            GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ", ARRAY_A);
        
        // 自动填充缺失的日期和月份数据
        $thirtyDaysData = self::fillMissingDates($thirtyDaysData, 30);
        $yearlyData = self::fillMissingMonths($yearlyData);
        
        // 获取最近7天的每日收入数据
        $sevenDaysData = array_slice($thirtyDaysData, -7);
    
        wp_localize_script('qk-admin', 'showOrderData', array(
            'daily' => $sevenDaysData,
            'monthly' => $thirtyDaysData,
            'yearly' => $yearlyData
        ));
        
        $data = $wpdb->get_results("
            SELECT 
            SUM(CASE WHEN DATE(order_date) = CURDATE() THEN order_total ELSE 0 END) AS day,
            SUM(CASE WHEN DATE(order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN order_total ELSE 0 END) AS yesterday,
            SUM(CASE WHEN YEAR(order_date) = YEAR(CURRENT_DATE()) AND MONTH(order_date) = MONTH(CURRENT_DATE()) THEN order_total ELSE 0 END) AS month,
            SUM(CASE WHEN YEAR(order_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND MONTH(order_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) THEN order_total ELSE 0 END) AS last_month,
            SUM(CASE WHEN YEAR(order_date) = YEAR(CURRENT_DATE()) THEN order_total ELSE 0 END) AS year,
            SUM(CASE WHEN YEAR(order_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 YEAR)) THEN order_total ELSE 0 END) AS last_year,
            SUM(IF(order_state != '0',order_total,0)) as total,
            SUM(IF(order_state = '4',order_total,0)) as refund
            FROM $table_name WHERE order_state != '0' AND order_state != '4' AND money_type = 0 AND order_type != 'balance' 
        ",ARRAY_A);
        
        self::getIncomeData();
        
        echo '<section class="data-info">';
        echo '<ul class="data-card">
                <li class="card-item">
                    <div class="header">今日收入</div>
                    <div class="body">
                        <span class="value">' . ($data[0]['day']?:0) . '</span>
                    </div>
                    <div class="footer">
                        <div class="left">
                            <span class="label">昨日：</span>
                            '.($data[0]['yesterday'] > 0 ?'
                            <span class="value" style=" color: #5bbf60; "><i title="fas fa-caret-up" class="fas fa-caret-up"></i> ' . $data[0]['yesterday'] . '</span>':'<span class="value">--</span>').'
                        </div>
                    </div>
                </li>
                <li class="card-item">
                    <div class="header">当月收入</div>
                    <div class="body">
                        <span class="value">' . ($data[0]['month']?:0) . '</span>
                    </div>
                    <div class="footer">
                        <div class="left">
                            <span class="label">上月：</span>
                            '.($data[0]['last_month'] > 0 ?'
                            <span class="value" style=" color: #5bbf60; "><i title="fas fa-caret-up" class="fas fa-caret-up"></i> ' . $data[0]['last_month'] . '</span>':'<span class="value">--</span>').'
                        </div>
                    </div>
                </li>
                <li class="card-item">
                    <div class="header">今年收入</div>
                    <div class="body">
                        <span class="value">' . ($data[0]['year']?:0) . '</span>
                    </div>
                    <div class="footer">
                        <div class="left">
                            <span class="label">去年：</span>
                            '.($data[0]['last_year'] > 0 ?'
                            <span class="value" style=" color: #5bbf60; "><i title="fas fa-caret-up" class="fas fa-caret-up"></i> ' . $data[0]['last_year'] . '</span>':'<span class="value">--</span>').'
                        </div>
                    </div>
                </li>
                <li class="card-item">
                    <div class="header">总收入</div>
                    <div class="body">
                        <span class="value">' . ($data[0]['total']?:0) . '</span>
                    </div>
                </li>
            </ul>';
        
        echo '<div class="data-tabs">
                <div onclick="orderEchartsPie(showIncomeData.today_income, \'今日\')">今日</div>
                <div onclick="showSevenDaysData()">最近7天</div>
                <div onclick="showThirtyDaysData()">最近30天</div>
                <div onclick="showYearlyData()">最近1年</div>
            </div>';
        
        // 输出统计数据
        echo '<div class="data-chart">
                <div id="order-echarts" style="width: 60%;height:450px;"></div>
                <div id="order-echarts-Pie" style="width: 40%;height:450px;"></div>
            </div>';
        
        echo '</section>';
        
    }
    
    public static function getIncomeData() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_order'; // 替换为实际的表名
    
        $types = array(
            'choujiang' => '抽奖',
            'duihuan' => '兑换',
            'goumai' => '购买',
            'post_neigou' => '文章内购',
            'dashang' => '打赏',
            'xiazai' => '资源下载',
            'money_chongzhi' => '余额充值',
            'vip_goumai' => 'VIP购买',
            'credit_chongzhi' => '积分购买',
            'video' => '视频购买',
            'verify' => '认证付费',
            'mission' => '签到填坑',
            'coupon' => '优惠劵订单',
            'circle_join' => '支付入圈',
            'circle_read_answer_pay' => '付费查看提问答案',
            'product' => '产品购买'
        );
    
        $income_array = array(
            'today_income' => array(),
            'seven_days_income' => array(),
            'thirty_days_income' => array(),
            'total_income' => array()
        );
    
        $queries = array(
            'today_income' => "SELECT order_type, SUM(order_total) as value FROM $table_name WHERE order_state != '0' AND order_state != '4' AND money_type = 0 AND DATE(order_date) = CURDATE() GROUP BY order_type",
            'seven_days_income' => "SELECT order_type, SUM(order_total) as value FROM $table_name WHERE order_state != '0' AND order_state != '4' AND money_type = 0 AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY order_type",
            'thirty_days_income' => "SELECT order_type, SUM(order_total) as value FROM $table_name WHERE order_state != '0' AND order_state != '4' AND money_type = 0 AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY order_type",
            'total_income' => "SELECT order_type, SUM(order_total) as value FROM $table_name WHERE order_state != '0' AND order_state != '4' AND money_type = 0 GROUP BY order_type"
        );
    
        foreach ($queries as $key => $query) {
            $results = $wpdb->get_results($query, ARRAY_A);
            foreach ($types as $type => $name) {
                $found = false;
                if ($results) {
                    foreach ($results as $result) {
                        if ($result['order_type'] == $type) {
                            $income_array[$key][] = array(
                                'value' => $result['value'],
                                'name' => $name
                            );
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $income_array[$key][] = array(
                        'value' => 0,
                        'name' => $name
                    );
                }
            }
        }
        
        wp_localize_script('qk-admin', 'showIncomeData', $income_array);
    }
    
    
    
    // 填充缺失的日期数据
    public static function fillMissingDates($data, $days) {
        $dates = array();
        $result = array();
    
        // 获取日期范围
        $start_date = wp_date('Y-m-d', strtotime("-$days days"));
        $end_date = wp_date('Y-m-d');
    
        // 生成日期数组
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $dates[] = $current_date;
            $current_date = wp_date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
    
        // 填充缺失的日期数据
        foreach ($dates as $date) {
            $found = false;
            foreach ($data as $item) {
                if ($item['date'] == $date) {
                    $result[] = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result[] = array('date' => $date, 'total' => 0);
            }
        }
    
        return $result;
    }
    
    // 填充缺失的月份数据
    private function fillMissingMonths($data) {
        $months = array();
        $result = array();
    
        // 获取月份范围
        $start_month = wp_date('Y-m', strtotime("-1 year"));
        $end_month = wp_date('Y-m');
    
        // 生成月份数组
        $current_month = $start_month;
        while ($current_month <= $end_month) {
            $months[] = $current_month;
            $current_month = wp_date('Y-m', strtotime($current_month . ' +1 month'));
        }
    
        // 填充缺失的月份数据
        foreach ($months as $month) {
            $found = false;
            foreach ($data as $item) {
                if ($item['date'] == $month) {
                    $result[] = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $result[] = array('date' => $month, 'total' => 0);
            }
        }
    
        return $result;
    }
    
    public function echarts_wp() {
        global $wpdb;
        $today = wp_date('Y-m-d');
        $yesterday = wp_date('Y-m-d', strtotime('-1 day'));
        
        // 获取最近七天的日期
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = wp_date('Y-m-d', strtotime("-$i days"));
        }
        
        // 获取每日发文数量
        $post_counts = self::get_counts('post', $dates);
        
        // 获取每日评论数量
        $comments_counts = self::get_counts('comment', $dates);
        
        // 获取每日注册用户数量
        $users_counts = self::get_counts('user', $dates);
        
        // 获取每日签到人数
        $sign_ins_counts = self::get_counts('sign_in', $dates);
        
        $data = array(
            'post' => array(
                'title' => '文章数',
                'today' => end($post_counts), //今日发布文章数量
                'yesterday' => $post_counts[count($post_counts) - 2], //昨日文章发布数
                'total' => wp_count_posts('post')->publish //文章总数
            ),
            'comment' => array(
                'title' => '评论数',
                'today' => end($comments_counts),
                'yesterday' => $comments_counts[count($comments_counts) - 2],
                'total' => wp_count_comments()->approved
            ),
            'user' => array(
                'title' => '用户数',
                'today' => end($users_counts),
                'yesterday' => $users_counts[count($users_counts) - 2],
                'total' => count_users()['total_users']
            ),
            'sign_in' => array(
                'title' => '今日签到',
                // 'today' => 
                'yesterday' => $sign_ins_counts[count($sign_ins_counts) - 2],
                'total' => end($sign_ins_counts),
            ),
        );
        
        wp_localize_script('qk-admin', 'showData', array(
            'posts' => $post_counts,
            'comments' => $comments_counts,
            'users' => $users_counts,
            'sign_ins' => $sign_ins_counts,
            'dates' => $dates
        ));
        
        $li = '';
        foreach ($data as $key => $value) {
            $li .= '<li class="card-item">
                <div class="header">' . $value['title'] . '</div>
                <div class="body">
                    <span class="value">' . $value['total'] . '</span>
                </div>
                <div class="footer">
                    <div class="left">
                        <span class="label">今日：</span>
                        '.($value['today'] > 0 ?'
                        <span class="value" style=" color: #ff4684; "><i title="fas fa-caret-up" class="fas fa-caret-up"></i> ' . $value['today'] . '</span>':'<span class="value">--</span>').'
                    </div>
                    <div class="right">
                        <span class="label">昨日：</span>
                        '.($value['yesterday'] > 0 ?'
                        <span class="value" style=" color: #5bbf60; "><i title="fas fa-caret-up" class="fas fa-caret-up"></i> ' . $value['yesterday'] . '</span>':'<span class="value">--</span>').'
                    </div>
                </div>
            </li>';
        }

        echo '<section class="data-info">';
        echo '<ul class="data-card">'.$li.'</ul>';
        // 输出统计数据
        echo '<div class="data-chart">
                <div id="data-echarts" style="width: 100%;height:400px;"></div>
            </div>';
        
        echo '</section>';
    }
    
    public static function get_counts($type, $dates) {
        global $wpdb;
        $counts = array();
        foreach ($dates as $date) {
            switch ($type) {
                case 'post':
                    $args = array(
                        'post_type' => 'post',
                        'posts_per_page' => -1,
                        'date_query' => array(
                            array(
                                'after' => $date,
                                'before' => $date,
                                'inclusive' => true,
                            ),
                        ),
                    );
                    $query = new \WP_Query($args);
                    $counts[] = $query->post_count;
                    break;
                case 'comment':
                    $args = array(
                        'status' => 'approve',
                        'date_query' => array(
                            array(
                                'after' => $date,
                                'before' => $date,
                                'inclusive' => true,
                            ),
                        ),
                    );
                    $comments_count = get_comments($args);
                    $counts[] = count($comments_count);
                    break;
                case 'user':
                    $args = array(
                        'role__not_in' => array('administrator'),
                        'date_query' => array(
                            array(
                                'after' => $date,
                                'before' => $date,
                                'inclusive' => true,
                            ),
                        ),
                    );
                    $counts[] = count(get_users($args));
                    break;
                case 'sign_in':
                    $counts[] = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}qk_sign_in WHERE DATE(sign_in_date) = %s",
                        $date
                    ));
                    break;
            }
        }
        
        return $counts;
    }
    
    //页面加载图标用css和js
    public static function load_enqueue_admin_script($hook){
        
        //判断下，是否在当前页面
        if ('toplevel_page_qk_main_options' != $hook) return;

        //wp_enqueue_script( 'echarts', '//fastly.jsdelivr.net/npm/echarts@5.4.2/dist/echarts.min.js', array(), QK_VERSION , false );
        wp_enqueue_script( 'echarts',QK_THEME_URI.'/Assets/admin/echarts.min.js?v=5.4.2', array(), QK_VERSION, true );
    }
}
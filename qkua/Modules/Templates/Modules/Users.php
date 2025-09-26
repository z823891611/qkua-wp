<?php namespace Qk\Modules\Templates\Modules;

use Qk\Modules\Templates\Modules\Posts;
use Qk\Modules\Common\User;
use Qk\Modules\Templates\Single;

//用户卡片模板
class Users{

    /**
     * 用户模块启动
     *
     * @param array $data 设置数据
     * @param int $i 第几个模块
     *
     * @return string
     */
    public function init($data, $i, $return = false){
        if(empty($data) || empty($data['user_type'])) return;
    
        $type = str_replace('-','_',$data['user_type']);
        
        if(!method_exists(__CLASS__, $type)){ 
            return self::user($data, $i, $return);
            
        };
    
        return self::$type($data, $i, $return);
    }
    
    /**
     * 获取用户列表html
     *
     * @param array $data 设置项数据
     * @param int $i 第几个模块
     * @param bool $return 是否直接返回 li 标签中的 html 代码，用作ajax加载
     *
     * @return string
     */
    public static function user($data,$i,$return = false){
  
        $index = $i;
        
        $i = isset($data['key']) && $data['key'] ? $data['key'] : 'ls'.round(100,999);
        
        $_user_data = self::get_user_data($data);
        $user_data = $_user_data['data'];
        
        //获取文章数据
        $_post_data = self::get_user_post(1,$data);
        $post_data = $_post_data['data'];
        
        $html = '';
        
        foreach ($user_data as $k => $v) {
            
            $_post_data = self::get_user_post($v['id'],$data);
            $post_data = $_post_data['data'];
            $post_list = '';
            
            if($post_data) {
                $post_list = '<div class="user-post-list">';
                foreach ($post_data as $post) {
                    $thumb = qk_get_thumb(array(
                        'url' => $post['thumb'],
                        'width' => 200,
                        'height' => 200,
                        'ratio' => 1
                    ));
                    
                    $post_list .= '
                        <a href="'.$post['link'].'" rel="nofollow" class="thumb-link">
                            <div class="post-thumb">'.qk_get_img(array('src'=>$thumb,'class'=>array('w-h'),'alt'=>$post['title'])).'</div>
                        </a>';
                }
                $post_list .= '</div>';
            }
            
            $html .= '<li class="user-list-item" id="item-'.$v['id'].'">
                <div class="item-in box qk-radius">
                    <div class="user-container">
                        <div class="left qk-flex">
                            <a href="'.$v['link'].'">
                                '.$v['avatar_html'].'
                            </a>
                            <div class="user-info">
                                '.$v['name_html'].'
                                <div class="desc text-ellipsis">'.$v['desc'].'</div>
                            </div>
                        </div>
                        <div class="right qk-flex">
                            <button class="follow qk-flex bg-text">
                                <a href="'.$v['link'].'">Ta的主页</a>
                            </button>
                        </div>
                    </div>
                    '.$post_list.'
                </div>
            </li>';
        }
        
        if($return){
            return array(
                'count'=>$_user_data['count'],
                'index'=>$i,
                'pages'=>$_user_data['pages'],
                'data'=>$html
            );
        }
        return ($data['user_row_count'] != 5 ?
        '<style>
            '.($data['waterfall_show'] == true ? 
            
            '.user-item-'.$i.' ul.qk-waterfall > li{
                width:'.((floor((1/$data['user_row_count'])*10000)/10000)*100).'%;
            }' :
                
            '.user-item-'.$i.' .qk-grid{
                grid-template-columns: repeat('.$data['user_row_count'].', minmax(0, 1fr));
            }
            ').'
            .user-item-'.$i.' .user-post-list {
                grid-template-columns: repeat('.$data['post_row_count'].', minmax(0, 1fr));
            }
        </style>':'').'
        <div id="user-item-'.$i.'" class="'.$data['user_type'].' user-list user-item-'.$i.'" data-key="'.$i.'" data-i="'.$index.'">
            '.self::get_post_modules_top($data).'
            <div class="hidden-line">
                <ul class="qk-grid '.($data['waterfall_show'] == true ? 'qk-waterfall' : '').'">'.$html.'</ul>
            </div>
        </div>';
    }
    
    /**
     * 获取文章模块的顶部内容
     * 
     * @param array $data 文章数据
     *   - post_meta: array 模块元数据
     *   - title: string 模块标题
     *   - desc: string 模块描述
     *   - nav: array 导航列表
     *   - change: string 换一换按钮
     *   - more: string 查看全部按钮链接
     * 
     * @return string 模块顶部内容的 HTML 代码
     */
    public static function get_post_modules_top($data) {
        
        $module_meta = isset($data['module_meta']) && is_array($data['module_meta']) ? $data['module_meta'] : array();
        
        //查看全部
        $more = '';
        if (in_array('more', $module_meta) && !empty($data['module_btn_text']) && !empty($data['module_btn_url'])) {
            $more = '<a class="button see-more no-hover" href="'.$data['module_btn_url'].'" target="_blank">
                        <span>'.$data['module_btn_text'].'</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>';
        }
        
        //模块标题
        $title = '';
        if (in_array('title', $module_meta) && !empty($data['title'])) {
            $title = '<div class="module-info qk-flex">
                        <h2 class="module-title">'.$data['title'].'</h2>
                        '.($more ? '<div class="module-action qk-flex">'.$more.'</div>' : '').'
                    </div>';
        }
        
        //模块描述
        $desc = '';
        if (in_array('desc', $module_meta) && !empty($data['desc'])) {
            $desc = '<div class="module-desc">'.$data['desc'].'</div>';
        }
        
        $html = '';
        if ($title || $desc) {
            $html .= '<div class="modules-top">';
            
            $html .= $desc;
            
            if ($title) {
                $html .= '<div class="module-top-wrapper">';
                
                $html .= $title;
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * 获取用户数据
     * 
     * @param array $data 数据参数，包含以下属性：
        - user_paged (int)：当前页码，默认为1
        - user_count (int)：每页显示的用户数量，默认为10
        - _user_role (string)：用户角色，默认为'subscriber'
        - metas (array)：自定义字段筛选条件，默认为空数组
        - user_order (string)：排序方式，默认为空
        - user__in (array)：需要查询的用户ID数组，默认为空数组
        - user_login (string)：用户登录名，默认为空
        - user_email (string)：用户邮箱，默认为空
        - user_nicename (string)：用户昵称，默认为空
        - user_registered (string)：用户注册时间，默认为空
        
     * @return array 返回用户数据，包含以下属性：
        - count (int)：符合查询条件的用户总数
        - pages (int)：符合查询条件的用户总页数
        - data (array)：用户数据数组
     */
    public static function get_user_data($data){
        
        $paged = isset($data['user_paged']) ? (int)$data['user_paged'] : 1;
        //偏移量 开始
        $offset = ($paged -1)*(int)$data['user_count'];
        
        //用户角色
        $role = isset($data['_user_role']) && $data['_user_role'] ? esc_attr($data['_user_role']) : 'subscriber';
        
        $args = array(
            //'role'           => $role,
            'number'         => (int)$data['user_count'] ? (int)$data['user_count'] : 10, //查询数量
            'offset'         => $offset,
            //'orderby'        => 'registered',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation'   => 'AND',
            ),
            'fields' => array('ID','display_name')
        );
        
        //如果自定义字段筛选
        if(isset($data['metas']) && !empty($data['metas'])){
            foreach($data['metas'] as $k => $v){
                array_push($args['meta_query'],array(
                    'key'     => $k,
                    'value'   => $v,
                    'compare' => '=',
                ));
            }
            unset($v);
        }

        //排序
        if(isset($data['user_order']) && !empty($data['user_order'])){
            switch($data['user_order']){
                case 'fans':
                    $args['meta_key'] = 'qk_fans'; //粉丝
                    $args['orderby'] = 'meta_value';
                    $args['meta_value'] = 'i'; //值为序列化，序列化里有i表示有值
                    $args['meta_compare'] = 'LIKE';
                    break;
                case 'post':
                    $args['orderby'] = 'post_count'; //文章数量
                    break;
                case 'lv':
                    $args['meta_key'] = 'qk_lv_exp'; //用户等级经验
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_value'] = '0'; 
                    $args['meta_compare'] = '>';
                    break;
                case 'vip':
                    $args['meta_key'] = 'qk_vip'; //会员等级
                    $args['orderby'] = 'meta_value';
                    break;
                case 'money':
                    $args['meta_key'] = 'qk_money'; //用户余额
                    $args['orderby'] = 'meta_value';
                    $args['meta_value'] = '0'; 
                    $args['meta_compare'] = '>';
                    break;
                case 'credit':
                    $args['meta_key'] = 'qk_credit'; //用户积分
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_value'] = '0'; 
                    $args['meta_compare'] = '>';
                    break;
            }
        }
        
        //如果存在用户ID
        if(isset($data['user__in']) && !empty($data['user__in'])){
            $args['include'] = $data['user__in'];
        }
        
        //如果存在搜索用户
        if(isset($data['search']) && !empty($data['search'])){
            $args['search'] = '*' . $data['search'] . '*';
        }
        
        //如果存在用户登录名
        if(isset($data['user_login']) && !empty($data['user_login'])){
            $args['search'] = '*' . $data['user_login'] . '*';
        }
        
        //如果存在用户邮箱
        if(isset($data['user_email']) && !empty($data['user_email'])){
            $args['search'] = '*' . $data['user_email'] . '*';
        }
        
        //如果存在用户昵称
        if(isset($data['user_nicename']) && !empty($data['user_nicename'])){
            $args['search'] = '*' . $data['user_nicename'] . '*';
        }
        
        //如果存在用户注册时间
        if(isset($data['user_registered']) && !empty($data['user_registered'])){
            $args['date_query'] = array(
                array(
                    'after' => $data['user_registered'],
                    'inclusive' => true,
                ),
            );
        }
        
        $user_query = new \WP_User_Query( $args );

        $user_data = array();
        $_pages = 1;
        $_count = 0;
        
        if ( ! empty( $user_query->get_results() ) ) {

            $_pages = ceil($user_query->get_total() / $args['number']);
            $_count = $user_query->get_total();
            
            $current_user_id = get_current_user_id();
            $follow = get_user_meta($current_user_id,'qk_follow',true);
            $follow = is_array($follow) ? $follow : array();
            
            foreach ( $user_query->get_results() as $user ) {
                
                $public_data = User::get_user_public_data($user->ID);
                $public_data['is_follow'] = in_array($user->ID,$follow) ? true : false;
                
                $user_data[] = $public_data;
            }
        }
        
        unset($user_query);
        return array(
            'count'=>$_count,
            'pages'=>$_pages,
            'data'=>$user_data
        );
    }
    
    /**
     * 获取用户的文章
     *
     * @param array $user_id 用户ID
     * @return void
     */
    public static function get_user_post($user_id,$data = array()){
        $args = array(
            '_post_type' => array('post','video'),
            'post_order'=>$data['post_order'],
            'post_count' => (int)$data['post_count'] ? (int)$data['post_count'] : 3,
            'author__in' => array($user_id),
            'get_post_meta' => false
        );
        
        //获取文章数据
        $post_data = Posts::get_post_data($args);
        
        return $post_data;
    }
}
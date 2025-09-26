<?php namespace Qk\Modules\Common;

class Verify {
    
    /**
     * 获取认证信息
     *
     * @return array 认证信息数组
     */
    public static function get_verify_info(){

        $verify_open = qk_get_option('verify_open');

        if(!$verify_open) return array('error'=>'认证服务已关闭，无法申请认证');

        $user_id = get_current_user_id();
        
        //if(!$user_id) return array('error'=>'请先登录');
        
        $verify_group = qk_get_option('verify_group');
        
        if(empty($verify_group)) return array('error'=>'请先在后台设置认证相关数据');
        
        foreach ($verify_group as $key => &$value) {
            $value['conditions'] = isset($value['conditions']) ? $value['conditions'] : array(); 
            foreach ($value['conditions'] as $k => &$v){
                $v['allow'] = self::check_verify_condition($user_id,$v);
            }
        }
        
        return $verify_group;
        
    }
    
    /**
     * 获取用户认证信息
     *
     * @return array 认证信息数组
     */
    public static function get_user_verify_info(){
        
        $user_id = get_current_user_id();

        // if(!$user_id) return array('error'=>'请先登录');
        
       //获取当前用户的认证数据
        $data = self::get_verify_data($user_id);
        
        return $data;
        
    }
    
    public static function submit_verify($data){
        
        $verify_open = qk_get_option('verify_open');
        
        if(!$verify_open) return array('error'=>'认证服务已关闭，无法申请认证');
        
        $user_id = get_current_user_id();

        if(!$user_id) return array('error'=>'请先登录');
        
        $data = apply_filters('qk_submit_verify_before',$user_id,$data);
        if(isset($data['error'])) return array('error' => $data['error']);
        
        //认证信息数组
        $verify_group = qk_get_option('verify_group');
        if(empty($verify_group)) return array('error'=>'请先在后台设置认证相关数据');
        
        //检查认证类型
        $index = array_search($data['type'], array_column($verify_group, 'type'));
        
        if($index === false || $index != $data['index']) return array('error'=>'不存在此认证类型');
        
        $verify = $verify_group[$index];
        
        //检查认证基础认证条件是否通过
        if(!empty($verify['conditions']) && is_array($verify['conditions'])) {
            foreach ($verify['conditions'] as $key => $value) {
                if(!self::check_verify_condition($user_id,$value)) return array('error'=>sprintf('你存在基础条件%s不通过，无法申请认证',$value['name']));
            }
        }
        
        //检查认证标题名称
        $data['title'] = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',wp_strip_all_tags($data['title']))));
        if(empty($data['title'])) return array('error' => '请填写认证标题');
        if(qkGetStrLen($data['title']) > 30) return array('error' => '认证标题太长，请限制在1-30个字符之内');
        
        //检查参数
        $document = self::check_verify_document($verify,$data);
        if(isset($document['error'])) return $document;

        // 0 为人工审核信息 1 为自动审核信息
        $verify_check = !!(int)$verify['verify_check'];
        
        //准备参数
        $args = array(
            'user_id' => $user_id,
            'type' => $data['type'],
            'title' => $data['title'],
            'verified' => 0, //是否实名
            'status' => $verify_check ? 1 : 0, //0为待审核 1为审核通过 2审核未通过 3已支付
            'date' => current_time('mysql'),
            'data' => !empty($document) ? maybe_serialize($document) : ''
        );
        
        if(self::update_verify_data($args)) {
            
            do_action('qk_submit_verify_success',$user_id,$data);
            
            if($verify_check) {
                
                update_user_meta($user_id,'qk_verify',$data['title']);
                update_user_meta($user_id,'qk_verify_type',$data['type']);
                
                do_action('qk_submit_verify_check_success',$user_id,$data);
                
                return 'success';
            }
            
            return 'pending';
        }
        
        return array('error'=>'服务错误请重新尝试');
    }
    
    /**
     * 获取用户认证数据
     *
     * @param int $user_id 用户ID
     * @return array 用户认证数据数组
     */
    public static function get_verify_data($user_id){
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_verify';

        $res = $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE user_id = %d
                ",
                $user_id
        ),ARRAY_A);
        
        $default = array(
            'user_id' => 0,
            'type' => '',
            'title' => '',
            'money' => 0,
            'credit' => 0,
            // 'verified' => 0,
            'status' => 0, 
            'date' => current_time('mysql'),
            'data' => '',
            'step' => 1, //步骤
            // 'value' => '',
            'opinion' => ''
        );
        
        if(!$res){
            return $default;
        }
        
        // 将合并默认数组和结果数组中与默认数组键相同的元素，并返回合并后的数组
        $data = array_merge($default, array_intersect_key($res, $default));
        
        $data['data'] = maybe_unserialize($data['data']);
        $data['data'] = empty($data['data']) ? new \stdClass : $data['data'];
        
        switch ($data['status']) {
            case '0':
                $data['step'] = 3;
                break;
            case '1':
            case '2':
                $data['step'] = 4;
                break;
        }
        
        return array_map(function($value) {
            return is_numeric($value) ? (int)$value : $value;
        }, $data);
    }
    
    public static function update_verify_data($data){
        
        if(empty($data['type'])) return false;

        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_verify';
        
        $verify_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE user_id = %d", $data['user_id']));

        if (!empty($verify_id)) {
            
            $where = array(
                'id' => $verify_id,
                'user_id' => $data['user_id'],
            );
            
            if($wpdb->update( $table_name , $data , $where )) {
                return true;
            };
            
        }else {
            
            $default = array(
                'user_id' => 0,
                'type' => '',
                'title' => '',
                'money' => 0,
                'credit' => 0,
                'verified' => 0,
                'status' => 0, 
                'date' => current_time('mysql'),
                'data' => '',
                'value' => '',
                'opinion' => ''
            );
    
            $args = wp_parse_args( $data ,$default );
            
            $format = array(
                '%d', // user_id
                '%s', // type
                '%s', // title
                '%d', // money
                '%d', // credit
                '%d', // verified
                '%d', // status
                '%s',  // date
                '%s', // data
                '%s', // value
                '%s' // opinion
            );
            
            if($wpdb->insert( $table_name, $args, $format)) {
                return true;
            };
        }
        
        return false;
    }
    
    /**
     * 检查认证条件是否完成
     *
     * @param int $user_id 用户ID
     * @param array $condition 认证条件数组
     * @return bool 是否通过认证条件
     */
    public static function check_verify_condition($user_id, $condition) {
        $completed_count = (int)apply_filters('qk_user_verify_condition_value',$user_id,$condition['key']);
        return ($completed_count >= (int)$condition['value'] && $completed_count !== 0);
    }
    
    /**
     * 检查认证条件是否完成
     *
     * @param int $user_id 用户ID
     * @param array $condition 认证条件数组
     * @return bool 是否通过认证条件
     */
    public static function check_verify_document($verify, $data) {
        
        $default = array(  
            'title' => '', // 认证信息  
            'company' => '', // 公司名称  
            'credit_code' => '', // 信用代码  
            'business_license' => '', // 营业执照  
            'business_auth' => '', // 认证申请公函  
            'official_site' => '', // 官方网站  
            'supplement' => '', // 补充资料  
            'operator' => '', // 运营者  
            'email' => '',  
            'telephone' => '', // 运营者手机号  
            'id_card' => '', // 身份证号  
            'idcard_hand' => '', // 手持身份证  
            'idcard_front' => '', // 身份证正面  
            'idcard_verso' => '', // 身份证背面  
        );
        
        $data = array_merge($default, array_intersect_key($data, $default));
        
        if(!empty($verify['verify_info_types']) && is_array($verify['verify_info_types'])) {
            $types = $verify['verify_info_types'];
            
            foreach ($types as $value) {
                if($value == 'personal') {
                    $data['operator'] = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',wp_strip_all_tags($data['operator']))));
                    
                    if(qkGetStrLen($data['operator']) > 6 || empty($data['operator'])) return array('error' => '姓名应在2位到6位之间');
                    
                    if(!is_email($data['email']) && !empty($data['email'])) return array('error' => '请输入正确的邮箱地址');
                    $data['email'] = sanitize_email($data['email']);
                    
                    if(preg_match("/^1[3456789]{1}\d{9}$/", $data['telephone'])) return array('error' => '请输入正确的手机号码');
                    
                    // if(!self::validation_filter_id_card($data['id_card'])) return array('error' => '身份证号码错误');
                    
                    $data['idcard_front'] = esc_url($data['idcard_front']);
                    if(empty($data['idcard_front']) || !attachment_url_to_postid($data['idcard_front']))  return array('error' => '请上传身份证正面照');
                    
                    $data['idcard_verso'] = esc_url($data['idcard_verso']);
                    if(empty($data['idcard_verso']) || !attachment_url_to_postid($data['idcard_verso']))  return array('error' => '请上传身份证背面照');
                    
                    $data['idcard_hand'] = esc_url($data['idcard_hand']);
                    if(empty($data['idcard_hand']) || !attachment_url_to_postid($data['idcard_hand']))  return array('error' => '请上传手持身份证照');
                    
                    
                }elseif ($value == 'official') {
                    $data['company'] = sanitize_text_field(wp_unslash(str_replace(array('{{','}}'),'',wp_strip_all_tags($data['company']))));
                    if(empty($data['company'])) return array('error' => '请输入公司名称');
                    
                    if(!preg_match("/^[a-z\d]*$/i",$data['credit_code']) || empty($data['credit_code'])) return array('error' => '请输入正确的统一社会信用代码');
                    
                    $data['business_license'] = esc_url($data['business_license']);
                    if(empty($data['business_license']) || !attachment_url_to_postid($data['business_license']))  return array('error' => '请上传营业执照');
                    
                    $data['business_auth'] = esc_url($data['business_auth']);
                    if(empty($data['business_auth']) || !attachment_url_to_postid($data['business_auth']))  return array('error' => '请上传认证申请公函');
                    
                    $data['supplement'] = esc_url(sanitize_text_field(trim($data['supplement'])));
                    $data['official_site'] = esc_url(sanitize_text_field(trim($data['official_site'])));
                }
            }
            
            //过滤空值
            $data = array_filter($data);
            ksort($data);
            
            return $data;
        }
        
        return '';
        
    }
    
    public static function validation_filter_id_card($id){
        // return true;
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        
        $arr_split = [];
        if(!preg_match($regx, $id)){
            return false;
        }
        
        if(15==strlen($id)){
            // 检查15位
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            
            @preg_match($regx, $id, $arr_split);
            // 检查生日日期是否正确
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];

            if($arr_split[2] < 71 || $arr_split[3] > 12 || $arr_split[4] > 31) return false;

            if(!wp_strtotime($dtm_birth)){
                return false;
            }else{
                return true;
            }
        }else{
            
            // 检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            
            if($arr_split[2] < 1971 || $arr_split[3] > 12 || $arr_split[4] > 31) return false;
            
            //检查生日日期是否正确
            if(!wp_strtotime($dtm_birth)) {
                return false;
            }else{
                
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
                $arr_ch = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
                $sign = 0;
                
                for ( $i = 0; $i < 17; $i++ ){
                    $b = (int) $id[$i];
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                
                if ($val_num != substr($id,17, 1)){
                    return false;
                }else{
                    return true;
                }
            }
        }
    }
}
<?php namespace Qk\Modules\Common;

//ip属地
class IpLocation {
    public static function get($ip){
        
        $type = qk_get_option('ip_location_type');

        if(!method_exists(__CLASS__,$type)) return  array('error'=>'位置服务商不存在');
        
        return self::$type($ip);
    }
    
    /**
     * 通过腾讯位置服务获取IP归属地信息
     * @param string $ip IP地址
     * 
     * @return array 包含IP归属地信息的数组，或者包含错误信息的数组
     */
    public function tencent($ip){
        $tencent = qk_get_option('tencent_ip_location');
        
        if (empty($tencent['app_key'])) {
            return array('error' => '请检查腾讯位置服务设置，缺失app_key参数');
        }
        
        //构造请求参数
        $params = array(
            'ip' => trim($ip),
            'key' => trim($tencent['app_key']),
        );
        
        if(!empty($tencent['secret_key'])) {
            $params['sig'] = md5('/ws/location/v1/ip?'.http_build_query($params).trim($tencent['secret_key']));
        }
        
        $api = 'https://apis.map.qq.com/ws/location/v1/ip?'.http_build_query($params);
        
        $res = wp_remote_get($api);
        
        if(is_wp_error($res)){
            return array('error'=>'网络错误，请稍后再试');
        }
        
        $res = json_decode($res['body'],true);
        
        if((int)$res['status'] === 0){
            
            $ad_info = $res['result']['ad_info'];
            
            $data = array(
                'ip'       => $ip,
                'nation'   => '', //国家
                'province' => '', //省份
                'city'     => '', //城市
                'district' => '', //区域
            );
            
            //return wp_parse_args($ad_info, $data);
            // 将默认数组和提取出的参数合并
            return array_merge($data, array_intersect_key($ad_info, $data));
            
        } else {
            return array('error'=>$res['message']);
        }
    }
    
    /**
     * 通过高德位置服务获取IP归属地信息
     * @param string $ip IP地址
     * 
     * @return array 包含IP归属地信息的数组，或者包含错误信息的数组
     */
    public function amap($ip){
        $amap = qk_get_option('amap_ip_location');
        
        if (empty($amap['app_key'])) {
            return array('error' => '请检查高德位置服务设置，缺失key参数');
        }
        
        //构造请求参数
        $params = array(
            'ip' => trim($ip),
            'key' => trim($amap['app_key']),
        );
        
        if(!empty($amap['secret_key'])) {
            $params['sig'] = md5(http_build_query($params).trim($amap['secret_key']));
        }
        
        $api = 'https://restapi.amap.com/v3/ip?'.http_build_query($params);
        
        $res = wp_remote_get($api);
        
        if(is_wp_error($res)){
            return array('error'=>'网络错误，请稍后再试');
        }
        
        $res = json_decode($res['body'],true);

        if((int)$res['status'] === 1){
            
            if(empty($res['province'])){
                return array('error'=>'高德定位失败非法IP或国外IP');
            }
            
            $data = array(
                'ip'       => $ip,
                'nation'   => '中国', //国家
                'province' => '', //省份
                'city'     => '', //城市
                'district' => '', //区域
            );
            
            // 将默认数组和提取出的参数合并
            return array_merge($data, array_intersect_key($res, $data));
            
        } else {
            return array('error'=>'错误信息：'.$res['info'].'，状态码：'.$res['infocode']);
        }
    }
    
    /**
     * 通过太平洋公共接口获取IP归属地信息
     * @param string $ip IP地址
     * 
     * @return array 包含IP归属地信息的数组，或者包含错误信息的数组
     */
    public function pconline($ip){
        
        $res = wp_remote_get('https://whois.pconline.com.cn/ipJson.jsp?json=true&ip='.trim($ip));
        
        if(is_wp_error($res)){
            return array('error'=>'网络错误，请稍后再试');
        }
        
        $body = wp_remote_retrieve_body($res); // 获取响应的主体内容
        $res = json_decode( iconv("GBK", "UTF-8//IGNORE", $body), true); // 解析JSON数据
        
        if(!empty($res) && empty($res['err'])){
            
            $data = array(
                'ip'       => $ip,
                'nation'   => $res['addr'], //国家
                'province' => $res['pro'], //省份
                'city'     => $res['city'], //城市
                'district' => $res['region'], //区域
            );
            
            // 将默认数组和提取出的参数合并
            return $data;
            
        } else {
            return array('error'=>'定位失败');
        }
    }
    
    /**
     * 生成位置字符串
     *
     * 根据提供的位置信息数据生成位置字符串
     *
     * @param array $data 包含位置信息的数组
     * @return string 位置字符串
     */
    public static function build_location($data) {
        
        $open = qk_get_option('user_ip_location_show');
        
        if(empty($open) || !$open) return '';
        
        $format = qk_get_option('ip_location_format');
        $format = !empty($format) ? $format : 'p';
        $location = '';
        
        if(!empty($data) && is_array($data)) {
            switch ( $format ) {
                case 'npc':
                    $location = $data['nation'] . $data['province'] . $data['city'];
                    break;
                case 'np':
                    $location = $data['nation'] . $data['province'];
                    break;
                case 'pc':
                    $location = $data['province'] . $data['city'];
                    break;
                case 'p':
                    $location = $data['province'];
                    break;
                default:
                    break;
            }
            
            if(!$location && in_array($format,array('pc','p','c'))){
                if(!empty($data['nation'])) {
                    $location = $data['nation'];
                }
            }
        }
        
        if(!$location) {
            $location = '未知';
        }
        
        return str_replace(array('省','自治区', '市', '特别行政区'), '', implode('', array_unique(preg_split('//u', $location, -1, PREG_SPLIT_NO_EMPTY))));
    }
}
<?php namespace Qk\Modules\Common;
class Invite {
    // 检查邀请码
    public static function checkInviteCode($inviteCode) {
        $inviteCode = strtoupper(trim($inviteCode," \t\n\r\0\x0B\xC2\xA0"));
        
        $invite_type = qk_get_option('invite_code_type');
        
        if ($invite_type == 0 && $inviteCode == '') {
            return false;
        }
        
        // 邀请码注册未开启但用户已填写邀请码
        if ($invite_type == 0 && $inviteCode != '') {
            return array('error' => '当前不允许使用邀请码');
        }
        
        if ($invite_type == 1 && $inviteCode == '') {
            return array('error' => '请使用输入邀请码');
        }
        
        if ($invite_type == 2 && $inviteCode == '') {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_card';

        $res = $wpdb->get_row(
            $wpdb->prepare("
                SELECT * FROM $table_name
                WHERE card_code = %s
                ",
                $inviteCode
        ), ARRAY_A);
        
        if (empty($res)) {
            return array('error' => '邀请码不存在');
        } elseif ((int)$res['status'] === 1) {
            return array('error' => '邀请码已被使用');
        } elseif (empty($res['type']) || $res['type'] !== 'invite') {
            return array('error' => '邀请码类型错误');
        }
        
        return $res;
    }

    // 使用邀请码
    public static function useInviteCode($user_id,$inviteCode) {
        $inviteCode = trim($inviteCode);
        
        // 再次检查邀请码是否有效
        $inviteInfo = self::checkInviteCode($inviteCode);
        if (isset($inviteInfo['error'])) {
            return $inviteInfo; // 邀请码无效，使用失败
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'qk_card';
        
        if($wpdb->update(
            $table_name, 
            array( 
                'status' => 1,
                'user_id' => $user_id
            ), 
            array( 'id' => $inviteInfo['id'] ),
            array( 
                '%d',
                '%d'
            ), 
            array( '%d' ) 
        )){
            return apply_filters('qk_invite_code_used',$user_id,$inviteInfo); // 邀请码成功使用
        }
        
        return array('error'=>'网络错误，请稍后重试');
    }
}
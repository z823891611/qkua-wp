<?php namespace Qk\Modules\Common;

//短信
class Sms {
    public static function send($phoneNumber,$code){
        
        $sms_type = qk_get_option('sms_type');

        if(!method_exists(__CLASS__,$sms_type)) return array('error'=>'短信服务商不存在');
        
        return self::$sms_type($phoneNumber,$code);
    }
    
    /**
     * 阿里云
     *
     * @param string $phoneNumber 不带国家码的手机号
     * @param array  $code      验证码
     * 
     * @return string 
     */
    public static function aliyun($phoneNumber,$code){
        
        $aliyun = qk_get_option('aliyun_sms');
        
        if (empty($aliyun['key_id']) || empty($aliyun['key_secret']) || empty($aliyun['sign_name']) || empty($aliyun['template_code'])) {
            return array('error' => '请检查阿里云短信设置，缺失参数');
        }
        
        $params = array(
            'Action'   => 'SendSms',
            'Version'  => '2017-05-25',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => uniqid(mt_rand(0,0xffff), true),
            'SignatureVersion' => '1.0',
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Format' => 'JSON',
        );
        
        $params['AccessKeyId'] = $aliyun['key_id'];
        
        //必填: 短信接收号码
        $params['PhoneNumbers'] = $phoneNumber;

        //必填: 短信签名，应严格按'签名名称'填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params['SignName'] = $aliyun['sign_name'];

        //必填: 短信模板Code，应严格按'模板CODE'填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params['TemplateCode'] = $aliyun['template_code'];

        //可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = json_encode(array(
            'code' => $code,
        ));
        
        ksort($params);
        
        $sortedQueryStringTmp = "";
        foreach ($params as $key => $value) {
            $sortedQueryStringTmp .= "&" . self::aliyunEncode($key) . "=" . self::aliyunEncode($value);
        }
        
        $stringToSign = "GET&%2F&" . self::aliyunEncode(substr($sortedQueryStringTmp, 1));
        
        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $aliyun['key_secret'] . "&",true));
        
        //签名
        $params ['Signature'] = $sign;
        
        $url = 'http://dysmsapi.aliyuncs.com/?' . http_build_query ( $params );
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        
        $result = json_decode ( $result, true );

        if (isset ( $result ['Code'] ) && $result ['Code'] == 'OK') {
            return '验证码已发送至您的手机，注意查收';
        }else{
            return array('error' => $result['Message'].'。错误代码：'.$result ['Code']);
        }
    }
    
    private static function aliyunEncode($str){
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }
    
    /**
     * 腾讯云指定模板单发
     *
     * @param string $phoneNumber 不带国家码的手机号
     * @param array  $msg      模板参数，模板 {1} 内容
     * 
     * @return string 
     */
    public function tencent($phoneNumber, $msg){
        $tencent = qk_get_option('tencent_sms');
        
        if (empty($tencent['app_id']) || empty($tencent['app_key']) || empty($tencent['sign_name']) || empty($tencent['template_id'])) {
            return array('error' => '请检查腾讯云短信设置，缺失参数');
        }
        
        // 短信应用 SDK AppID
        $appid = $tencent['app_id'];
        // 短信应用 SDK AppKey
        $appkey = $tencent['app_key'];
        // 签名参数
        $sign = $tencent['sign_name'];
        // 短信模板 ID
        $template_id = $tencent['template_id'];
        
        $random = rand(100000, 999999);
        $curTime = time();
        $wholeUrl =  'https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid=' . $appid . '&random=' . $random;

        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = '86'; //国家码，如 86 为中国
        $tel->mobile = ''.$phoneNumber;

        $data->tel = $tel;
        $data->sig = hash('sha256', 'appkey='.$appkey.'&random='.$random.'&time='.$curTime.'&mobile='.$phoneNumber);
        $data->tpl_id = $template_id;
        $data->params = array($msg,5); //验证码、时效
        $data->sign = $sign;
        $data->time = $curTime;
        $data->extend = ''; //扩展码，可填空串
        $data->ext = ''; //服务端原样返回的参数，可填空串

        return self::tencentSendCurlPost($wholeUrl, $data);
    }
    
    public static function tencentSendCurlPost($url, $dataObj){
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataObj));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);  //超时10秒
        
        $ret = curl_exec($curl);
        if (false == $ret) {
            curl_close($curl);
            return array('error' => curl_error($curl));
        } else {
            $rsp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                curl_close($curl);
                return array('error' => $rsp.' '.curl_error($curl));
            } else {
                    $result = $ret;
            }
        }

        curl_close($curl);
    
        $result = json_decode ( $result, true );
        if($result['result'] == 0) return '验证码已发送至您的手机，注意查收';

        return array('error' => $result['errmsg']);
    }
}
<?php
use Qk\Modules\Common\Pay;

/**
 * 通知回调地址
 * 
 * */

if(!empty($_POST)){
    $data = $_POST;
}elseif(!empty($_GET)){
    $data = $_GET;
}else{
    $data = file_get_contents('php://input');
}

$res = Pay::pay_notify('post',$data);

if(isset($res['error']) || empty($res)){
    echo 'fail';
}else{
    echo 'success';
}
exit;
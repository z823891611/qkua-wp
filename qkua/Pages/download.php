<?php

$token = isset($_GET['token']) ? $_GET['token'] : '';
if($token){
    //获取下载地址
    $url = QK\Modules\Common\Post::download_file($_GET['token']);

    if(isset($url['error'])){
        echo $url['error'];
        exit;
    } 
    // var_dump($url);
    // exit;

    echo '<script language="JavaScript">  
        window.location.href = "'.$url.'"
    </script>';
    exit;
}

?>

<!doctype html>
<html <?php language_attributes(); ?> class="avgrund-ready">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="Cache-Control" content="no-transform" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <meta name="renderer" content="webkit"/>
    <meta name="force-rendering" content="webkit"/>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1"/>
    <link rel="profile" href="http://gmpg.org/xfn/11">
        
    <?php wp_head();?>

</head>
<?php
    $post_id = isset($_GET['post_id']) && $_GET['post_id'] ? (int)$_GET['post_id'] : 0;
    $index = isset($_GET['index']) && $_GET['index'] ? (int)$_GET['index'] : 0;
    
    $download_data = QK\Modules\Common\Post::get_download_page_data($post_id,$index);
    
    if(isset($download_data['error'])){
        wp_die($download_data['error'],'QK主题提示');
    }

    if(!isset($download_data['can']['allow']) || !$download_data['can']['allow']) {
        wp_die('您无权下载此资源','QK主题提示');
    }
    
?>

<body <?php body_class(); ?>>

<div id="page" class="site">
    <div id="content" class="site-content">
        <div class="content-wrapper">
            <div class="download-page wrapper box" style="--wrapper-width: 600px;">
                <?php echo QK\Modules\Templates\Header::logo() ?>
                <h1><?php echo $download_data['title']; ?></h1>
                
                <div class="attrs">
                    <?php foreach ($download_data['attrs'] as $k => $v) {
                        echo '
                            <div class="item">
                                <span>'.$v['key'].'</span>
                                <span>'.$v['value'].'</span>
                            </div>
                        ';
                    } ?>
                </div>
                <div id="tabs" class="tabs">
                    <ul class="tabs-nav">
                        
                        <?php foreach ($download_data['links'] as $k => $v) {
                            echo '<li'.($k == 0 ? ' class="active"': '').'>'.$v['name'].'</li>';
                        } ?>
                        
                        <div class="active-bar"></div>
                    </ul>
                    <div class="tabs-content">
                        <?php foreach ($download_data['links'] as $k => $v) {
                            echo '
                                <div class="tab-pane'.($k == 0 ? ' active': '').'">
                                    <div class="attr">
                                        '.($v['tq'] ? '<div class="tq"><span>提取码：</span><span class="bg-text">'.$v['tq'].'</span></div>':'').'
                                        '.($v['jy'] ? '<div class="jy"><span>解压码：</span><span class="bg-text">'.$v['jy'].'</span></div>':'').'
                                    </div>
                                    <a class="button no-hover" href="?token='.$v['token'].'"><i class="ri-download-fill" style="font-size: 16px; line-height: 16px; margin-right: 6px;"></i>下载</a>
                                </div>
                            ';
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php
/**
 * Template name: 转存js页面（QKUA主题）
 * Description:  由wordpress主题QKUA主题的作者开发，用于js转存
 *
 * Author: QKUA主题
 * Author URL:https://www.qkua.com/
 * Date: 2023-06-05
 * Contact: 946046483@qq.com
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 非登录用户禁止访问
    if (!is_user_logged_in()) {
        echo '无法上传，请先登录后';
        exit;
    }

    $jsLink = $_POST['jsLink'];
    if (!filter_var($jsLink, FILTER_VALIDATE_URL)) {
        echo '无法无效的JS链接';
        exit;
    }

    $jsFileName = basename(parse_url($jsLink, PHP_URL_PATH));
    $jsDirectory = ABSPATH . 'say/js/';

    if (!file_exists($jsDirectory)) {
        if (!mkdir($jsDirectory, 0755, true)) {
            echo '无法创建JS文件夹';
            exit;
        }
    }

    $jsFilePath = $jsDirectory . $jsFileName;
    $postId = get_the_ID();
    $postMeta = get_post_meta($postId, 'downloaded_js_files', true);

    if (file_exists($jsFilePath)) {
        if (is_array($postMeta) && in_array($jsFileName, $postMeta)) {
            echo '该JS文件已经下载过';
            exit;
        }

        $postMeta[] = $jsFileName;
        update_post_meta($postId, 'downloaded_js_files', $postMeta);
    } else {
        $jsContent = file_get_contents($jsLink);

        if ($jsContent === false) {
            echo '无法下载JS文件';
            exit;
        }

        $result = file_put_contents($jsFilePath, $jsContent);

        if ($result === false) {
            echo '无法保存JS文件';
            exit;
        }

        if (!is_array($postMeta)) {
            $postMeta = array();
        }

        $postMeta[] = $jsFileName;
        update_post_meta($postId, 'downloaded_js_files', $postMeta);
    }

    $newJsLink = site_url('/say/js/' . $jsFileName);
    echo $newJsLink;
    exit;
}
get_header();
?>

<div class="js-downloader wrapper">
    <form id="js-form">
        <label for="js-link">请输入JS链接：</label>
        <input type="text" id="js-link" name="jsLink">
        <button type="submit">提交</button>
    </form>

    <div id="js-container"></div>

    <?php 
    $postId = get_the_ID();
    $postMeta = get_post_meta($postId, 'downloaded_js_files', true);
    if (is_array($postMeta) && count($postMeta) > 0) {
    ?>
    <table>
        <thead>
            <tr>
                <th>文件名</th>
                <th>转存时间</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($postMeta as $jsFileName) {
                $jsFilePath = ABSPATH . 'say/js/'. $jsFileName;
                if(!file_exists( $jsFilePath)) continue;
                $jsFileTime = date('Y-m-d', filemtime($jsFilePath));
            ?>
            <tr>
                <td><?php echo site_url('/say/js/' . $jsFileName); ?></td>
                <td><?php echo $jsFileTime; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php } ?>
</div>

<style>
.js-downloader form {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.js-downloader label {
  margin-right: 10px;
}

.js-downloader input[type="text"] {
  flex: 1;
}

.js-downloader button[type="submit"] {
  margin-left: 10px;
}

.js-downloader p {
  color: red;
}

.js-downloader table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 20px;
}

.js-downloader th,
.js-downloader td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}

.js-downloader th {
  background-color: #f2f2f2;
  font-weight: bold;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#js-form').submit(function(event) {
            event.preventDefault();
            var jsLink = $('#js-link').val();
            $.ajax({
                url: '<?php echo get_permalink(); ?> ',
                type : 'post',
                data: {
                    jsLink: jsLink
                },
                success: function(response) {
                    if (response.startsWith('无法') || response == '该JS文件已经下载过') {
                        $('#js-container').html('<p>' + response + '</p>');
                    } else {
                        $('#js-container').html('<p style="color: green;">上传成功！转存链接：' + response + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#js-container').html('<p>提交表单失败：' + error + '</p>');
                }
            });
        });
    });
</script>

<?php 
get_footer();
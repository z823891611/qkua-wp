<?php
/**
 * Plugin Name: Steam Game Scraper (七夸主题专用)
 * Description: 采集Steam：LOGO下简介、封面、价格、发行日期、中英文名、商店标签（过滤“色情/暴力/血腥”）；经典编辑器一键插入，并自动写入文章标题与标签；黑底卡片 + 7个 SteamDB 链接。
 * Version: 4.5
 * Author: Doubao
 */

if (!defined('ABSPATH')) { exit; }

/** 资源加载：后台JS + 前后端CSS */
function steam_scraper_enqueue_assets($hook){
    $is_editor = is_admin() && in_array($hook, array('post.php','post-new.php'));
    if ($is_editor) {
        wp_enqueue_script('steam-scraper-script', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), '4.5', true);
        wp_localize_script('steam-scraper-script', 'SteamScraper', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('steam_scraper_nonce')
        ));
    }
    wp_enqueue_style('steam-scraper-style', plugin_dir_url(__FILE__) . 'css/admin.css', array(), '4.5');
}
add_action('admin_enqueue_scripts', 'steam_scraper_enqueue_assets');
add_action('wp_enqueue_scripts', 'steam_scraper_enqueue_assets');

/** 经典编辑器：按钮加到“添加媒体”旁 */
function steam_scraper_media_button(){
    echo '<button type="button" id="steam-scraper-btn" class="button" style="margin-left:6px;">📥 采集Steam游戏信息</button>';
}
add_action('media_buttons', 'steam_scraper_media_button', 15);

/** 多字节安全截断（用于简介） */
function steam_scraper_mb_trim($text, $max = 220){
    if (!function_exists('mb_strlen')) {
        return (strlen($text) > $max) ? (substr($text, 0, $max) . '…') : $text;
    }
    $text = trim($text);
    if (mb_strlen($text, 'UTF-8') > $max) {
        return mb_substr($text, 0, $max, 'UTF-8') . '…';
    }
    return $text;
}

/** 发行日期标准化为 YYYY.MM.DD */
function steam_scraper_norm_date($date_str){
    if (empty($date_str)) return '';
    $s = trim(preg_replace('/\s+/u', ' ', $date_str));
    $ts = strtotime($s);
    if ($ts) return date('Y.m.d', $ts);
    if (preg_match('/(\d{4}).{0,3}(\d{1,2}).{0,3}(\d{1,2})/u', $s, $m)) {
        $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3];
        return sprintf('%04d.%02d.%02d', $y, $mo, $d);
    }
    if (preg_match('/(\d{4})[\-\/\.](\d{1,2})[\-\/\.](\d{1,2})/', $s, $m)) {
        return sprintf('%04d.%02d.%02d', $m[1], $m[2], $m[3]);
    }
    return '';
}

/** 从商店HTML提取“标签”（简介下方热门标签） */
function steam_scraper_extract_tags($html){
    $tags = array();
    if (empty($html)) return $tags;

    $block = $html;
    if (preg_match('/<div[^>]*class="[^"]*\bglance_tags\b[^"]*"[^>]*>(.*?)<\/div>/is', $html, $m1)) {
        $block = $m1[1];
    } elseif (preg_match('/<div[^>]*id="popular_tags"[^>]*>(.*?)<\/div>/is', $html, $m2)) {
        $block = $m2[1];
    }

    if (preg_match_all('/<a[^>]*class="[^"]*app_tag[^"]*"[^>]*>(?:\s*<span[^>]*>)?([^<]+?)(?:<\/span>)?\s*<\/a>/iu', $block, $m)) {
        foreach ($m[1] as $txt) {
            $t = trim(wp_strip_all_tags($txt));
            if ($t !== '') $tags[] = $t;
        }
    }
    return $tags;
}

/** 从 API genres 提取候选标签（兜底） */
function steam_scraper_tags_from_api($data){
    $tags = array();
    if (isset($data['genres']) && is_array($data['genres'])) {
        foreach ($data['genres'] as $g) {
            if (!empty($g['description'])) {
                $t = sanitize_text_field($g['description']);
                if ($t !== '') $tags[] = $t;
            }
        }
    }
    return $tags;
}

/** 标签清洗：去重 + 屏蔽词（不做数量限制） */
function steam_scraper_tags_finalize($tags){
    if (!is_array($tags)) $tags = array();
    $tags = array_values(array_unique(array_map('trim', $tags)));
    $ban = array('色情','暴力','血腥');
    $filtered = array();
    foreach ($tags as $t){
        $bad = false;
        foreach ($ban as $b){
            if (function_exists('mb_stripos')){
                if (mb_stripos($t, $b) !== false) { $bad = true; break; }
            } else {
                if (stripos($t, $b) !== false) { $bad = true; break; }
            }
        }
        if (!$bad) $filtered[] = $t;
    }
    return array_values($filtered);
}

/** AJAX：采集 */
function steam_scraper_fetch_game(){
    check_ajax_referer('steam_scraper_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('msg' => '权限不足'));
    }

    $input = isset($_POST['steam_input']) ? trim(sanitize_text_field(wp_unslash($_POST['steam_input']))) : '';
    if (empty($input)) {
        wp_send_json_error(array('msg' => '请输入Steam游戏URL或AppID'));
    }

    // 解析 AppID
    $appid = '';
    if (preg_match('/app\/(\d{3,10})/i', $input, $m)) {
        $appid = $m[1];
    } else if (preg_match('/^(\d{3,10})$/', $input, $m)) {
        $appid = $m[1];
    } else if (preg_match('/(\d{3,10})/', $input, $m)) {
        $appid = $m[1];
    }
    if (empty($appid)) {
        wp_send_json_error(array('msg' => '未识别到有效的AppID'));
    }

    $game = array();
    // 中文接口
    $api_url = "https://store.steampowered.com/api/appdetails?appids={$appid}&l=schinese";
    $res = wp_remote_get($api_url, array('timeout' => 20, 'user-agent' => 'Mozilla/5.0'));
    if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) == 200) {
        $body = wp_remote_retrieve_body($res);
        $json = json_decode($body, true);
        if (isset($json[$appid]['success']) && $json[$appid]['success']) {
            $d = $json[$appid]['data'];
            $title = isset($d['name']) ? $d['name'] : '';
            $cover = isset($d['header_image']) ? $d['header_image'] : '';

            // 只取 LOGO 下简介：short_description
            $desc_raw = '';
            if (!empty($d['short_description'])) { $desc_raw = $d['short_description']; }
            $desc_raw = str_ireplace(array('<br>','<br/>','<br />','&nbsp;'), array("\n","\n","\n", ' '), $desc_raw);
            $desc_raw = wp_strip_all_tags($desc_raw);
            $desc_raw = html_entity_decode($desc_raw, ENT_QUOTES, 'UTF-8');
            $desc = steam_scraper_mb_trim($desc_raw, 220);

            $price = '免费';
            if (!empty($d['price_overview']['final_formatted'])) {
                $price = $d['price_overview']['final_formatted'];
            }

            $game = array(
                'appid' => $appid,
                'title' => sanitize_text_field($title),
                'cover' => esc_url_raw($cover),
                'desc'  => $desc,
                'price' => sanitize_text_field($price),
                'store_url' => "https://store.steampowered.com/app/{$appid}/",
                'name_cn' => sanitize_text_field($title),
                'name_en' => '',
                'release_date' => isset($d['release_date']['date']) ? sanitize_text_field($d['release_date']['date']) : '',
                'release_date_iso' => steam_scraper_norm_date(isset($d['release_date']['date']) ? $d['release_date']['date'] : ''),
                'tags' => steam_scraper_tags_finalize(steam_scraper_tags_from_api($d)) // 先用API兜底，稍后用HTML覆盖
            );
        }
    }

    // 英文接口补充英文名与可解析日期
    $api_url_en = "https://store.steampowered.com/api/appdetails?appids={$appid}&l=english";
    $res_en = wp_remote_get($api_url_en, array('timeout' => 15, 'user-agent' => 'Mozilla/5.0'));
    if (!is_wp_error($res_en) && wp_remote_retrieve_response_code($res_en) == 200) {
        $body_en = wp_remote_retrieve_body($res_en);
        $json_en = json_decode($body_en, true);
        if (isset($json_en[$appid]['success']) && $json_en[$appid]['success']) {
            $d_en = $json_en[$appid]['data'];
            if (empty($game['name_en']) && !empty($d_en['name'])) {
                $game['name_en'] = sanitize_text_field($d_en['name']);
            }
            if (!empty($d_en['release_date']['date'])) {
                $iso = steam_scraper_norm_date($d_en['release_date']['date']);
                if (!empty($iso)) { $game['release_date_iso'] = $iso; }
            }
        }
    }

    // 商店页面HTML抓标签（优先于API genres）
    $_tag_url = "https://store.steampowered.com/app/{$appid}/?l=schinese&cc=cn";
    $_tag_res = wp_remote_get($_tag_url, array(
        'timeout' => 20,
        'redirection' => 5,
        'headers' => array(
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Referer' => 'https://store.steampowered.com/',
        ),
        'cookies' => array(
            'wants_mature_content' => '1',
            'birthtime' => '0',
            'lastagecheckage' => '1-January-1980',
        ),
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36'
    ));
    if (!is_wp_error($_tag_res) && wp_remote_retrieve_response_code($_tag_res) == 200) {
        $_tag_html = wp_remote_retrieve_body($_tag_res);
        $t = steam_scraper_extract_tags($_tag_html);
        $t = steam_scraper_tags_finalize($t);
        if (!empty($t)) { $game['tags'] = $t; }
    }

    // 必要字段缺失时：回退到页面解析
    if (empty($game['title']) || empty($game['cover'])) {
        $page_url = "https://store.steampowered.com/app/{$appid}/?l=schinese&cc=cn";
        $pr = wp_remote_get($page_url, array('timeout' => 20, 'user-agent' => 'Mozilla/5.0'));
        if (!is_wp_error($pr) && wp_remote_retrieve_response_code($pr) == 200) {
            $html = wp_remote_retrieve_body($pr);
            if (preg_match('/<div class="apphub_AppName"[^>]*>(.*?)<\/div>/is', $html, $mm)) {
                $game['title'] = sanitize_text_field(wp_strip_all_tags($mm[1]));
            }
            if (preg_match('/<img[^>]*class="game_header_image_full"[^>]*src="([^"]+)"/is', $html, $mm)) {
                $game['cover'] = esc_url_raw($mm[1]);
            }
            if (preg_match('/<div class="game_description_snippet"[^>]*>(.*?)<\/div>/is', $html, $mm)) {
                $desc_raw = str_ireplace(array('<br>','<br/>','<br />','&nbsp;'), array("\n","\n","\n", ' '), $mm[1]);
                $desc_raw = wp_strip_all_tags($desc_raw);
                $desc_raw = html_entity_decode($desc_raw, ENT_QUOTES, 'UTF-8');
                $game['desc'] = steam_scraper_mb_trim($desc_raw, 220);
            }
            if (preg_match('/<div class="discount_final_price"[^>]*>(.*?)<\/div>/is', $html, $mm)) {
                $game['price'] = sanitize_text_field(strip_tags($mm[1]));
            } elseif (preg_match('/<div class="game_purchase_price[^"]*"[^>]*>(.*?)<\/div>/is', $html, $mm)) {
                $game['price'] = sanitize_text_field(strip_tags($mm[1]));
            } else {
                if (empty($game['price'])) $game['price'] = '免费';
            }
            $game['appid'] = $appid;
            $game['store_url'] = "https://store.steampowered.com/app/{$appid}/";
            if (empty($game['tags'])) {
                $_tags = steam_scraper_extract_tags($html);
                $_tags = steam_scraper_tags_finalize($_tags);
                if (!empty($_tags)) { $game['tags'] = $_tags; }
            }
        }
    }

    if (empty($game['title']) || empty($game['cover'])) {
        wp_send_json_error(array('msg' => '采集失败，未获取到必要信息'));
    }

    wp_send_json_success($game);
}
add_action('wp_ajax_steam_scraper_fetch_game', 'steam_scraper_fetch_game');
add_action('wp_ajax_nopriv_steam_scraper_fetch_game', 'steam_scraper_fetch_game');

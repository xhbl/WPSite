<?php
/**
 * common functions and definitions.
 */


// 拆分绝对路径为站点路径和相对路径
function urlpath_split($srcurl) {
    // 尝试解析 URL
    $srcurl = ltrim($srcurl);
    $parts = parse_url($srcurl);

    // 检查是否有 scheme 和 host（至少需要这两个）
    if (!isset($parts['scheme']) || !isset($parts['host'])) {
        return [
            'hosturl' => '',
            'relpath'  => $srcurl,
        ];
    }

    // 构建标准化的 hosturl（协议 + 主机 + 可选端口），统一 scheme 和 host 为小写
    $hosturl = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
    if (isset($parts['port'])) {
        $hosturl .= ':' . $parts['port'];
    }

    // 统一比较源 URL 的左边部分
    $srcurl_lower = strtolower($srcurl);
    $pos = stripos($srcurl_lower, $hosturl);
    if ($pos === 0) {
        $relpath = substr($srcurl, strlen($hosturl));
        return [
            'hosturl' => $hosturl,
            'relpath'  => $relpath,
        ];
    }

    // 未匹配，返回原值
    return [
        'hosturl' => '',
        'relpath'  => $srcurl,
    ];
}

// Helper function to check if content has absolute URLs
function has_absolute_urls($content, $site_url) {
    $escaped_site_url = preg_quote($site_url, '/');

    // Patterns to match absolute URLs that should be converted to relative
    $patterns = array(
        '/href=["\']' . $escaped_site_url . '\/([^"\']*)["\']/',  // href="http://domain.com/path"
        '/src=["\']' . $escaped_site_url . '\/([^"\']*)["\']/',   // src="http://domain.com/path"
        '/url\(["\']?' . $escaped_site_url . '\/([^"\']*)["\']?\)/', // url(http://domain.com/path)
        '/src=\\\\u0022' . $escaped_site_url . '\/([^\\\\]*?)\\\\u0022/', // Inline JSON escaped: src=\\u0022http://domain.com/path\\u0022
        '/href=\\\\u0022' . $escaped_site_url . '\/([^\\\\]*?)\\\\u0022/', // Inline JSON escaped: href=\\u0022http://domain.com/path\\u0022
        '/"url"\s*:\s*"' . $escaped_site_url . '\/([^"]*)"/',    // JSON: "url":"http://domain.com/path"
        '/"src"\s*:\s*"' . $escaped_site_url . '\/([^"]*)"/'     // JSON: "src":"http://domain.com/path"
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Helper function to check if content has relative URLs
function has_relative_urls($content) {
    // Patterns to match relative URLs that should be converted to absolute
    $patterns = array(
        '/href=["\']\/(?!\/|http|#)([^"\']*)["\']/',  // href="/path" but not href="//domain" or href="http" or href="#anchor"
        '/src=["\']\/(?!\/|http)([^"\']*)["\']/',     // src="/path" but not src="//domain" or src="http"
        '/url\(["\']?\/(?!\/|http)([^"\']*)["\']?\)/', // url(/path) but not url(//domain) or url(http)
        '/src=\\\\u0022\/(?!\/|http)([^\\\\]*?)\\\\u0022/', // Inline JSON escaped: src=\\u0022/path\\u0022
        '/href=\\\\u0022\/(?!\/|http)([^\\\\]*?)\\\\u0022/', // Inline JSON escaped: href=\\u0022/path\\u0022
        '/"url"\s*:\s*"\/(?!\/|http)([^"]*)"/',      // JSON: "url":"/path"
        '/"src"\s*:\s*"\/(?!\/|http)([^"]*)"/'       // JSON: "src":"/path"
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Function to convert absolute URLs to relative URLs
function convert_absolute_to_relative($content, $site_url) {
    $escaped_site_url = preg_quote($site_url, '/');

    // Patterns to match and convert absolute URLs
    $patterns_replacements = array(
        // href="http://domain.com/path" -> href="/path"
        array(
            'pattern' => '/href=["\']' . $escaped_site_url . '\/([^"\']*)["\']/',
            'replacement' => 'href="/$1"'
        ),
        // src="http://domain.com/path" -> src="/path"
        array(
            'pattern' => '/src=["\']' . $escaped_site_url . '\/([^"\']*)["\']/',
            'replacement' => 'src="/$1"'
        ),
        // url(http://domain.com/path) -> url(/path)
        array(
            'pattern' => '/url\(["\']?' . $escaped_site_url . '\/([^"\']*)["\']?\)/',
            'replacement' => 'url(/$1)'
        ),
        // WordPress Gutenberg blocks: src=\\u0022http://domain.com/path\\u0022 -> src=\\u0022/path\\u0022
        array(
            'pattern' => '/src=\\\\u0022' . $escaped_site_url . '\/([^\\\\]*?)\\\\u0022/',
            'replacement' => 'src=\\\\u0022/$1\\\\u0022'
        ),
        // WordPress Gutenberg blocks: href=\\u0022http://domain.com/path\\u0022 -> href=\\u0022/path\\u0022
        array(
            'pattern' => '/href=\\\\u0022' . $escaped_site_url . '\/([^\\\\]*?)\\\\u0022/',
            'replacement' => 'href=\\\\u0022/$1\\\\u0022'
        ),
        // WordPress Gutenberg blocks JSON: "url":"http://domain.com/path" -> "url":"/path"
        array(
            'pattern' => '/"url"\s*:\s*"' . $escaped_site_url . '\/([^"]*)"/',
            'replacement' => '"url":"/$1"'
        ),
        // WordPress Gutenberg blocks JSON: "src":"http://domain.com/path" -> "src":"/path"
        array(
            'pattern' => '/"src"\s*:\s*"' . $escaped_site_url . '\/([^"]*)"/',
            'replacement' => '"src":"/$1"'
        )
    );

    $new_content = $content;

    foreach ($patterns_replacements as $pr) {
        $new_content = preg_replace($pr['pattern'], $pr['replacement'], $new_content);
    }

    return $new_content;
}

// Function to restore relative URLs to absolute URLs
function convert_relative_to_absolute($content, $site_url) {
    // Patterns to match and replace relative URLs
    $patterns_replacements = array(
        // href="/path" -> href="http://domain.com/path"
        array(
            'pattern' => '/href=["\']\/(?!\/|http|#)([^"\']*)["\']/',
            'replacement' => 'href="' . $site_url . '/$1"'
        ),
        // src="/path" -> src="http://domain.com/path"
        array(
            'pattern' => '/src=["\']\/(?!\/|http)([^"\']*)["\']/',
            'replacement' => 'src="' . $site_url . '/$1"'
        ),
        // url(/path) -> url(http://domain.com/path)
        array(
            'pattern' => '/url\(["\']?\/(?!\/|http)([^"\']*)["\']?\)/',
            'replacement' => 'url(' . $site_url . '/$1)'
        ),
        // WordPress Gutenberg blocks: src=\\u0022/path\\u0022 -> src=\\u0022http://domain.com/path\\u0022
        array(
            'pattern' => '/src=\\\\u0022\/(?!\/|http)([^\\\\]*?)\\\\u0022/',
            'replacement' => 'src=\\\\u0022' . $site_url . '/$1\\\\u0022'
        ),
        // WordPress Gutenberg blocks: href=\\u0022/path\\u0022 -> href=\\u0022http://domain.com/path\\u0022
        array(
            'pattern' => '/href=\\\\u0022\/(?!\/|http)([^\\\\]*?)\\\\u0022/',
            'replacement' => 'href=\\\\u0022' . $site_url . '/$1\\\\u0022'
        ),
        // WordPress Gutenberg blocks JSON: "url":"/path" -> "url":"http://domain.com/path"
        array(
            'pattern' => '/"url"\s*:\s*"\/(?!\/|http)([^"]*)"/',
            'replacement' => '"url":"' . $site_url . '/$1"'
        ),
        // WordPress Gutenberg blocks JSON: "src":"/path" -> "src":"http://domain.com/path"
        array(
            'pattern' => '/"src"\s*:\s*"\/(?!\/|http)([^"]*)"/',
            'replacement' => '"src":"' . $site_url . '/$1"'
        )
    );

    $new_content = $content;

    foreach ($patterns_replacements as $pr) {
        $new_content = preg_replace($pr['pattern'], $pr['replacement'], $new_content);
    }

    return $new_content;
}

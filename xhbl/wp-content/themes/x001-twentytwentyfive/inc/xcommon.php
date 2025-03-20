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

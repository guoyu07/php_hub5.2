<?php

function insanity_check()
{
    if (app()->environment('production')) {
        exit('别傻了? 这是线上环境呀。');
    }
}

function cdn($filePath)
{
    if (config('phphub.url_static')) {
        return config('phphub.url_static') . $filePath;
    } else {
        return config('phphub.url') . $filePath;
    }
}

function getCdnDomain()
{
    return config('phphub.url_static') ?:  config('phphub.url');
}

function lang($text)
{
    // trans 函数使用本地文件翻译给定语言行
    return str_replace('phphub.', '', trans('phphub.' . $text));
}

function getUserStaticDomain()
{
    return config('phphub.user_static') ?: config('phphub.url');
}

function admin_link($title, $path, $id = '')
{
    return '<a href="' . admin_url($path, $id) . '" target="_blank">' . $title . '</a>';
}

function admin_url($path, $id = '')
{
    return env('APP_URL') . "admin/$path" . ($id ? '/' . $id : '');
}

function show_crx_hint()
{
    // 存储一次性数据
    session()->flash('show_crx_hint', 'yes');
}

function check_show_crx_hint()
{
    return session('show_crx_hint') ? true : false;
}

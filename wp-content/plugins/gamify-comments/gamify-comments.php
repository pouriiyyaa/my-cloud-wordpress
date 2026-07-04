<?php
/**
 * Plugin Name: سیستم تعاملی و گیمیفیکیشن نظرات
 * Description: سیستم هوشمند لایک بدون رفرش و نشان‌های کاربری در وردپرس روی سرور رندر.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ۱. نمایش دکمه لایک در کامنت‌ها
add_filter( 'comment_text', 'gamify_render_buttons', 99, 2 );
function gamify_render_buttons( $comment_text, $comment ) {
    $likes = get_comment_meta( $comment->comment_ID, '_comment_likes', true ) ?: 0;
    return $comment_text . '<div style="margin-top:10px;"><button class="gamify-like-btn" data-id="' . $comment->comment_ID . '" style="background:#f0f2f5; border:none; padding:6px 12px; cursor:pointer; border-radius:20px;">👍 (<span class="count">' . $likes . '</span>)</button></div>';
}

// ۲. ساخت آدرس شبکه برای ثبت لایک
add_action( 'rest_api_init', function () {
    register_rest_route( 'gamify/v1', '/like/(?P<id>\d+)', array(
        'methods'             => 'POST',
        'callback'            => function($request) {
            $comment_id = $request['id'];
            $new_likes = ((int) get_comment_meta( $comment_id, '_comment_likes', true )) + 1;
            update_comment_meta( $comment_id, '_comment_likes', $new_likes );
            return array( 'success' => true, 'new_likes' => $new_likes );
        },
        'permission_callback' => '__return_true',
    ));
});

// ۳. کد جاوااسکریپت برای کارکرد بدون رفرش
add_action( 'wp_enqueue_scripts', function () {
    wp_register_script( 'gamify-js', '', [], '', true );
    wp_enqueue_script( 'gamify-js' );
    wp_add_inline_script( 'gamify-js', "
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.gamify-like-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const span = this.querySelector('.count');
                    fetch('/wp-json/gamify/v1/like/' + id, { method: 'POST' })
                    .then(res => res.json()).then(data => { if (data.success) span.textContent = data.new_likes; });
                });
            });
        });
    " );
});

// ۴. نشان مدیر سایت
add_filter( 'get_comment_author_link', function ( $return, $author, $comment_id ) {
    $comment = get_comment( $comment_id );
    if ( $comment && !empty( $comment->user_id ) && user_can( $comment->user_id, 'manage_options' ) ) {
        return $return . ' <span style="background:#d32f2f; color:#fff; padding:2px 6px; font-size:11px; border-radius:3px;">مدیر سایت</span>';
    }
    return $return;
}, 10, 3 );

<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        "install",
        "logout",
        "admin/*",
        "set_locale",
        "downloads/*/download",
        "downloads/dropbox_preview_url",
        "notifications/read",
        "item/*",
        "payment/token/*",
        "add_to_cart_async",
        "item/product_folder",
        "update_price",
        "guest/*",
        "checkout/*",
        "user_notifs",
        "guest/download",
        "download",
        "dd/*",
        'send_email_verification_link',
        'save_reaction',
        'get_reactions',
        'items/live_search'
    ];
}

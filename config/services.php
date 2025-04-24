<?php

declare(strict_types=1);

use net\authorize\api\constants\ANetEnvironment;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'yng' => [
        'key' => env('YNG_KEY'),
        'short_link' => env('YNG_SHORT_LINK'),
        'encrypted_key' => env('YNG_ENCRYPTED_KEY'),
    ],

    'younegotiate' => [
        'invitation_login_key' => env('INVITATION_LINK_LOGIN_KEY'),
    ],

    'merchant' => [
        'tilled_sandbox_enabled' => env('ENABLE_PAYMENT_SANDBOX', true),
        'tilled_api_key' => env('TILLED_API_KEY'),
        'tilled_publishable_key' => env('TILLED_PUBLISHABLE_KEY'),
        'tilled_account' => env('TILLED_ACCOUNT_ID'),
        'tilled_merchant_account_id' => env('TILLED_MERCHANT_ACCOUNT_ID'),
        'tilled_cc_pricing_template_id' => env('TILLED_CC_PRICING_TEMPLATE_ID'),
        'tilled_ach_pricing_template_id' => env('TILLED_ACH_PRICING_TEMPLATE_ID'),
        'tilled_webhook_secret' => env('TILLED_WEBHOOK_SECRET'),
    ],

    'google_recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'smarty' => [
        'key' => env('SMARTY_AUTH_KEY'),
    ],

    'inspectlet' => [
        'user_id' => env('INSPECTLET_USER_ID'),
        'username' => env('INSPECTLET_USERNAME'),
        'token' => env('INSPECTLET_TOKEN'),
    ],

    'telnyx' => [
        'token' => env('TELNYX_TOKEN'),
        'from' => env('TELNYX_FROM_NUMBER'),
    ],

    'usaepay_url' => env('ENABLE_PAYMENT_SANDBOX', true)
        ? 'https://sandbox.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl'
        : 'https://www.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl',

    'authorize_environment' => env('ENABLE_PAYMENT_SANDBOX', true)
        ? ANetEnvironment::SANDBOX
        : ANetEnvironment::PRODUCTION,
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google oauth client id
    |--------------------------------------------------------------------------
    */
    'google_client_id' => env('GOOGLE_CLIENT_ID', ''),


    /*
    |--------------------------------------------------------------------------
    | Refresh token 最大生命周期(单位:秒)
    |--------------------------------------------------------------------------
    */
    'refresh_token_max_lifetime' => env('REFRESH_TOKEN_MAX_LIFETIME', 7776000),

    /*
    |--------------------------------------------------------------------------
    | Access token 最大生命周期(单位:秒)
    |--------------------------------------------------------------------------
    */
    'access_token_max_lifetime' => env('ACCESS_TOKEN_MAX_LIFETIME', 86400 * 7),

    /*
    |--------------------------------------------------------------------------
    | 是否限制 token 数量
    |--------------------------------------------------------------------------
    |
    | 为 0 时不限制, 不为 0 的时候为最大 token 数
    |
    */
    'token_limit' => env('TOKEN_LIMIT', 99),


    /*
    |--------------------------------------------------------------------------
    | Magic Link 的有效期
    |--------------------------------------------------------------------------
    |
    | 单位: 秒
    |
    */
    'magic_max_lifetime' => env('MAGIC_MAX_LIFETIME', 600),

    /*
    |--------------------------------------------------------------------------
    | Quick Login Link 的有效期
    |--------------------------------------------------------------------------
    |
    | 单位: 秒
    |
    */
    'quick_login_max_lifetime' => env('QUICK_LOGIN_MAX_LIFETIME', 600),

    /*
    |--------------------------------------------------------------------------
    | SendGrid API Key
    |--------------------------------------------------------------------------
    |
    |
    |
    */
    'sendgrid_api_key' => env('SENDGRID_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Maps Time Zone API Key
    |--------------------------------------------------------------------------
    |
    | @see https://developers.google.com/maps/documentation/timezone/start
    |
    */
    'google_map_time_zone_api_key' => env('GOOGLE_MAP_TIME_ZONE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Fonts API Key
    |--------------------------------------------------------------------------
    |
    | @see https://developers.google.com/fonts/docs/developer_api
    |
    */
    'google_fonts_api_key' => env('GOOGLE_FONTS_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Storage Credential File
    |--------------------------------------------------------------------------
    |
    | The full path to your service account credentials .json file retrieved from the Google Developers Console.
    |
    | @see https://developers.google.com/fonts/docs/developer_api
    |
    */
    'google_cloud_storage_credential_file' => env('GOOGLE_CLOUD_STORAGE_CREDENTIAL_FILE', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Storage Bucket Name
    |--------------------------------------------------------------------------
    */
    'google_cloud_storage_bucket_name' => env('GOOGLE_CLOUD_STORAGE_BUCKET_NAME', ''),
];
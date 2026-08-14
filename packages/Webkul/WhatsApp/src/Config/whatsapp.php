<?php

return [

    /*
    |--------------------------------------------------------------------
    | Meta WhatsApp Business Cloud API credentials
    |--------------------------------------------------------------------
    | Get these from Meta for Developers > your App > WhatsApp > API Setup.
    | Store the real values in .env, never commit them.
    */

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),

    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v19.0'),

    /*
    |--------------------------------------------------------------------
    | Webhook verification
    |--------------------------------------------------------------------
    | Arbitrary string you choose yourself and enter in the Meta App
    | Dashboard webhook config. Must match WHATSAPP_VERIFY_TOKEN in .env.
    */

    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------
    | App secret, used to validate X-Hub-Signature-256 on webhook calls
    |--------------------------------------------------------------------
    */

    'app_secret' => env('WHATSAPP_APP_SECRET'),

    /*
    |--------------------------------------------------------------------
    | Behavior
    |--------------------------------------------------------------------
    */

    // Auto-create a Lead when a message arrives from an unknown number.
    'auto_create_lead' => env('WHATSAPP_AUTO_CREATE_LEAD', true),

    // Default lead source label applied to auto-created leads.
    'default_lead_source' => 'whatsapp',

    // Default sales channel/pipeline stage assigned to auto-created leads.
    // Leave null to fall back to Krayin's own default pipeline settings.
    'default_pipeline_id' => null,

    /*
    |--------------------------------------------------------------------
    | Media storage
    |--------------------------------------------------------------------
    | Disk (from config/filesystems.php) that downloaded WhatsApp media
    | (images, documents, audio, video) gets stored on. Use 'public' for
    | local dev; swap to an 's3' disk in production so files survive
    | deploys and don't bloat the app server.
    */

    'media_disk' => env('WHATSAPP_MEDIA_DISK', 'public'),

];

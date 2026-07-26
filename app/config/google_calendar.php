<?php

return [
    'consent_version' => env('GOOGLE_CALENDAR_CONSENT_VERSION', 'gcal-1.0'),

    'consent_title' => 'Consentimento Google Agenda — Inova Hub / Finova',

    /*
    | Minimum Calendar scopes (People API intentionally excluded).
    */
    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/calendar.events',
    ],
];

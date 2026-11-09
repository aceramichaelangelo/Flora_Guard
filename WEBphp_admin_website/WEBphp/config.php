<?php
declare(strict_types=1);

/**
 * Flora Guard Admin — configuration. Copy this folder to XAMPP htdocs and edit values below.
 */

const APP_NAME = 'Native Flora Detection and Management System Using Computer Vision and Geoanalytics in Talaingod, Davao del Norte';
/** Short brand shown on login, sidebar, and page titles. */
const APP_BRAND = 'Flora Guard';
const APP_REGION = 'Davao del Norte';
const MOBILE_APP_URL = 'mobile.php';

/** Firebase Realtime Database root URL (no trailing slash). */
const FIREBASE_DB_URL = 'https://denr-28e1b-default-rtdb.firebaseio.com';

/**
 * Web API key from Firebase Console → Project settings → General → Web API Key.
 * Required for admin login (Identity Toolkit).
 */
const FIREBASE_WEB_API_KEY = 'REPLACE_WITH_FIREBASE_WEB_API_KEY';

/**
 * If true and FIREBASE_WEB_API_KEY is still the placeholder, login accepts:
 *   admin@davao.gov.ph / admin123
 * Disable in production.
 */
const TG_DEV_LOGIN = true;

const SESSION_NAME = 'tg_admin_session';

/**
 * Mapbox **public** access token (`pk.`). Same token as the Flutter app; used by the admin map (Mapbox GL JS).
 * You can override with Apache `SetEnv MAPBOX_ACCESS_TOKEN "pk...."` instead of editing this file.
 */
const MAPBOX_ACCESS_TOKEN =
    'REPLACE_WITH_MAPBOX_PUBLIC_ACCESS_TOKEN';

/** Sender address for registration OTP emails (must be a real mailbox on your hosting domain). */
const OTP_MAIL_FROM = 'inigma@inigma-ph.com';

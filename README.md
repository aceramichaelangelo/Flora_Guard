# Flora Guard Admin

Flora Guard Admin is a PHP web dashboard for managing native-flora records, users, scans, conservation data, and geospatial monitoring in Talaingod, Davao del Norte.

The application is designed to work with the Flora Guard mobile application and uses Firebase Realtime Database for shared data storage. Mapbox is used for interactive map visualization.

## Features

- Admin authentication and session management
- Dashboard overview and conservation statistics
- Native tree and species record management
- Scan and image record monitoring
- Interactive Mapbox map with:
  - Species filters
  - Barangay filters
  - Multiple map styles
  - Mapped-tree and hotspot statistics
- User management
- Notifications and reports
- Species infographic generation
- Firebase Realtime Database integration
- OTP registration support

## Technology Stack

- PHP 8.x
- HTML, CSS, and JavaScript
- Firebase Realtime Database
- Firebase Authentication
- Mapbox GL JS
- Chart.js
- Apache/XAMPP

## Project Structure

```text
WEBphp_admin_website/
└── WEBphp/
    ├── api/          JSON API endpoints
    ├── assets/       CSS, JavaScript, images, and vendor files
    ├── includes/     Shared layout and initialization files
    ├── lib/          Firebase, data, OTP, and application helpers
    ├── cache/        Application cache files
    ├── storage/      Protected application storage
    ├── uploads/      Protected uploaded files
    ├── config.php    Application configuration
    └── index.php     Application entry point
```

## Requirements

- Windows with XAMPP
- Apache
- PHP 8.x with cURL enabled
- A Firebase project with Realtime Database enabled
- A Mapbox public access token for map features

## Installation with XAMPP

1. Clone or download this repository.

2. Copy the project folder into the XAMPP web root:

   ```text
   C:\xampp\htdocs\2_Web_Admin
   ```

3. Start **Apache** from the XAMPP Control Panel.

4. Open the application:

   ```text
   http://localhost/2_Web_Admin/WEBphp_admin_website/WEBphp/
   ```

5. Configure the application in:

   ```text
   WEBphp_admin_website/WEBphp/config.php
   ```

## Configuration

### Firebase

Set the Firebase Web API key in `config.php`:

```php
const FIREBASE_WEB_API_KEY = 'YOUR_FIREBASE_WEB_API_KEY';
```

The Firebase Realtime Database URL is configured in the same file:

```php
const FIREBASE_DB_URL = 'https://YOUR_PROJECT_ID-default-rtdb.firebaseio.com';
```

For production use, disable development login:

```php
const TG_DEV_LOGIN = false;
```

The Firebase project must have Email/Password authentication enabled if Firebase Authentication is being used.

### Mapbox

Do not commit a Mapbox token to the repository. Configure it through Apache instead:

```apache
SetEnv MAPBOX_ACCESS_TOKEN "pk.your_mapbox_public_token"
```

Alternatively, replace the placeholder value in `config.php` locally. Keep any real token out of Git commits and public repositories.

The map page displays a warning when a valid token beginning with `pk.` is not available.

### Email and OTP

Update the sender address in `config.php` to a real mailbox configured on your hosting provider:

```php
const OTP_MAIL_FROM = 'your-email@example.com';
```

## Development Login

The project includes a development-only login fallback when the Firebase API key is still a placeholder. This fallback is intended for local development only.

Before deploying publicly:

1. Configure Firebase Authentication.
2. Set `FIREBASE_WEB_API_KEY`.
3. Set `TG_DEV_LOGIN` to `false`.
4. Use a real Firebase administrator account.
5. Change any default or test credentials.

## Security Notes

- Never commit Firebase keys, Mapbox tokens, email credentials, or other private secrets.
- Use environment variables or Apache configuration for deployment secrets.
- Restrict Firebase Realtime Database rules before production deployment.
- Disable development login in production.
- Keep uploaded files and application storage protected from direct execution.
- Rotate any credential or token that has previously been exposed.

## Troubleshooting

### Map does not load

Confirm that:

- Apache is running.
- `MAPBOX_ACCESS_TOKEN` is configured.
- The token is a valid public Mapbox token beginning with `pk.`.
- The browser can load the Mapbox assets.

### Firebase login fails

Confirm that:

- The Firebase Web API key is correct.
- Email/Password authentication is enabled.
- The Firebase Realtime Database URL is correct.
- The user exists in Firebase Authentication.
- The user's profile has the required administrator role.

### PHP errors appear

Confirm that:

- PHP 8.x is installed and enabled in XAMPP.
- The PHP cURL extension is enabled.
- Apache is serving the correct project directory.
- `config.php` contains valid configuration values.

## License

This project was developed as an academic/capstone system. Add the appropriate license and attribution details before redistributing it publicly.

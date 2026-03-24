# MauticDirectPushBundle

Send push notifications directly via Firebase Cloud Messaging (FCM) and Apple Push Notification Service (APNs) from Mautic — no OneSignal or other third-party push aggregator required.

## Requirements

- Mautic 5.x or 6.x
- PHP 8.1+
- `curl` with HTTP/2 support (for APNs via pushok)
- `google/auth` (installed via composer)
- `edamov/pushok` (installed via composer)

## Installation

Copy the `MauticDirectPushBundle` directory into your Mautic `plugins/` directory:

```
plugins/
  MauticDirectPushBundle/
    Config/
    Controller/
    ...
```

Then clear the cache and run migrations:

```bash
php bin/console cache:clear
php bin/console mautic:plugins:reload
php bin/console doctrine:migrations:migrate --no-interaction
```

## Configuration

Go to **Settings → Configuration → Direct Push Notifications** and configure:

### FCM (Android / Web)

1. Go to Firebase Console → Project Settings → Service Accounts
2. Click "Generate new private key" to download the JSON file
3. Paste the entire JSON contents into the "Service Account JSON" field
4. Project ID is auto-populated from the JSON

### APNs (iOS)

1. Go to Apple Developer Console → Keys → Create a key with APNs enabled
2. Download the `.p8` file (one-time download)
3. Paste the `.p8` file contents into "APNs Auth Key" field
4. Enter Key ID, Team ID, and your app's Bundle ID
5. Check "Production Environment" for App Store builds

## Device Token Registration

Your mobile app registers device tokens via the API:

```
POST /api/push/devices
Authorization: Basic <credentials>
Content-Type: application/json

{
    "token": "fcm-or-apns-device-token",
    "platform": "android",
    "contact_id": 123,
    "app_id": "com.example.app"
}
```

Or use `email` instead of `contact_id` to look up the contact.

To deactivate a token:

```
DELETE /api/push/devices/{token}
```

## Usage

### Campaign Actions

In the Campaign Builder, add a **Send Push Notification** action. Select a published notification template. The notification is sent to all active device tokens for each contact.

### Segment Broadcasts

Create a notification with type "Segment (Broadcast)" and publish it. Run:

```bash
php bin/console mautic:broadcasts:send --channel=push
```

### Click Tracking

The app should POST to `/push/callback` when a notification is tapped:

```json
{
    "tracking_hash": "hash-from-notification-data"
}
```

The `tracking_hash` is included in the notification's `data` payload as `tracking_hash`.

## Contact Timeline

Push notification sends and clicks appear on the contact's activity timeline.

## Reports

A "Push Notifications" report context is available under Reports showing send/click/failure data.

## Architecture

- **FCM**: `google/auth` for OAuth 2.0 token generation from service account key, HTTP v1 API via curl
- **APNs**: `edamov/pushok` for HTTP/2 multiplexed connections with ES256 JWT auth, batched concurrent delivery
- Invalid tokens (UNREGISTERED, 410 Gone) are automatically deactivated
- iOS sends are batched through pushok's connection pool; Android/web sends go individually through FCM HTTP v1

## License

GPL-3.0-or-later

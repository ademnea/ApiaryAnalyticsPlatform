# Farmer Mobile API

This document describes the Farmer Mobile API implemented in
[`routes/farmer_api.php`](../../routes/farmer_api.php). The routes are loaded
from [`routes/api.php`](../../routes/api.php).

## Base URL and authentication

All routes use the `/api/v1/farmer` prefix. For local development, the base
URL is typically `http://localhost:8000/api/v1/farmer`.

Public authentication and password-recovery routes do not require a token.
After an administrator approves a farmer account, log in and send the
returned Sanctum token on protected requests:

```http
Authorization: Bearer <token>
Accept: application/json
```

Tokens expire after 30 days. Login is limited to 10 attempts per IP per
minute. Registration is limited to 3 attempts per IP per minute because
registration can trigger billable SMS. A newly registered account is pending
approval and cannot use the protected farmer endpoints until activated and
assigned an accepted role.

Protected routes require the `farmer` or `farmer-write` role. Updating a hive's
location specifically requires `farmer-write`. `POST /logout` only requires
authentication, so an account can revoke its current token even if its role
has changed.

## Endpoints

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| `POST` | `/register` | Public | Submit a farmer registration for approval |
| `POST` | `/login` | Public, rate-limited | Log in and receive a token |
| `POST` | `/logout` | Authenticated | Revoke the current token |
| `POST` | `/password/forgot` | Public, rate-limited | Request a password reset code |
| `POST` | `/password/reset` | Public, rate-limited | Set a new password using the reset code |
| `GET` | `/profile` | Farmer | Read the signed-in farmer's profile |
| `PUT` | `/profile` | Farmer | Update the signed-in farmer's profile |
| `POST` | `/device-token` | Farmer | Register an FCM device token |
| `GET` | `/apiaries` | Farmer | List the signed-in farmer's apiaries |
| `GET` | `/apiaries/{apiaryId}/hives` | Farmer | List hives in an apiary |
| `GET` | `/hives/{hive_id}/temperature` | Farmer | Read temperature history |
| `GET` | `/hives/{hive_id}/humidity` | Farmer | Read humidity history |
| `GET` | `/hives/{hive_id}/carbondioxide` | Farmer | Read carbon-dioxide history |
| `GET` | `/hives/{hive_id}/weight` | Farmer | Read weight history |
| `GET` | `/hives/{hive_id}/latest` | Farmer | Read the latest values for all four sensors |
| `GET` | `/hives/{hive_id}/photos` | Farmer | List hive photos |
| `GET` | `/hives/{hive_id}/audio` | Farmer | List hive audio |
| `GET` | `/hives/{hive_id}/videos` | Farmer | List hive videos |
| `GET` | `/hives/{hive_id}/inspections` | Farmer | List hive inspections |
| `PUT` | `/hives/{hive_id}/location` | Farmer-write | Update a hive's location |
| `GET` | `/alerts` | Farmer | List the farmer's alerts |
| `PATCH` | `/alerts/{alert_id}/read` | Farmer | Mark one alert as read |
| `POST` | `/messages` | Farmer | Send a message to an administrator |
| `GET` | `/messages` | Farmer | List the farmer's messages |

## Request details

All request bodies should be sent as JSON with `Content-Type: application/json`.

### Registration

`POST /register`

```json
{
  "name": "Amina Example",
  "first_name": "Amina",
  "last_name": "Example",
  "email": "amina@example.com",
  "telephone": "+254700000000",
  "password": "example-password",
  "password_confirmation": "example-password"
}
```

`name`, `email`, `telephone`, and `password` are required. `name` is limited
to 255 characters; `first_name` and `last_name` are optional and limited to
100 characters. The email must be unique among non-deleted user accounts.
The password must be at least 8 characters and match its confirmation.
Registration creates a pending account and returns HTTP `201`.

### Login and logout

`POST /login` requires `email` and `password`. A successful response is
unwrapped:

```json
{
  "token": "1|...",
  "expires_at": "2026-11-18T10:00:00+00:00",
  "farmer": {
    "id": 42,
    "name": "Amina Example",
    "email": "amina@example.com",
    "role": "farmer"
  }
}
```

Invalid credentials return HTTP `401`. A valid account that is pending,
rejected, or inactive returns HTTP `403`. `POST /logout` revokes only the
token used for that request and returns a message.

### Password recovery

`POST /password/forgot` accepts `email`. The response is intentionally the
same whether or not the email belongs to an account. It is limited to 5
requests per IP per minute. A six-digit code is sent to the farmer's email and
SMS number and expires after 10 minutes. A new code can be requested once per
minute for the same account.

`POST /password/reset` accepts `email`, `token` (a six-digit string),
`password`, and `password_confirmation`. The password must be at least 8
characters and match its confirmation. Codes are single-use; at most 5 code
checks are allowed per email every 10 minutes, and the endpoint is also
rate-limited per IP. An invalid or expired code returns HTTP `422`.

### Profile and device token

`GET /profile` returns the farmer's `id`, `first_name`, `last_name`, `gender`,
`email`, `telephone`, `address`, and `role`.

`PUT /profile` accepts any of the following optional fields:

| Field | Validation |
| --- | --- |
| `first_name` | String, max 100 characters |
| `last_name` | String, max 100 characters |
| `gender` | `Male`, `Female`, `Other`, or null |
| `telephone` | Valid telephone number |
| `address` | String, max 255 characters |
| `password` | At least 8 characters; if supplied, must match `password_confirmation` |
| `password_confirmation` | Confirmation for `password` |

Email and role cannot be changed through this endpoint. The response contains
the updated profile in the standard data envelope.

`POST /device-token` accepts `device_token` (a non-empty string of at most
512 characters). The API does not return the token in its response.

### Apiaries, hives, and pagination

`GET /apiaries` and `GET /apiaries/{apiaryId}/hives` return paginated results.
The hive list includes its available location fields (`latitude`,
`longitude`, and `accuracy_meters`).

List endpoints accept `?page=1&per_page=25`. The default is 25 for apiaries,
hives, and inspections; 15 for alerts and messages; and 8 for media.
Media accepts at most 50 items per page. Other list endpoints clamp the page
size to 1–100.

Apiary, hive, inspection, and media lists return:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 25,
    "total": 0
  }
}
```

Sensor history, alerts, and message lists use the shared response envelope;
their paginator is nested in `data` and follows Laravel's paginator shape
(including `current_page`, `data`, `last_page`, `per_page`, and `total`).

### Sensor readings

The sensor history endpoints accept optional `from`, `to`, and `per_page`
query parameters. `start` and `end` are also accepted as aliases for `from`
and `to`. Dates must be valid dates; `to` cannot be earlier than `from`.
Results are ordered newest first. `per_page` defaults to 15 and must be from
1 to 100. The `latest` endpoint always fetches each sensor's latest reading;
date filters and `per_page` do not change that result.

`GET /hives/{hive_id}/latest` returns `hive_id`, the latest `temperature`,
`humidity`, `co2`, and `weight` readings (each may be null), and `fetched_at`.

### Hive location

`PUT /hives/{hive_id}/location` accepts:

```json
{
  "latitude": -1.2864,
  "longitude": 36.8172,
  "accuracy_meters": 12.5
}
```

Latitude and longitude are required numbers in the ranges `-90..90` and
`-180..180`. `accuracy_meters` is optional and must be between 0 and 100.
The response contains the updated hive ID and coordinates.

### Alerts and messages

`GET /alerts` lists the authenticated farmer's alerts.
`PATCH /alerts/{alert_id}/read` marks an alert as read; it will not allow a
farmer to update another farmer's alert.

`POST /messages` requires `subject` (string, max 255 characters) and
`message` (string). An optional `hive_id` can associate the message with one
of the farmer's hives. `GET /messages` lists the signed-in farmer's messages.

## SMS notifications

SMS delivery uses the Laravel notification queue and Africa's Talking; the
application does not need a separate Express server. Set these values in the
deployment environment (never commit real credentials):

```dotenv
AFRICASTALKING_API_KEY=your-api-key
AFRICASTALKING_USERNAME=your-live-username
AFRICASTALKING_ADMIN_PHONE_NUMBERS=+256700000001,+256700000002
MOBILE_APP_URL=https://app.ademnea.com
```

Use `AFRICASTALKING_USERNAME=sandbox` for the Africa's Talking sandbox.
Administrator phone numbers are a comma-separated list. Farmers are texted
using their saved `telephone`, `phone_number`, or `phone` value; hardware-team
numbers come from the team's contact and active member records.

Keep a queue worker running in each environment, for example:

```bash
php artisan queue:work
```

Farmer registration, account creation/approval/rejection, password-reset
codes, feedback replies (when a phone was supplied), and farmer anomaly
alerts can generate SMS. Existing alert routes also send SMS to configured
administrators and the relevant hardware team. SMS attempts and delivery
status are recorded in `notification_logs`. Password-reset SMS contains a
short-lived reset code; its log content is encrypted at rest. Never log or
expose that code or provider credentials. Account creation SMS intentionally
does not include a password; account welcome emails containing a password are
not copied into SMS.

The current implementation uses saved phone numbers without a separate SMS
opt-in or phone-verification step. Before production use, ensure recipients
have agreed to receive messages and review local SMS consent and sender-ID
requirements. Registration is IP rate-limited to reduce unsolicited,
billable SMS.

## Response and error formats

Most data endpoints using the shared API response helper return:

```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Authentication endpoints use top-level response fields instead of this
envelope. Apiary, hive, media, and inspection listings return `data` and
`meta` without a `success` field. The hive-location response contains
`message` and `data`. Use the documented shape for each endpoint rather than
assuming one universal response format.

Common HTTP statuses include `200` for success, `201` for registration or
message creation, `401` for missing/invalid authentication, `403` for
disallowed account/role or access, `404` for missing resources, `422` for
validation failures, and `429` when a rate limit is exceeded. Validation
responses follow Laravel's JSON validation response format.

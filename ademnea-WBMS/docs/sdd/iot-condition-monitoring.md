# 4.4 IoT Condition Monitoring and Anomaly Detection — Developer E

> Software Design Document entry, following the AdEMNEA Development Guide §3.2 per-module
> template. Covers SRS §4.6. Status: **draft, pending review** — per the Development Guide's own
> workflow (§6.1, Step 2), no migration, model, or controller code should be written against this
> module until this document has written sign-off from a reviewer or the project lead, and the six
> items in **"Open Items Requiring Human Sign-Off"** below have been resolved.
>
> Branch: `feat-IoT_Condition_Monitoring`. Owning module in the SRS: §4.6.
>
> **Implementation status (2026-09-15): Part 1 is built. Part 2 (ML) is not started** — see
> "Part 1 As Built" directly below for where the implementation deliberately differs from the
> original design in the rest of this document.

## Part 1 As Built — Deviations From This Design

The sections below this one are the original design. Part 1 was built against it, with these
deliberate changes. Where they conflict, **this section wins**.

**Anomalies are incidents, not one row per reading.** The original "append-only, one row per
violation" model meant a low-battery device inserted a row on every heartbeat and "Unresolved"
only ever grew. `sensor_anomalies` now holds at most one *open* row per
`(device_id, hive_id, sensor_type, anomaly_type)`:
- `SensorAnomaly::recordOrTouch()` opens an incident or touches the open one (`occurrences`,
  `last_seen_at`, `last_record_value`). `record_value` keeps the first flagged value as evidence.
- Callers alert only when `$anomaly->wasRecentlyCreated` — one farmer alert per incident.
- Incidents **auto-resolve** (`auto_resolved = true`) when the condition clears: a rule evaluated
  clean for the next reading, device telemetry back within thresholds, or the device reporting again.
- Admins can **acknowledge** and **resolve** (with a note) — `acknowledged_at/_by`,
  `resolved_by`, `resolution_note`. A recurrence after resolution opens a new incident, so history
  is still never deleted (REQ-F-IOT-13).
- Migration: `2026_09_15_000001_add_incident_fields_to_sensor_anomalies_table`.

**Two categories, one table.** `sensor_type = 'telemetry'` rows are *device issues*
(`SensorAnomaly::deviceIssues()`); everything else is a *hive condition* (`hiveConditions()`).

**Device telemetry rules record every violated condition**, not only the highest-severity one
(`DeviceTelemetryRuleEvaluator::evaluateAll()`); critical battery supersedes low battery. Unassigned
devices fall back to global thresholds (`getForHive()` requires a hive id).

**`CheckDeviceHealth` also detects late submissions** (`submission_delay`): silent for more than
`submission_delay_multiplier` (seeded `3`) × `expected_interval_minutes`, but not yet past
`device_offline_silence_minutes`. Offline supersedes late.

**UI and routes**

| Page | Route | Permission | Replaces |
|---|---|---|---|
| Device Fleet — live health (`IotDeviceHealthEvaluator`), attention panels, open device issues, filterable device table | `GET /admin/devices/fleet` → `admin.devices.fleet` | `view-device-fleet\|manage-iot-devices` | the "Fleet Overview" half of `AnomalyDashboardController` |
| Anomaly Dashboard — hive conditions only | `admin.anomaly.dashboard` | `view-anomaly-analytics` | — |
| All Anomalies — list, filters, detail, acknowledge/resolve | `admin.anomaly.anomalies.{index,show,acknowledge,resolve}` | `view-anomaly-analytics\|view-device-fleet` | — |
| Analytics heatmap — hive conditions only | `admin.anomaly.analytics` | `view-anomaly-analytics` | — |
| Per-device detail (UC-IOT-10) — health, 7-day trend, 24h submission rate, incidents | on the device registry page `admin.iot-devices.show` | `manage-iot-devices` | `AnomalyDeviceDetailController` / `admin.anomaly.devices.show` (removed) |
| System Alerts — every alert sent to farmers, linked to its incident | `GET /admin/alerts` → `admin.alerts.index` | `view-monitoring-dashboard\|view-hive-data` | placeholder closure |
| Hive page — open anomalies card | `admin.hives.show` | (hive permissions) | first consumer of `AnomalyStatusContract` |

The topbar bell counts devices with an open `device_offline` incident; the sidebar "System Alerts"
badge shows alerts sent in the last 24h. ML Models stays a "Soon" placeholder until Part 2.

**Controllers stay thin**, per the Development Guide: queries live in
`App\Services\Anomaly\{DeviceFleetService, AnomalyDashboardService, AnomalyIncidentService,
DeviceHealthReportService, SystemAlertService}`.

**Open items — status**

| # | Item | Status |
|---|---|---|
| 1 | Ingestion events | Done — both `event(...)` calls are live |
| 2 | Tumbling vs sliding Z-score window | Built as tumbling 24h (`RollingStatsService`); sliding window still a Phase 2 refinement |
| 3 | Python/scikit-learn runtime | **Open** — blocks Part 2 |
| 4 | `AlertService.php` fix | Done — file parses; delivery goes through `NotificationDispatchService` |
| 5 | Telemetry history retention | Done — `PruneTelemetryHistory` daily, `telemetry_history_retention_days` (90) |
| 6 | Other-module consumer of `AnomalyStatusContract` | Admin hive page uses it; Farmer Mobile API consumer **still undecided** |

**Known external dependency:** `NotificationDispatchService` is still a logging stub (Farmer API
module), so alerts are stored and visible in-app/admin but not pushed or sent by SMS.


## Existing Extension Points (read this first)

This module is **not greenfield**. The codebase already anticipates it in several places, and this
design deliberately builds on those rather than introducing a parallel structure:

| What already exists | Where | What it means for this module |
|---|---|---|
| Two commented-out event dispatches | `app/Services/IotSensorIngestionService.php:95`, `app/Services/IotHeartbeatProcessingService.php:40` | These are our ingestion hooks. We do not touch the ingestion pipeline's write path — we consume events it already left for us. |
| Three placeholder admin routes | `routes/web.php:487-494` (`admin.anomaly.dashboard`, `admin.anomaly.analytics`, `admin.anomaly.models`), already gated by `permission:view-anomaly-analytics` | We replace the closures with real controllers; no new route registration pattern needed. |
| A reserved alert type | `alerts.type` enum already includes `data_anomaly` (migration `2026_07_08_060001_create_alerts_table.php`) | Anomaly alerts use the existing `alerts` table, not a new one. |
| A stub scheduled job | `app/Jobs/CheckDeviceHealth.php`, scheduled `everyFiveMinutes()` in `app/Console/Kernel.php:13` | This *is* SRS UC-IOT-03 (server-side gap/interval detection). We implement its body, we don't create a new job for it. |
| Seeded RBAC permission group | `SuperAdminSeeder.php` / `RoleController.php` — "Monitoring & Anomaly": `view-monitoring-dashboard`, `view-device-fleet`, `view-anomaly-analytics` | We extend this group's arrays, we don't create a new group. |
| A working threshold pattern | `app/Models/AlertThreshold.php` (`Cache::remember`-backed `get()`/`getForHive()`) + `database/seeders/AlertThresholdSeeder.php` | All new Layer 1 rule constants (static ranges, stuck-value window, Z-score σ, silence window, etc.) are seeded rows here, not hardcoded — per-hive overridable for free. |
| A cross-module read precedent | `app/Services/IotDeviceIdentificationService.php` reads `Hive`/`Farm` Eloquent models directly (`$device->hive()->first()`, `$hive->farm`) rather than through a contract | We follow the same precedent for reading `hives.current_status` — see §4.4.8. |
| A cross-module contract precedent | `app/Contracts/ApiaryDirectoryServiceContract.php` | Template for the one new contract this module *does* introduce (`AnomalyStatusContract`, §4.4.5). |

One thing this document **flags rather than routes around silently**: `app/Services/Farmer/AlertService.php`
does not compile. Full ownership determination and corrective plan below.

---

## Module Phasing: Two Parts

SRS §4.6 is really two mostly-independent builds sharing one dashboard and one alert surface. This
document specifies both in full, but they should be sequenced separately because only one of them
has every dependency already resolved:

| | **Part 1 — Layer 1 + Telemetry + Dashboard + Alerts** | **Part 2 — Layer 2 (ML)** |
|---|---|---|
| Covers | Gap/interval detection (`CheckDeviceHealth`), the four rule evaluators, `hive_rolling_stats`, `sensor_anomalies`, `iot_device_telemetry_history`, all three dashboard controllers, `AnomalyAlertDispatchService` | `ml_model_versions`, `MODULES/ml_scripts/`, `MlTrainingInvoker`/`MlScoringInvoker`, `TrainAnomalyModels`/`ScoreHiveAnomalies` jobs |
| SDD sections | §4.4.2 (all but `ml_model_versions`), §4.4.3 (all but `MlModelVersion`), §4.4.4 (all), §4.4.5 (Rules Engine, Supporting Services, Listeners, `CheckDeviceHealth`), §4.4.6 (migrations 1–3) | §4.4.2 (`ml_model_versions`), §4.4.3 (`MlModelVersion`), §4.4.5 (ML sub-section), §4.4.6 (migration 4) |
| Blocking dependency | None — every table, event, route, and service it needs either already exists or is fully specified here | Open Item #3: unconfirmed Python 3/scikit-learn runtime in the deployment environment. Everything else about it (file contract, invocation pattern, validation gate) is fully specified and does not need to wait. |
| Can ship alone? | Yes — SRS's rules engine + dashboard already deliver real anomaly detection and alerting without ML | Not standalone — Layer 2 augments Layer 1's `sensor_anomalies` table and dashboard, it doesn't replace them |

Recommendation carried into "the way forward": build Part 1 first, in full, with tests, as one
coherent slice; resolve Open Item #3 in parallel; build Part 2 once that's answered. The `ml_model_versions`
table/model can be created with Part 1 (it has no dependency on the Python runtime existing yet —
it's just a table) or held for Part 2, whichever is more convenient at the time; it doesn't force
the sequencing either way.

---

## Dependency Risk: `AlertService.php` — Ownership and Corrective Plan

### Whose file is this?

Per the Development Guide's own §4.2 Migration Ownership table, the `alerts` table (along with
`farmer_messages`, `notification_logs`, `farmer_audit_logs`) is explicitly assigned to
**Developer D — Farmer Mobile API Backend (SRS §4.8)**, not Developer E (this module). Corroborating
evidence in the file itself: it lives at `app/Services/Farmer/AlertService.php` (the `Farmer`
namespace, not `Anomaly` or `Iot`), and its `evaluateThresholds()`/notification methods carry
`REQ-F-FAPI-24` through `30` in their doc comments — Farmer API requirement IDs, not IoT ones. This
is unambiguous: **it is not this module's file to silently rewrite as part of its own scope**, the
same way this document already declined to edit `IotSensorIngestionService.php` without flagging it
first (§4.4.8).

### What's actually broken (re-verified by full read, not a skim)

The earlier note in this document undersold it. Reading the complete file line-by-line:

1. **Duplicate `createAlert()` declaration** — `createAlert(int $farmerId, int $hiveId, string
   $type, string $message): ?Alert` (lines 79–99, includes the `isWithinCooldown()` check) and a
   second, incompatible `createAlert(array $data): Alert` (lines 187–200, **no cooldown check at
   all**). This is a PHP fatal at parse time ("Cannot redeclare AlertService::createAlert()") — the
   class cannot be loaded, not just "cannot run one branch of it." The array-based overload is also
   a silent correctness regression on top of being a duplicate: it bypasses cooldown entirely.
2. **`sendPushNotification` (lines 131–182)** contains an orphaned `$alert->update(['is_read' =>
   true, 'read_at' => now()])` call with no `$alert` variable anywhere in this method's scope
   (looks like a stray fragment of `markRead`/`markAsRead` pasted into the wrong place), and
   references `$success` on lines 163/167 that is never assigned anywhere in the method.
3. **`sendEmailNotification` (lines 202–252)** never calls `Mail::` despite importing
   `Illuminate\Support\Facades\Mail` — it doesn't send an email at all. Its body instead contains a
   `foreach ($hives as $hive) { ... $this->checkFeedRequired($hive, $farmer->id, $weightThreshold);
   }` loop — logic that belongs to `evaluateThresholds()`, not here.
4. **`sendSmsNotification` (lines 254–298)** has the same undefined-`$success` bug as
   `sendPushNotification`.
5. A duplicate `use Illuminate\Support\Facades\Log;` import (lines 13 and 15).

### Blast radius (confirmed by grep across the whole app, not assumed)

Because the failure is a parse-time fatal, *every* consumer that resolves `AlertService` out of the
container breaks the instant Laravel tries to construct it — regardless of whether the broken
method is the one being called. Three live consumers were found:

- `app/Jobs/CheckFeedAlerts.php` — scheduled **hourly**, already calls `evaluateThresholds()`.
- `app/Jobs/CheckDeviceHealth.php` — scheduled **every 5 minutes**; its `handle()` body is
  currently just a TODO, but it still type-hints `AlertService` as a parameter, so the job already
  fails before reaching its own stub logic.
- `app/Http/Controllers/Api/Farmer/AlertController.php` — constructor-injects `AlertService`,
  backing two **live, routed** Farmer API endpoints: `GET /api/v1/farmer/alerts` and `PATCH
  /api/v1/farmer/alerts/{alert_id}/read` (`routes/farmer_api.php:78-79`). **These two endpoints are
  down right now**, independent of anything in this module.

Also confirmed by grep: `sendPushNotification`, `sendEmailNotification`, and `sendSmsNotification`
have **zero call sites anywhere in the app** outside their own definitions. Nothing currently
depends on their (broken) bodies.

### Corrective plan

Don't attempt to reconstruct the intended bodies of the three `send*Notification` methods — with
zero callers, there's nothing to infer their correct behavior from, and it would be guessing. The
architecturally correct fix is smaller than a repair:

1. **Delete all three `send*Notification` methods**, plus the now-unused private `getFcmAccessToken()`
   helper. This is not a compromise forced by the corruption — `App\Services\Farmer\NotificationDispatchService`
   already exists as the single designated delivery layer, with its own `sendPush()`/`sendSms()`
   stub methods explicitly reserved for this exact purpose (see its docblock). `AlertService::createAlert()`
   (the surviving one) already correctly delegates to it: `$this->notifications->dispatch($alert)`
   (line 96). Keeping three more, broken, uncalled, parallel send methods on `AlertService` would
   just be duplicated responsibility even once fixed.
2. **Delete the `createAlert(array $data): Alert` overload** (lines 187–200) — keep the original
   `createAlert(int $farmerId, int $hiveId, string $type, string $message): ?Alert` (lines 79–99),
   since it's the one with the cooldown check and the one every real call path (`checkFeedRequired`)
   actually uses.
3. **Remove the duplicate `Log` import** (line 15).
4. Net result: `AlertService` returns to being the single-responsibility "alert CRUD + cooldown +
   feed-threshold evaluation" class its constructor already implies (`fetchForFarmer`, `getAlerts`,
   `markRead`, `markAsRead`, `evaluateThresholds`, `createAlert`, `isWithinCooldown`,
   `checkFeedRequired`), with all delivery going through `NotificationDispatchService` — which is
   exactly the shape `AnomalyAlertDispatchService` (§4.4.5) is designed to sit alongside, not
   inherit corruption from.

### Whose commit is this?

Per the guide's ownership table, this is Developer D's file. In a real multi-person team this would
be raised to them and merged as its own `[FAPI]`-tagged commit, separate from this module's
`[ANOMALY]`-tagged work — not bundled into this module's PR. That convention is worth keeping even
in a solo session: applying this fix (whenever it's applied) as its own small, separately-committed
change keeps the git history honest about which module a change belongs to, and keeps this
module's own PR reviewable on its own terms. **Not applying this fix is not a blocker for Part 1**
of this module — `AnomalyAlertDispatchService` depends on `NotificationDispatchService` directly,
never on `AlertService` — so it can be scheduled independently of this module's own build order.

---

## 4.4.1 Sub-Modules Covered

Per SRS §4.6.1, this document covers all three:

1. **Device Telemetry Collection** — heartbeat reception and telemetry piggybacking are already
   implemented by the IoT Data Receiver module; this document covers only the piece that isn't
   built yet: **server-side gap and interval detection** (SRS UC-IOT-03).
2. **Anomaly Detection Engine** — Layer 1 real-time rules engine (static threshold, stuck/frozen
   sensor, rolling Z-score, device telemetry thresholds) and Layer 2 batch ML pipeline (Isolation
   Forest now, Random Forest as Phase 1b, LSTM deferred to Phase 2 per SRS).
3. **Dashboard Analytics and Alert Dispatch** — Fleet Overview, Per-Device Detail, Anomaly
   Analytics, and the alert routing/dispatch table.

## 4.4.2 Database Tables

Four new tables. All follow the schema rules already established by the sensor tables (named FK
constraints, mandatory `(hive_id, created_at)`-style compound indexes, explicit rationale comments)
— see `database/migrations/2026_07_24_122312_create_hive_temperatures_table.php` as the reference
pattern being mirrored below. One existing table gets one optional additive column.

### `sensor_anomalies` (new)

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| `id` | bigint (PK) | — | — | |
| `device_id` | unsignedBigInteger, FK → `iot_devices.id` | no | — | `onDelete('restrict')`, named constraint `fk_sensor_anomalies_device` |
| `hive_id` | unsignedBigInteger, FK → `hives.id` | yes | null | `onDelete('cascade')`, named constraint `fk_sensor_anomalies_hive`. Nullable because a device-telemetry anomaly (e.g. `low_battery`) may not always resolve to a hive if the device is unassigned. |
| `sensor_type` | string | no | — | `temperature\|humidity\|co2\|weight\|telemetry` — matches `IotSensorIngestionService`'s existing match arms plus a `telemetry` arm for device-health anomalies |
| `anomaly_type` | string | no | — | e.g. `static_threshold_breach`, `frozen_sensor`, `statistical_deviation`, `low_battery`, `critical_battery`, `weak_signal`, `reboot_loop`, `storage_full`, `device_offline`, `submission_delay`, `ml_isolation_forest`, `ml_random_forest` |
| `anomaly_score` | float | yes | null | `1.0` for rules-layer certainty per SRS; the model's raw score for ML layers |
| `record_value` | json | no | — | the flagged value(s), e.g. `{"brood_section": 71.2}` |
| `detection_layer` | string | no | — | `rules\|ml\|rf\|lstm` |
| `detected_at` | timestamp | no | — | |
| `alerted` | boolean | no | `false` | |
| `alerted_at` | timestamp | yes | null | |
| `resolved` | boolean | no | `false` | |
| `resolved_at` | timestamp | yes | null | |
| `created_at` | timestamp | no | `useCurrent()` | no `updated_at`; lifecycle columns carry their own timestamps |
| `last_seen_at` | timestamp | yes | null | *(as built)* most recent detection of this incident |
| `occurrences` | unsignedInteger | no | `1` | *(as built)* detections folded into this incident |
| `last_record_value` | json | yes | null | *(as built)* most recent flagged value(s) |
| `acknowledged_at` / `acknowledged_by` | timestamp / FK → `users.id` | yes | null | *(as built)* `nullOnDelete` |
| `resolved_by` | FK → `users.id` | yes | null | *(as built)* null when auto-resolved |
| `resolution_note` | text | yes | null | *(as built)* |
| `auto_resolved` | boolean | no | `false` | *(as built)* |

Indexes: `(hive_id, detected_at)` and `(device_id, detected_at)` — **mandatory**, these directly
serve "last 20 anomalies per device" (UC-IOT-10) and the "7-day anomaly heatmap per hive"
(UC-IOT-11) dashboard queries. *(As built)* plus `(device_id, anomaly_type, resolved)` for the open-incident lookup. Soft delete: **no** — anomaly records are permanent evidence and
ML training-label history per SRS REQ-F-IOT-13 ("never deleted").

### `ml_model_versions` (new)

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| `id` | bigint (PK) | — | — | |
| `hive_id` | unsignedBigInteger, FK → `hives.id` | no | — | `onDelete('cascade')`, named constraint `fk_ml_model_versions_hive` |
| `model_type` | string | no | — | `isolation_forest\|random_forest\|lstm` |
| `model_path` | string | no | — | relative path under `MODULES/ml_models/` |
| `training_window_start` | timestamp | no | — | |
| `training_window_end` | timestamp | no | — | |
| `contamination` | float | yes | `0.05` | Isolation Forest hyperparameter |
| `validation_fpr` | float | yes | null | set by the training script; null until validation runs |
| `is_active` | boolean | no | `false` | app-layer enforced: only one `(hive_id, model_type)` pair may be active — see §4.4.9(a) |
| `trained_at` | timestamp | no | — | |
| `created_at`/`updated_at` | timestamps | — | — | standard `$table->timestamps()`; `updated_at` changes when `is_active` is flipped (rollback/promotion) |

Index: `(hive_id, model_type, is_active)`. Soft delete: no — old versions are kept for rollback per
SRS, never deleted, just deactivated.

### `iot_device_telemetry_history` (new, append-only)

Same column shape as the existing `iot_device_telemetry` table (see `app/Models/IotDeviceTelemetry.php`)
plus a `recorded_at` timestamp, because **`iot_device_telemetry` is a single upsert-per-device row**
(`IotDeviceTelemetry::updateOrCreate(['device_id' => ...], [...])` in
`IotHeartbeatProcessingService::store()`) — it has no history to chart. See §4.4.9(e) for why this
is a *new* table rather than converting the existing one to insert-only.

| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigint (PK) | — | |
| `device_id` | unsignedBigInteger, FK → `iot_devices.id` | no | `onDelete('cascade')`, named constraint `fk_iot_device_telemetry_history_device` |
| `battery_level`, `signal_strength`, `uptime_seconds`, `cpu_usage`, `storage_usage`, `reboot_count`, `sensor_read_success_rate` | float/integer as in `iot_device_telemetry` | yes | copied verbatim from the heartbeat payload at insert time |
| `error_codes` | json | yes | |
| `recorded_at` | timestamp | no | the heartbeat's timestamp |
| `created_at` | timestamp | no, `useCurrent()` | append-only, no `updated_at` |

Index: `(device_id, recorded_at)` — **mandatory**, serves the 7-day battery/signal trend charts and
24h submission-rate bar chart directly. Soft delete: no. **Retention policy: open item, see below.**

### `hive_rolling_stats` (new)

| Column | Type | Nullable | Default | Notes |
|---|---|---|---|---|
| `id` | bigint (PK) | — | — | |
| `hive_id` | unsignedBigInteger, FK → `hives.id` | no | — | `onDelete('cascade')`, named constraint `fk_hive_rolling_stats_hive` |
| `sensor_type` | string | no | — | `temperature\|humidity\|co2\|weight` |
| `channel` | string | yes | null | `honey_section\|brood_section\|exterior` for 3-zone sensors; null for `co2`/`weight` (single-value) |
| `mean` | float | no | `0` | |
| `variance` | float | no | `0` | Welford's algorithm accumulator — see §4.4.9(b) |
| `sample_count` | unsignedInteger | no | `0` | |
| `window_start` | timestamp | yes | null | start of the current tumbling window |
| `updated_at` | timestamp | no | — | `$table->timestamps()` |

Unique constraint: `(hive_id, sensor_type, channel)`. Soft delete: no.

### Additive change to an existing table

`alerts` gets one **optional** nullable column, its own tiny migration, only if §4.4.9(f) is
adopted: `source_anomaly_id` (unsignedBigInteger, nullable, FK → `sensor_anomalies.id`,
`onDelete('set null')`, named constraint `fk_alerts_source_anomaly`) — lets the dashboard join an
alert back to the anomaly detail that triggered it. Everything else about `alerts` is reused as-is.

### Tables explicitly reused, unmodified

`iot_devices`, `hives`, `iot_device_telemetry`, `alerts`, `alert_thresholds`,
`iot_ingestion_logs`. No `dashboard_alerts` table is created — see §4.4.9(f).

## 4.4.3 Eloquent Models

New models, all under `app/Models/`, each with explicit `$fillable` and `$casts` per the Development
Guide's model rules (no `$guarded = []`):

- **`SensorAnomaly`** — `$fillable` = all columns except `id`/`created_at`. `$casts`:
  `record_value` → `array`, `alerted`/`resolved` → `boolean`, `detected_at`/`alerted_at`/`resolved_at`
  → `datetime`. Relations: `belongsTo(IotDevice::class, 'device_id')`, `belongsTo(Hive::class, 'hive_id')`.
- **`MlModelVersion`** — `$fillable` = all columns except `id`/timestamps. `$casts`:
  `is_active` → `boolean`, `contamination`/`validation_fpr` → `float`, window/`trained_at` →
  `datetime`. Relation: `belongsTo(Hive::class)`.
- **`IotDeviceTelemetryHistory`** — mirrors `IotDeviceTelemetry`'s `$fillable`/`$casts` plus
  `recorded_at` → `datetime`. Relation: `belongsTo(IotDevice::class, 'device_id')`.
- **`HiveRollingStat`** — `$fillable` = `hive_id, sensor_type, channel, mean, variance,
  sample_count, window_start`. `$casts`: `mean`/`variance` → `float`, `sample_count` → `integer`,
  `window_start` → `datetime`. Relation: `belongsTo(Hive::class)`.

Two small, additive changes to *existing* models — both safe because they only uncomment/extend
code already left for this purpose, they don't touch logic owned by other modules:

- `app/Models/IotDevice.php` — uncomment the `telemetry(): HasOne` relation (lines 49–52) pointing
  at `IotDeviceTelemetry`, needed by the Fleet Overview and Per-Device Detail queries. Leave
  `ingestionLogs()` commented unless a concrete dashboard need for it emerges.
- `app/Models/Alert.php` — add `cooldownMinutesFor(string $type): ?int`, replacing the current
  flat "1 hour, only `malfunction` exempt" model (`isCooldownExempt()`, line 42–45) with a small
  keyed map, since SRS requires `critical_battery` = 15 min and `device_offline` = no cooldown
  until recovery, not just a single exemption:
  ```php
  public function cooldownMinutesFor(string $type): ?int
  {
      return match ($type) {
          'malfunction', 'critical_event' => null,   // no cooldown, always fires
          'critical_battery' => 15,
          'device_offline' => null,                  // no cooldown until recovery (handled separately)
          default => 60,
      };
  }
  ```
  `isCooldownExempt()` stays as-is (still used by `AlertService::createAlert()`, however broken
  that file currently is) — this is a pure addition, not a breaking change to the model's public
  surface.

## 4.4.4 Controller and Route Design

All three placeholder closures in `routes/web.php:487-494` are replaced with real controllers,
kept under the same existing `permission:view-anomaly-analytics` middleware group — no route
registration changes needed beyond swapping the closure body for `Controller::class, 'method'`.

| Controller | Namespace | Methods | Route (existing name preserved) |
|---|---|---|---|
| `AnomalyDashboardController` | `App\Http\Controllers\Admin` | `index()` | `GET /admin/anomaly/dashboard` → `admin.anomaly.dashboard` |
| `AnomalyAnalyticsController` | `App\Http\Controllers\Admin` | `index(Request $request)` (accepts `?days=7\|30` filter) | `GET /admin/anomaly/analytics` → `admin.anomaly.analytics` |
| `AnomalyModelsController` | `App\Http\Controllers\Admin` | `index()`, `activate(MlModelVersion $model)` | `GET /admin/anomaly/models` → `admin.anomaly.models`; new `POST /admin/anomaly/models/{model}/activate` → `admin.anomaly.models.activate` |

One net-new route not currently stubbed anywhere, required by SRS UC-IOT-10 (Per-Device Detail),
added under the same middleware group:

- `AnomalyDeviceDetailController::show(IotDevice $device)` — `GET /admin/anomaly/devices/{device}`
  → `admin.anomaly.devices.show`.

Per Development Guide coding standards: controllers contain no business logic — each method
resolves its service class from the container, calls one method, passes the result to a Blade view
under `resources/views/admin/anomaly/`. No raw `DB::` queries in any controller.

The `AnomalyModelsController::activate()` action needs its own permission
(`manage-anomaly-models`, additive to the seeder — see §4.4.7) since rolling back a model is a
write action, distinct from the read-only `view-anomaly-analytics` permission the other three
routes use.

## 4.4.5 Service Classes

All new classes under `App\Services\Anomaly\` (new subdirectory, following the existing
`App\Services\{Admin,ApiaryManagement,Farmer,Iot}` convention):

**Layer 1 — Rules Engine** (`App\Services\Anomaly\RulesEngine\`)
- `ThresholdRuleEvaluator` — static physically-plausible-range check (temperature -10–60°C, CO₂
  0–5000ppm, weight 5–120kg, humidity 0–100%). Thresholds read via `AlertThreshold::getForHive()`,
  not hardcoded.
- `StuckSensorRuleEvaluator` — last-10-consecutive-identical-value check per `(device_id,
  sensor_type)`; queries the relevant typed sensor table using the existing `(hive_id, created_at)`
  index.
- `ZScoreRuleEvaluator` — reads `HiveRollingStat` (via `RollingStatsService`, cached), flags if
  `|reading - mean| > 3 * sqrt(variance)`.
- `DeviceTelemetryRuleEvaluator` — battery/RSSI/reboot/storage threshold checks against the
  `IotDeviceTelemetry` row just written.

Each evaluator has one method, e.g. `evaluate(HiveTemperature $reading): ?SensorAnomaly`
(or the telemetry equivalent), returning `null` on no violation or a persisted `SensorAnomaly` on
violation — composed by the listeners below, which run them in sequence and stop at first hit
(per SRS: "stopping at the first violation found").

**Supporting services** (`App\Services\Anomaly\`)
- `RollingStatsService` — `updateAndGet(int $hiveId, string $sensorType, ?string $channel, float
  $value): HiveRollingStat`. Incremental Welford update against `hive_rolling_stats`, fronted by a
  `Cache::remember('rolling_stats:{hive}:{sensor}:{channel}', 60, ...)` read, mirroring
  `AlertThreshold::get()`'s exact idiom (`app/Models/AlertThreshold.php:21-26`).
- `AnomalyAlertDispatchService` — `dispatch(SensorAnomaly $anomaly): ?Alert`. Resolves the target
  farmer via `$anomaly->hive->farm->farmer` (same relation chain
  `IotDeviceIdentificationService` already uses), checks `Alert::cooldownMinutesFor()`, writes
  `Alert::create([...])` directly, then calls the existing, syntactically-valid
  `App\Services\Farmer\NotificationDispatchService::dispatch($alert)` for delivery. **Deliberately
  does not depend on `App\Services\Farmer\AlertService`** — see §4.4.9(d).
- `AnomalyStatusService implements App\Contracts\AnomalyStatusContract` — the module's public
  surface for other modules (§4.4.8).

**ML** (`App\Services\Anomaly\Ml\`)
- `MlTrainingInvoker` — `train(int $hiveId, CarbonPeriod $window): MlModelVersion`. Wraps
  `Illuminate\Support\Facades\Process` (Laravel 13's native process facade — **not** `shell_exec`;
  confirmed by repo-wide grep that no `shell_exec`/`Process::` precedent exists yet, so this is a
  fresh convention, not a copy of one). Exports a CSV snapshot of the training window's readings to
  a tmp path, invokes `MODULES/ml_scripts/train_isolation_forest.py --hive-id=... --window-start=...
  --window-end=... --input=<csv> --out=<json>`, reads the JSON result file (never stdout — stdout
  is reserved for human-readable logs Laravel captures on failure), creates the `MlModelVersion`
  row, and only sets `is_active=true` if the script's own `validation_fpr <= 0.15` gate passed
  (enforced in Python, checked here by reading the JSON field + process exit code).
- `MlScoringInvoker` — `score(int $hiveId, Carbon $since): Collection<SensorAnomaly>`. Same
  `Process`-based pattern, invokes `score_isolation_forest.py`, reads back a JSON list of flagged
  readings, persists each as a `SensorAnomaly` with `detection_layer = 'ml'`.

**Listeners** (`App\Listeners\Anomaly\`) — plain listeners (do **not** implement `ShouldQueue`):
- `EvaluateSensorReadingRules` — subscribes to `App\Events\SensorRecordReceived`. Runs the four
  rule evaluators against the just-persisted reading, in order, stopping at first match; persists
  any resulting `SensorAnomaly` and calls `AnomalyAlertDispatchService::dispatch()`.
- `EvaluateDeviceTelemetryRules` — subscribes to `App\Events\DeviceTelemetryReceived`. Runs
  `DeviceTelemetryRuleEvaluator`; also inserts the `IotDeviceTelemetryHistory` row here (this is
  the simplest place to do it, since the event already carries the just-written `IotDevice`/
  telemetry state).

Both listeners are synchronous by design — see §4.4.9(a) for why.

**Jobs** (`App\Jobs\`, same directory as existing jobs, following the exact pattern of
`CheckFeedAlerts`/`CheckDeviceHealth` — `implements ShouldQueue`, uses
`Dispatchable, InteractsWithQueue, Queueable, SerializesModels`, injected via `handle()` method
parameters):
- **`CheckDeviceHealth` (existing file — implement its body, don't create a new job).** Iterate
  active `IotDevice`s, compute `time_since_last_contact` from `iot_device_telemetry.last_heartbeat_at`
  /`last_data_received_at` against `expected_interval_minutes` and the seeded
  `device_offline_silence_minutes` threshold (default 120). Create/resolve `device_offline`
  `SensorAnomaly`+`Alert` via the same dispatch service used by the listeners, so gap detection and
  Layer 1 rule violations share one alerting path. This directly replaces the job's current
  `// TODO` body and its already-accurate inline comments (lines 26–53) describing exactly this
  logic.
- **`TrainAnomalyModels` (new)** — `$schedule->job(new TrainAnomalyModels())->weekly()`, added
  as a new line in `Kernel.php` alongside the two existing `$schedule->job(...)` calls. Iterates
  hives with ≥4 weeks of sensor data, calls `MlTrainingInvoker::train()` per hive.
- **`ScoreHiveAnomalies` (new)** — `$schedule->job(new ScoreHiveAnomalies())->everyThirtyMinutes()`.
  Iterates hives with an active `ml_model_versions` row, calls `MlScoringInvoker::score()`.

## 4.4.6 Migrations List

In dependency order (all depend only on already-existing `hives`/`iot_devices`; no dependencies
between items 1–4):

1. `create_hive_rolling_stats_table`
2. `create_iot_device_telemetry_history_table`
3. `create_sensor_anomalies_table`
4. `create_ml_model_versions_table`
5. `add_source_anomaly_id_to_alerts_table` — **only if** §4.4.9(f)'s optional FK is adopted; must
   run after #3 since it references `sensor_anomalies`.

File naming follows the repo's actual convention (confirmed from `database/migrations/`, e.g.
`2026_08_10_000001_add_hive_id_to_alert_thresholds_table.php`): `YYYY_MM_DD_HHMMSS_verb_table.php`,
dated after the latest existing migration (`2026_08_10_000001_...`).

## 4.4.7 Seeders

No new seeder files — extend two existing ones, per the repo's established convention of adding to
shared seeder arrays rather than creating per-module seeders:

- **`database/seeders/SuperAdminSeeder.php`** (`$permissions` array, "Monitoring & anomaly"
  section, currently lines 46–49) and **`app/Http/Controllers/Admin/RoleController.php`**
  (`$systemPermissions` lines 40–43 and the `'Monitoring & Anomaly'` group in
  `$permissionGroups`, lines 78–81) — add one new permission: `manage-anomaly-models` (for the
  model-rollback action in §4.4.4). Both arrays must be updated together, exactly as the existing
  three entries already are duplicated across both files.
- **`database/seeders/AlertThresholdSeeder.php`** — add the Layer 1 rule constants as global
  (`hive_id` null) rows, using the exact existing idiom (`AlertThreshold::updateOrCreate(['key' =>
  ...], ['value' => ..., 'description' => ...])`, see lines 15–28 of that file):
  `temp_min_c` (-10), `temp_max_c` (60), `co2_max_ppm` (5000), `weight_min_kg` (5),
  `weight_max_kg` (120), `stuck_sensor_reading_count` (10), `zscore_stddev_threshold` (3),
  `device_offline_silence_minutes` (120), `low_battery_pct` (20), `critical_battery_pct` (5),
  `weak_signal_rssi_dbm` (-85), `reboot_loop_count_per_hour` (3), `storage_full_pct` (90). Every
  one of these becomes per-hive-overridable for free via `AlertThreshold::getForHive()`, with no
  extra code.

## 4.4.8 Interactions with Other Modules

| Direction | Module | Interface | Notes |
|---|---|---|---|
| **Reads from** | IoT Data Receiver (owns `IotSensorIngestionService`, `IotHeartbeatProcessingService`) | `App\Events\SensorRecordReceived`, `App\Events\DeviceTelemetryReceived` | These two event classes don't exist yet. Creating them, and uncommenting the two `event(...)` lines that reference them, is a **cross-module touch** — flagged in Open Items below for the ingestion module's owner to confirm, even though the comment at `IotSensorIngestionService.php:93-94` ("stays exactly as already designed in §4.5.8 — decoupled, not called directly") strongly suggests this was pre-approved by whoever wrote that comment. |
| **Reads from** | Apiary Management (owns `hives`, `Hive` model) | Direct `Hive`/`Farm` Eloquent model reads (`$anomaly->hive`, `$hive->farm`) | Follows the precedent already set by `IotDeviceIdentificationService::resolveHiveAndFarm()` (`app/Services/IotDeviceIdentificationService.php:26-42`), which reads `Hive`/`Farm` directly rather than through `ApiaryDirectoryServiceContract`. That contract is scoped specifically to the device-assignment wizard's listing needs (`listApiariesWithPrimaryFarmer`, `listHivesAvailableForDeviceType`) and isn't the right fit for a simple `current_status` read — using it here would be a misuse of a narrowly-scoped interface, not a boundary win. |
| **Writes to** | shared `alerts` / `NotificationLog` surface | `Alert::create()` + `App\Services\Farmer\NotificationDispatchService::dispatch()` | Not `AlertService` — see §4.4.9(d). Note `NotificationDispatchService` is *currently also a stub* (logs only, per its own docblock) pending FCM/Africa's Talking credentials and a working `farmers.fcm_token` column — this module's alerts will be created correctly and will appear in-app, but push/SMS delivery depends on that other work landing first, independent of this design. |
| **Exposes to** other modules | new `App\Contracts\AnomalyStatusContract` | `hasUnresolvedAnomalies(int $hiveId): bool`, `latestAnomalies(int $hiveId, int $limit = 5): Collection` | Implemented by `AnomalyStatusService`. Modeled directly on `ApiaryDirectoryServiceContract`'s doc-comment style (`app/Contracts/ApiaryDirectoryServiceContract.php:7-14`): state who owns it, state the real implementation class, warn against changing the signature without coordination. Rationale for needing this at all: `alerts` is a *notification* record subject to cooldown suppression — a hive can have a live unresolved anomaly with no recent `alerts` row. No confirmed consumer yet for v1 (see Open Items). |

## 4.4.9 Known Design Decisions and Rationale

**(a) Layer 1 runs as synchronous (non-queued) event listeners, not inline inside the ingestion
services, and not as queued listeners either.**
The SRS's "evaluated ... before it is written to the database" is satisfied in spirit, not
literally: the raw reading must never be blocked or rejected by rule evaluation (data-integrity
rule — even physically implausible readings are stored, flagged). What must happen deterministically
is the *flagging*, before the single-threaded `IotQueueWorkerCommand` loop
(`app/Console/Commands/IotQueueWorkerCommand.php`) acknowledges the queue message and moves to the
next one. Firing the event at the end of `store()` with a plain listener achieves exactly that —
Laravel dispatches non-queued listeners inline, in the same call stack, before `acknowledge()`
runs. Putting the rule logic directly inside `IotSensorIngestionService::store()` instead would
couple the ingestion module to anomaly-detection logic it has no reason to know about — the two
commented-out `event()` lines are clearly the intended seam, not an oversight.

**(b) `hive_rolling_stats` is a real table with an incremental Welford update, fronted by a short
cache TTL — not a pure-SQL window-function query, and not Redis-only.**
Computing a 24h aggregate via SQL on every single ingested reading inside a synchronous listener
doesn't scale with fleet growth and re-scans data that barely changes reading-to-reading. Redis-only
would lose the stats on eviction/restart and gives nothing to plot on a trend chart or audit later.
A durable row per `(hive_id, sensor_type, channel)`, updated with a cheap O(1) incremental formula,
cached the same way `AlertThreshold` already is, is the idiomatic fit for this codebase. **This
uses a tumbling 24h window (reset on schedule), not a true sliding window** — Welford's naive
incremental form computes an all-time mean, not a decaying/sliding one; true sliding-window support
is flagged as a possible Phase 2 refinement rather than built now (see Open Items).

**(c) ML training/scoring is invoked via Laravel 13's `Process` facade with a file-based JSON
contract, from scratch — not by following an existing pattern, because none exists.**
The Development Guide's mention of a `DataReportController.php`/`shell_exec` precedent was checked
directly (repo-wide grep) and does not exist anywhere in this codebase. Rather than inventing a
brittle `shell_exec`-based convention to match a guide that describes code that isn't there,
this design uses Laravel's native `Process` facade (typed exceptions, timeouts,
`Process::fake()` testability) and a **file-based** output contract: the Python script writes a
single JSON file to a path Laravel passes it, rather than mixing structured output with library
warnings on stdout. Training data crosses the boundary as an exported CSV snapshot, not live DB
credentials handed to Python — keeping the ML scripts decoupled from the app's Eloquent schema.

**(d) Alert dispatch bypasses `AlertService.php` entirely, reusing `NotificationDispatchService`
directly instead.**
Full ownership determination, corruption detail (it's worse than a single duplicate method — see
that section), blast-radius, and corrective plan are in **"Dependency Risk: `AlertService.php`"**
near the top of this document, not repeated here. The short version: it's Developer D's file
(Farmer Mobile API Backend, per the guide's Migration Ownership table), it currently fails to
parse, and this module's `AnomalyAlertDispatchService` depends only on the already-clean
`NotificationDispatchService::dispatch()` — zero dependency on the broken file, zero duplicated
delivery logic.

**(e) `iot_device_telemetry_history` is a new append-only table, not a conversion of
`iot_device_telemetry` from upsert to insert.**
Confirmed by reading `IotHeartbeatProcessingService::store()`: it calls
`IotDeviceTelemetry::updateOrCreate(['device_id' => $device->id], [...])` — literally one row per
device, overwritten every heartbeat. That table is used as a cheap "what's the device's current
state" read (e.g. by `CheckDeviceHealth`), and other code already assumes that shape (the
commented-out `IotDevice::telemetry(): HasOne` singular relation). Changing its write behavior to
always-insert would silently break that assumption for a module we don't own. A parallel
`_history` table, populated by the same `DeviceTelemetryReceived` listener, keeps the fast
current-state read untouched while giving the dashboard the time series it needs.

**(f) No `dashboard_alerts` table — `alerts` already serves that purpose. This is a deliberate
deviation from the Development Guide's own Migration Ownership table**, which lists
`dashboard_alerts` alongside `sensor_anomalies`/`ml_model_versions`/`hive_rolling_stats` as one of
this module's (Developer E's) tables to create. Flagging the deviation explicitly rather than
silently building what the table implies: `alerts` already has `farmer_id`, `hive_id`, `type`,
`is_read`/`read_at`, and is the table `AlertService`/`NotificationDispatchService`/the Farmer API
alert endpoints already key off of. A second alert-feed table would fork the read model for no
benefit — the guide's table list predates knowing that `alerts` already covers this need. If the
dashboard needs to join from an alert back to full anomaly detail (score, record value, detection
layer), that's the optional `source_anomaly_id` FK described in §4.4.2, not a parallel table.

---

## Open Items Requiring Human Sign-Off

Per the Development Guide's workflow (§6.1, Step 2: "Get written confirmation ... before proceeding
to Step 3"), these six items should be explicitly confirmed — not assumed — before migrations are
written:

1. **Confirm with the IoT Data Receiver module's owner** that creating `SensorRecordReceived`/
   `DeviceTelemetryReceived` and uncommenting the two `event(...)` lines in their files is this
   module's change to make (the code comments strongly suggest yes, but those two files aren't
   this module's to edit unilaterally without a nod).
2. **Confirm the tumbling-vs-true-sliding 24h window simplification** for the Z-score check
   (§4.4.9b) is acceptable for v1, or whether true sliding-window support is a hard requirement now
   rather than a Phase 2 refinement.
3. **Confirm Python 3 + scikit-learn availability** in whatever environment runs the Laravel
   scheduler/queue worker (same container as `iot:work`, or a separate box?) — this determines
   whether `Process::run(['python3', ...])` even has a binary to find. Also confirm the
   CSV-export-to-Python approach (vs. giving Python direct DB credentials) is acceptable
   infra-wise.
4. **Decide when to apply the `AlertService.php` fix.** It's Developer D's file (per Migration
   Ownership), it currently fails to parse, and it already takes down two live Farmer API routes
   and the hourly `CheckFeedAlerts` job — independent of this module. The corrective plan is fully
   specified in "Dependency Risk: `AlertService.php`" above and is safe to apply any time (nothing
   in this module depends on it being fixed first). Decide only *when* to land it — as its own
   `[FAPI]`-tagged commit before, during, or after this module's Part 1 — not *whether*.
5. **Confirm a retention/growth policy** for `iot_device_telemetry_history` — one row per device
   per heartbeat (interval configurable per `iot_devices.expected_interval_minutes`, commonly every
   few minutes) times fleet size grows fast. Options: prune rows older than N days, or downsample
   to hourly after 7 days. Not designed here; flagged for a decision before this ships to
   production scale.
6. **Confirm whether any other module needs `AnomalyStatusContract` wired as a real consumer for
   v1** (e.g. does the Farmer Mobile API dashboard need "does my hive have an active anomaly" now,
   or is that a later phase?) — the interface is cheap to define now either way; the question is
   only whether v1 needs a second module actually calling it.

## SRS Traceability (spot-check)

| SRS REQ | Satisfied by |
|---|---|
| REQ-F-IOT-03 (gap/interval detection) | `CheckDeviceHealth` job body (§4.4.5) — `device_offline` + `submission_delay` |
| REQ-F-IOT-05 (static threshold) | `ThresholdRuleEvaluator` |
| REQ-F-IOT-06 (stuck value) | `StuckSensorRuleEvaluator` |
| REQ-F-IOT-07 (rolling Z-score) | `ZScoreRuleEvaluator` + `RollingStatsService` + `hive_rolling_stats` |
| REQ-F-IOT-08 (device telemetry thresholds) | `DeviceTelemetryRuleEvaluator` |
| REQ-F-IOT-09/10 (Isolation Forest train/score) | `MlTrainingInvoker`/`TrainAnomalyModels`, `MlScoringInvoker`/`ScoreHiveAnomalies` |
| REQ-F-IOT-11 (Random Forest, Phase 1b) | Same `Ml*` service pattern, deferred — new script + real-time scoring hook added to the sensor-reading listener once Phase 1b is greenlit |
| REQ-F-IOT-12 (LSTM, Phase 2) | Deferred per SRS; `MODULES/` layout leaves room, no design committed here |
| REQ-F-IOT-13 (`sensor_anomalies` table) | §4.4.2 |
| REQ-F-IOT-14/15/16 (dashboards) | *(as built)* `DeviceFleetController`, `AnomalyDashboardController`, `AnomalyController`, `AnomalyAnalyticsController`, device health on `IotDeviceRegistryController@show` |
| REQ-F-IOT-17 (alert routing) | `AnomalyAlertDispatchService` + `Alert::cooldownMinutesFor()`; admin view `SystemAlertController` |
| REQ-F-IOT-18 (model versioning) | `ml_model_versions` + `AnomalyModelsController` rollback action |
| REQ-F-IOT-19 (device auth) | Already implemented by the IoT Data Receiver module (`IotDeviceAuthenticationService`) — no change needed here |

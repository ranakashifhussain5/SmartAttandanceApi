# Project Handoff Document
## University Smart Attendance System — ESP32-S3 BLE Beacon

---

## 1. Executive Summary

This project is a university Final Year Project (FYP) building a smart, hard-to-bypass
student attendance system. The system uses ESP32-S3 microcontroller boards installed
in each classroom as BLE beacons, a mobile app that verifies student presence via
GPS geofencing + BLE/QR scanning, and a backend server that performs final validation.
No paid APIs, no facial recognition, and no expensive commercial hardware are used —
the entire stack is built on open tools and ~$10/classroom DIY beacons.

---

## 2. Current Status

### Fully completed
- Full system architecture designed (2-layer: GPS → BLE → Server; WiFi and the
  rotating-token layer were both dropped — see Decision Log in Section 4)
- Physical ESP32-S3 beacon **already flashed and running** — one board, one room,
  which is all this FYP demo needs. It broadcasts UUID + Major(room number) +
  Minor. The firmware **source code has been deleted from this repo** (was in
  `esp32s3-attendance-beacon/`) since only one already-flashed beacon is needed —
  re-flashing/multi-room tooling isn't required for this scope. If more beacons
  are ever needed later, that firmware would need to be rewritten from scratch
  (see the "no rotating token" design in the Decision Log below to reproduce it).
- Backend (Laravel, this repo) **updated** — `attendance/mark` endpoint now
  validates GPS geofence + RSSI threshold + detected room Major instead of WiFi MAC.
  It does **not** read or validate any Minor/token value — even though the
  already-flashed beacon still broadcasts one, the app/server simply ignore it.
- `suspicious_attempts` table added — every rejected submission is logged with a reason

### Partially done
- Mobile app: architecture decided (Flutter, per user request), not yet built
- Backend: code written, **not yet migrated/tested against a live database**
  (MySQL wasn't running locally when last checked)

### Needs to happen next
- Start MySQL locally, run `php artisan migrate`, sanity-check the new schema
- Build the Flutter app: GPS check → BLE scan (read UUID/Major, RSSI — ignore Minor)
  → submit to `POST /api/attendance/mark` with
  `{session_id, detected_major, rssi, latitude, longitude}`
- Set `ATTENDANCE_CAMPUS_LAT` / `ATTENDANCE_CAMPUS_LNG` in `.env` (currently blank —
  geofence check is silently skipped until these are set)
- Register the room in the admin Room API with `beacon_major` = the Major value
  the already-flashed beacon broadcasts (check with nRF Connect)

---

## 3. Tech Stack / Frameworks

### Beacon (ESP32-S3 Firmware)
| Layer | Choice | Notes |
|---|---|---|
| Framework | ESP-IDF v5.x | NOT Arduino |
| Language | C | Standard ESP-IDF |
| BLE | Bluedroid stack | `esp_gap_ble_api.h` |
| LED | `espressif/led_strip ^2.5.0` | RMT-based WS2812B driver |
| Storage | NVS (Non-Volatile Storage) | `nvs_flash.h` |
| Watchdog | `esp_task_wdt.h` | 30s timeout |
| Logging | `esp_log.h` | 115200 baud UART0 |

### Mobile App (not yet started — Flutter chosen)
- Flutter
- Native GPS APIs (no Google Maps — budget constraint)
- Native BLE scanning APIs (read iBeacon UUID/Major + RSSI, no pairing/connect)
- Posts to this repo's Laravel API (`sanctum` auth, see `routes/api.php`)

### Backend Server (this repo — Laravel)
- Laravel + MySQL (existing project, extended rather than rebuilt)
- `AttendanceService::mark()` — GPS geofence + RSSI threshold + room-Major match
- `suspicious_attempts` table — every rejection reason logged for later review

### Hardware per beacon unit
- ESP32-S3 DevKit board
- USB cable + mobile charger (current power source)
- No battery for now (will add 18650 + TP4056 later if needed)

---

## 4. Key Decisions & Architecture

### System flow (agreed, final — updated: WiFi AND rotating token both removed)
```
Student opens app
  → Mock location check → block if detected
  → Native GPS geofence (silent/background, generous ~150m campus boundary)
     [catches remote/off-campus spoofing — no WiFi corroboration used]

  PASS → unlock attendance screen

  → BLE scan (passive advertisement only — NO pairing/connection):
    detect room beacon, read UUID + Major(room number) + RSSI

  → App sends to server:
    { session_id, detected_major, rssi, latitude, longitude }

Server checks (AttendanceService::mark):
  → Session active? (teacher-started, within timetable window)
  → Student not blocked, batch matches, not already marked (dedupe)
  → GPS within geofence?
  → RSSI >= room's threshold (default −75 dBm)?
  → detected_major == room.beacon_major?

  PASS all → attendance marked + push notification
  FAIL any → rejected + suspicious attempt logged (with fail_reason) in `suspicious_attempts`

Post-session:
  → Teacher receives summary message (X present, Y absent) — SessionService::end()
    [secondary manual oversight layer — not a substitute for GPS]
```

**Decision log:**
- WiFi SSID check **removed** — was weak corroboration (campus-wide, not room-level); BLE+RSSI already give stronger room-level proof.
- MAC address **never used** for identification — beacon identified via UUID + Major(room) only.
- Rotating HMAC token **removed** — the beacon has no WiFi and no RTC, so it cannot know
  real wall-clock time, and the server's token-window calculation (based on real time) could
  never match the beacon's (based on seconds-since-boot). Fixing this properly would need a
  ~$2 battery-backed RTC module (e.g. DS3231); user chose to drop the token instead and accept
  the residual risk below rather than add hardware.
- GPS **kept, silent/background** — now the primary defense against remote/off-campus spoofing,
  since there's no rotating token to make a captured signal single-use.
- **Known residual risk (accepted trade-off):** since the beacon's advertisement is static
  (fixed UUID+Major, never changes), a student who scans it once could in principle hardcode
  those values into a modified app and submit them from elsewhere, as long as they can also
  fake a plausible GPS location and RSSI. Mitigated by GPS geofence + RSSI threshold + teacher
  summary spot-checks, but not eliminated. Documented here so it's not accidentally presented
  as unbypassable in the FYP writeup.
- RSSI is client-reported and technically spoofable on a modified/rooted phone — same accepted
  residual risk as above.
- BLE stays **passive scan/advertisement only** — never GATT connect/pair (needed for many
  phones to read the beacon simultaneously without a connection queue).

### Critical design decisions
- **No facial recognition** — dropped by user, too complex for scope
- **No paid APIs** — native GPS only, no Google Maps
- **Beacon is 100% offline** — no WiFi on beacon ever, no RTC (see token removal above)
- **BLE = passive broadcast** — unlimited phones receive simultaneously, no pairing
- **TX power = 0 dBm** — intentionally reduced from default +9 dBm to stay within classroom
- **RSSI threshold = −75 dBm default** — server rejects weaker signals (student likely in
  corridor); overridable per room via `rooms.rssi_threshold`
- **No rolling token** — static iBeacon UUID+Major only (see Decision Log above for why)

### BLE advertisement payload (iBeacon format)
```
UUID   → fixed 16-byte room identifier (same across all beacons)
Major  → room number (uint16), e.g. 204 — matched against rooms.beacon_major
Minor  → unused, fixed at 0x0000
```

### RGB LED states (WS2812B, GPIO48)
| State | Color | Pattern |
|---|---|---|
| Booting | White | Solid 2s |
| Broadcasting OK | Green | Slow pulse 2s cycle |
| NVS error | Yellow | Fast blink |
| Fatal error | Red | Solid on |

---

## 5. Core Outputs (HISTORICAL — firmware source was deleted from this repo; the physical
board is already flashed and running, so these snippets are kept only as a reference for
rebuilding firmware from scratch if a second beacon is ever needed)

### beacon_config.h
```c
#ifndef BEACON_CONFIG_H
#define BEACON_CONFIG_H

// ── Edit before flashing each beacon ──

#define BEACON_ROOM_NUMBER     204

#define BEACON_UUID            {0x6E,0x40,0x00,0x01, \
                                0xB5,0xA3,0xF3,0x93, \
                                0xE0,0xA9,0xE5,0x0E, \
                                0x24,0xDC,0xCA,0x9E}

// TX power: 0 dBm = good for 20-30m classroom coverage
#define BEACON_TX_POWER        ESP_PWR_LVL_N0

// Calibrated RSSI at 1m, used in the iBeacon payload's measured-power byte
#define BEACON_MEASURED_POWER_1M   (-59)

// ──────────────────────────────────────
#endif
```

### sdkconfig.defaults
```
CONFIG_BT_ENABLED=y
CONFIG_BT_BLE_ENABLED=y
CONFIG_BT_BLUEDROID_ENABLED=y
CONFIG_BT_BLE_50_FEATURES_SUPPORTED=y
CONFIG_BT_BLE_42_FEATURES_SUPPORTED=y
CONFIG_ESP_TASK_WDT_EN=y
CONFIG_ESP_TASK_WDT_TIMEOUT_S=30
CONFIG_LOG_DEFAULT_LEVEL_INFO=y
```

### idf_component.yml
```yaml
dependencies:
  espressif/led_strip: "^2.5.0"
  idf: ">=5.0.0"
```

### Project structure
```
esp32s3-attendance-beacon/
├── CMakeLists.txt
├── sdkconfig.defaults
├── idf_component.yml
├── main/
│   ├── CMakeLists.txt
│   ├── main.c               ← app_main, boot sequence
│   ├── beacon_config.h      ← developer edits this per room
│   ├── ble_advertiser.c/.h  ← BLE init + advertisement
│   ├── led_controller.c/.h  ← RGB LED states
│   └── nvs_config.c/.h      ← NVS read/write
├── README.md
└── DEPLOY_CHECKLIST.md
```

### Boot sequence
```
1.  Print boot banner + reset reason
2.  Init NVS
3.  Load config from NVS (write defaults from beacon_config.h if first boot)
4.  Print config to serial
5.  Start LED task → WHITE (booting)
6.  Init watchdog (30s)
7.  Init BLE stack (Bluedroid)
8.  Start BLE advertisement (iBeacon, 1M PHY, 0 dBm, 150ms interval)
9.  Set LED → GREEN PULSE
10. Main loop: feed watchdog every 5s
```

### Serial log format
```
[BOOT]   Attendance Beacon v1.0
[BOOT]   Reboot reason: 0
[CONFIG] Room: 204
[CONFIG] TX Power: 0 dBm
[BLE]    Broadcasting started
```

### Build & flash commands (Windows example)
```bash
# Activate IDF (once per terminal)
%IDF_PATH%\export.bat

# Build
cd esp32s3-attendance-beacon
idf.py set-target esp32s3
idf.py build

# Erase old NVS then flash
idf.py -p COM3 erase-flash
idf.py -p COM3 flash monitor

# Exit monitor
Ctrl + ]
```

---

## 6. Full AI Agent Prompt (HISTORICAL — already executed, kept for reference only)

> ⚠️ This prompt was already run and the firmware it describes has since been
> **superseded**: the rotating HMAC token it specifies was removed (no WiFi + no
> RTC meant the beacon and server clocks could never agree — see Decision Log
> in Section 4). The actual, current firmware is in `esp32s3-attendance-beacon/`
> and does NOT match every detail below. Don't re-paste this prompt as-is.

> Copy everything between the triple-dashes below and paste it directly
> into Claude Code or OpenCode as your first message.

---

# AI Agent Prompt — ESP32-S3 BLE Attendance Beacon Firmware

## Your Role
You are an expert embedded systems engineer. Implement complete, simple,
production-ready firmware for an ESP32-S3 BLE attendance beacon using
Espressif IDF framework. Keep code clean, minimal, and well-commented.

## What This Beacon Does
- Sits in a university classroom, powered by USB mobile charger
- Continuously broadcasts a BLE advertisement packet
- Packet contains: room ID + a rolling token that changes every 30 seconds
- Students' phones scan and receive this packet via attendance app
- App sends data to backend server → server verifies → marks attendance
- Beacon is fully OFFLINE — no WiFi, no internet, ever
- Beacon only broadcasts — never connects, never pairs with phones

## Hardware
- Board: ESP32-S3 DevKit
- Power: USB cable → mobile charger (no battery)
- RGB LED: WS2812B onboard, GPIO48, single pixel

## Features to Implement

### 1. BLE Advertisement
- Format: iBeacon
- PHY: 1M PHY (works with all phones)
- TX power: 0 dBm → covers 20–30m, stays within classroom
- Advertisement interval: 150ms
- Non-connectable broadcast only (ADV_TYPE_NONCONN_IND)
- Payload:
  - Major → room number (uint16) e.g. 204
  - Minor → rolling HMAC token (uint16) changes every 30s
  - UUID  → fixed 16-byte room UUID set in config header

### 2. Rolling HMAC Token
- Every 30 seconds, compute new token:
  - window = seconds_since_boot / 30 (uint32)
  - token  = HMAC-SHA256(secret_key, window)
  - minor  = first 2 bytes of token (uint16)
- Use ESP32-S3 hardware HMAC peripheral (esp_hmac.h)
- Update BLE advertisement Minor value with new token
- Use esp_timer periodic callback every 30 seconds
- Secret key loaded from NVS

### 3. NVS Config Storage
Store in NVS namespace "beacon_cfg":
- "room_number" → uint16
- "room_uuid"   → blob 16 bytes
- "room_secret" → blob 32 bytes
On first boot: write defaults from beacon_config.h into NVS
On every boot: read from NVS
Never log secret key to serial

### 4. RGB LED States
Drive WS2812B on GPIO48 using espressif/led_strip component.
LED task runs independently via FreeRTOS — never blocks BLE.
- Booting        → White,  solid 2s
- Broadcasting   → Green,  slow pulse 2s cycle
- Token rotating → Blue,   one quick flash
- NVS error      → Yellow, fast blink
- Fatal error    → Red,    solid on

### 5. Watchdog Timer
- Timeout: 30 seconds
- Feed in main loop every 5 seconds
- Auto-reboots if firmware hangs

### 6. Serial Logging
Baud 115200. Tags: [BOOT] [CONFIG] [BLE] [TOKEN] [ERROR]
Never log secret key.

## Project Structure
```
esp32s3-attendance-beacon/
├── CMakeLists.txt
├── sdkconfig.defaults
├── idf_component.yml
├── main/
│   ├── CMakeLists.txt
│   ├── main.c
│   ├── beacon_config.h
│   ├── ble_advertiser.c/.h
│   ├── hmac_token.c/.h
│   ├── led_controller.c/.h
│   └── nvs_config.c/.h
└── README.md
```

## beacon_config.h
```c
#ifndef BEACON_CONFIG_H
#define BEACON_CONFIG_H
#define BEACON_ROOM_NUMBER     204
#define BEACON_UUID            {0x6E,0x40,0x00,0x01, \
                                0xB5,0xA3,0xF3,0x93, \
                                0xE0,0xA9,0xE5,0x0E, \
                                0x24,0xDC,0xCA,0x9E}
#define BEACON_SECRET_KEY      "ROOM204_DEFAULT_SECRET_KEY_32B!!"
#define BEACON_TX_POWER        ESP_PWR_LVL_N0
#endif
```

## sdkconfig.defaults
```
CONFIG_BT_ENABLED=y
CONFIG_BT_BLE_ENABLED=y
CONFIG_BT_BLUEDROID_ENABLED=y
CONFIG_BT_BLE_50_FEATURES_SUPPORTED=y
CONFIG_BT_BLE_42_FEATURES_SUPPORTED=y
CONFIG_ESP_TASK_WDT_EN=y
CONFIG_ESP_TASK_WDT_TIMEOUT_S=30
CONFIG_LOG_DEFAULT_LEVEL_INFO=y
```

## idf_component.yml
```yaml
dependencies:
  espressif/led_strip: "^2.5.0"
  idf: ">=5.0.0"
```

## Boot Sequence
1.  Print boot banner + reset reason
2.  Init NVS
3.  Load config from NVS (write defaults if first boot)
4.  Print config to serial
5.  Start LED task → WHITE (booting)
6.  Init watchdog
7.  Init BLE stack
8.  Compute first HMAC token
9.  Start BLE advertisement
10. Start 30s timer → token rotation
11. Set LED → GREEN PULSE
12. Main loop: feed watchdog every 5s

## Build and Flash Commands (include in README.md)
```bash
# Activate IDF
. $IDF_PATH/export.sh             # Linux/macOS
%IDF_PATH%\export.bat             # Windows

# Build
cd esp32s3-attendance-beacon
idf.py set-target esp32s3
idf.py build

# Erase + flash + monitor
idf.py -p COM3 erase-flash        # Windows (change port as needed)
idf.py -p COM3 flash monitor
# Exit monitor: Ctrl + ]

# Change room config workflow:
# 1. Edit beacon_config.h
# 2. idf.py build
# 3. idf.py -p COM3 erase-flash
# 4. idf.py -p COM3 flash monitor
```

## Rules
- ESP-IDF v5.x APIs only. No Arduino. No Arduino libraries.
- FreeRTOS for all tasks and timers.
- No blocking delays outside LED task.
- No WiFi code anywhere.
- HMAC must use hardware peripheral only.
- Never log secret key.
- Check every ESP-IDF API return value.
- Compile with zero warnings.
- Comments explain WHY not WHAT.
- Keep it simple — no over-engineering.

## Deliverables
1. Complete compilable ESP-IDF project (all files above)
2. README.md with build/flash commands + room config change guide
3. DEPLOY_CHECKLIST.md — steps for flashing multiple classrooms

---

## 7. Next Immediate Action Items

- [x] **Firmware:** built in `esp32s3-attendance-beacon/`, no token, fixed iBeacon
- [x] **Backend:** `AttendanceService::mark()` updated (GPS + RSSI + Major check)
- [ ] **Test beacon:** Flash to your ESP32-S3 devkit → verify green pulse LED
      and BLE advertisement using nRF Connect app on your phone
- [ ] **Test range:** Walk away from beacon with nRF Connect → confirm signal
      drops below −75 dBm at ~25–30m distance
- [ ] **Run migrations:** start MySQL, `php artisan migrate`, `php artisan db:seed` (optional)
- [ ] **Set campus geofence:** fill in `ATTENDANCE_CAMPUS_LAT`/`ATTENDANCE_CAMPUS_LNG`
      in `.env` — geofence check is silently skipped until these are set
- [ ] **Register rooms:** for each physical beacon, create/update its Room via the
      admin API with `beacon_major` = that beacon's `BEACON_ROOM_NUMBER`
- [ ] **Build the Flutter app:** GPS check → BLE scan (UUID/Major + RSSI, no
      pairing) → `POST /api/attendance/mark` with
      `{session_id, detected_major, rssi, latitude, longitude}`
- [ ] **Future hardware addition:** When ready, add 18650 battery + TP4056
      charging module for wireless classroom deployment (components list
      already decided in prior session)
EOF
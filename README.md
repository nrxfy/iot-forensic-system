# iot-forensic-system
IoT Device Behavior Logging and ForensicvSystem - FYP

# IoT Device Behavior Logging and Forensic System

## About
Centralized IoT security event logging with SHA-256 integrity verification and forensic timeline reconstruction for SME environments.

## Features
- RFID + Keypad authentication with role/time-based access rules
- SHA-256 log integrity verification
- Incident timeline reconstruction
- Forensic report generation
- Node-RED / ESP32 / Wokwi integration

## Setup
1. Install XAMPP
2. Import `database.sql` into MySQL
3. Copy `config/db.example.php` to `config/db.php` and add your credentials
4. Access `http://localhost/IOT_FORENSICS/login.php`

## Default Login
| User | Password | Role |
|------|----------|------|
| admin | 123456 | Admin |
| analyst | 123456 | Analyst |
| staff01 | 123456 | Staff |
| cleaner01 | 123456 | Cleaner |

## API Endpoint
`POST /api_log.php` — Accepts device events from IoT hardware


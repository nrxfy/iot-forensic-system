IOT_FORENSIC enhanced version

Database:
1. Open phpMyAdmin.
2. Drop old iot_forensic1 database if you want a clean reset.
3. Import database.sql.

Run:
http://localhost/IOT_FORENSIC/login.php

Default accounts:
admin / 123456
analyst / 123456
staff01 / 123456
cleaner01 / 123456

New improvements:
- Demo OTP after login. The OTP is shown on screen for local XAMPP demo.
- Login and logout events are stored in Forensic Logs and Timeline.
- Admin user management: edit name, username, role, RFID/ID, PIN, active/disabled status.
- Admin door management: edit door details, lock/unlock/offline status.
- Admin access rules: add, edit, enable/disable, delete door access rules.
- Dashboard shows updated door status and active rules.
- API improved: tamper/restart/battery events do not require RFID/PIN.
- ACCESS_ATTEMPT still requires valid RFID + PIN and checks role/time access rule.

Important:
Folder name must be IOT_FORENSIC inside htdocs.
Node-RED/PHP API URL:
http://localhost/IOT_FORENSIC/api_log.php


UPGRADE NOTES:
- Admin only can use Door Simulator, User Management, Door Management, and Access Rules.
- Normal users can open My Accessible Doors to see which doors they can unlock/use.
- Door unlock request creates GRANTED or DENIED forensic logs automatically.
- Forensic Report page can filter incidents and print/save as PDF from the browser.

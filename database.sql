CREATE DATABASE IF NOT EXISTS iot_forensic1;
USE iot_forensic1;

-- Clean reset to avoid old table/column mismatch errors
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS event_logs;
DROP TABLE IF EXISTS access_rules;
DROP TABLE IF EXISTS doors;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','analyst','staff','cleaner') NOT NULL DEFAULT 'staff',
  rfid_uid VARCHAR(50) UNIQUE,
  pin_code VARCHAR(20),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS doors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  door_code VARCHAR(50) NOT NULL UNIQUE,
  door_name VARCHAR(100) NOT NULL,
  location VARCHAR(100) NOT NULL,
  status ENUM('LOCKED','UNLOCKED','OFFLINE') DEFAULT 'LOCKED',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS access_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  door_id INT NOT NULL,
  role ENUM('admin','analyst','staff','cleaner') NOT NULL,
  allowed_start TIME NOT NULL,
  allowed_end TIME NOT NULL,
  is_enabled TINYINT(1) DEFAULT 1,
  FOREIGN KEY (door_id) REFERENCES doors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS event_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_time DATETIME NOT NULL,
  door_id INT NULL,
  device_id VARCHAR(50) NOT NULL,
  user_id INT NULL,
  actor_name VARCHAR(100),
  actor_role VARCHAR(50),
  event_type VARCHAR(80) NOT NULL,
  auth_method VARCHAR(40),
  result ENUM('GRANTED','DENIED','WARNING','INFO') NOT NULL,
  severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
  source_ip VARCHAR(45),
  reason TEXT,
  raw_payload TEXT,
  hash_value CHAR(64) NOT NULL,
  stored_hash CHAR(64) NOT NULL,
  integrity_status ENUM('VALID','TAMPERED','UNKNOWN') DEFAULT 'VALID',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (door_id) REFERENCES doors(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO doors (door_code, door_name, location, status) VALUES
('DOOR-01','Main Entrance','Lobby','LOCKED'),
('DOOR-02','Server Room','Level 2','LOCKED'),
('DOOR-03','Storage Room','Ground Floor','LOCKED');

-- Default password for all seeded users: 123456
INSERT IGNORE INTO users (full_name, username, password, role, rfid_uid, pin_code, is_active) VALUES
('System Administrator','admin', '$2y$12$LBgjwJT3LPj5rZ004bPSCuS6SCAkHT/x7rR7Bdw7kFeNQAnwKBh.W','admin','ADMIN001','123456',1),
('Security Analyst','analyst', '$2y$12$LBgjwJT3LPj5rZ004bPSCuS6SCAkHT/x7rR7Bdw7kFeNQAnwKBh.W','analyst','ANALYST001','123456',1),
('Staff User','staff01', '$2y$12$LBgjwJT3LPj5rZ004bPSCuS6SCAkHT/x7rR7Bdw7kFeNQAnwKBh.W','staff','STAFF001','111111',1),
('Cleaner User','cleaner01', '$2y$12$LBgjwJT3LPj5rZ004bPSCuS6SCAkHT/x7rR7Bdw7kFeNQAnwKBh.W','cleaner','CLEAN001','222222',1);

-- Clean testing-friendly access rules: one rule per role per door
-- For demo/testing, staff can access Main Entrance all day.
INSERT INTO access_rules (door_id, role, allowed_start, allowed_end, is_enabled)
SELECT id, 'admin', '00:00:00', '23:59:59', 1 FROM doors WHERE door_code IN ('DOOR-01','DOOR-02','DOOR-03');

INSERT INTO access_rules (door_id, role, allowed_start, allowed_end, is_enabled)
SELECT id, 'staff', '00:00:00', '23:59:59', 1 FROM doors WHERE door_code='DOOR-01';

INSERT INTO access_rules (door_id, role, allowed_start, allowed_end, is_enabled)
SELECT id, 'analyst', '08:00:00', '18:00:00', 1 FROM doors WHERE door_code='DOOR-02';

INSERT INTO access_rules (door_id, role, allowed_start, allowed_end, is_enabled)
SELECT id, 'cleaner', '18:00:00', '22:00:00', 1 FROM doors WHERE door_code='DOOR-03';

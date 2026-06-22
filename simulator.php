<?php
require 'includes/auth.php';
require_admin();
include 'config/db.php';
include 'includes/layout.php';
include 'includes/log_func.php';

$msg = '';

function process_simulation($conn, $door_id, $rfid, $pin, $event, $raw_source = 'manual') {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
  $door_id = (int)$door_id;
  $rfid = trim($rfid);
  $pin = trim($pin);
  $event = trim($event);

  $door = $conn->query("SELECT * FROM doors WHERE id=$door_id")->fetch_assoc();
  if (!$door) {
    return '<div class="alert alert-danger">Door not found.</div>';
  }

  $stmt = $conn->prepare('SELECT * FROM users WHERE rfid_uid=? AND pin_code=? AND is_active=1');
  $stmt->bind_param('ss', $rfid, $pin);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  $raw = json_encode([
    'source' => $raw_source,
    'door_id' => $door_id,
    'rfid_uid' => $rfid,
    'pin_code' => $pin,
    'event_type' => $event
  ]);

  if (!$user && $event === 'ACCESS_ATTEMPT') {
    create_log($conn, $door['door_code'], $door_id, [], 'AUTHENTICATION_FAILED', 'RFID+KEYPAD', 'DENIED', 'HIGH', 'Invalid RFID or PIN', $ip, $raw);
    return '<div class="alert alert-danger">Access denied: invalid RFID/PIN. Red forensic log created.</div>';
  }

  if (!$user && $event !== 'ACCESS_ATTEMPT') {
    create_log($conn, $door['door_code'], $door_id, [], $event, 'SYSTEM', 'WARNING', 'MEDIUM', 'System/device event simulated without valid user credential', $ip, $raw);
    return '<div class="alert alert-warning">Device warning event logged. Yellow forensic log created.</div>';
  }

  if ($event !== 'ACCESS_ATTEMPT') {
    $severity = ($event === 'TAMPER_DETECTED') ? 'CRITICAL' : 'MEDIUM';
    $reason = 'Device event simulated: ' . $event;
    create_log($conn, $door['door_code'], $door_id, $user, $event, 'SYSTEM', 'WARNING', $severity, $reason, $ip, $raw);
    return '<div class="alert alert-warning">'.$event.' logged successfully. Yellow forensic log created.</div>';
  }

  $now = date('H:i:s');
  $rule = $conn->prepare('SELECT * FROM access_rules WHERE door_id=? AND role=? AND is_enabled=1 AND ? BETWEEN allowed_start AND allowed_end LIMIT 1');
  $rule->bind_param('iss', $door_id, $user['role'], $now);
  $rule->execute();
  $allowed = $rule->get_result()->fetch_assoc();

  if ($allowed) {
    create_log($conn, $door['door_code'], $door_id, $user, 'ACCESS_GRANTED', 'RFID+KEYPAD', 'GRANTED', 'LOW', 'User allowed by role and time rule', $ip, $raw);
    $conn->query("UPDATE doors SET status='UNLOCKED' WHERE id=$door_id");
    return '<div class="alert alert-success">Access granted. Green forensic log created.</div>';
  }

  create_log($conn, $door['door_code'], $door_id, $user, 'ACCESS_DENIED', 'RFID+KEYPAD', 'DENIED', 'HIGH', 'Role or time restriction violated', $ip, $raw);
  return '<div class="alert alert-danger">Access denied due to door rule/time restriction. Red forensic log created.</div>';
}

function door_id_by_code($conn, $code) {
  $stmt = $conn->prepare('SELECT id FROM doors WHERE door_code=? LIMIT 1');
  $stmt->bind_param('s', $code);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  return $row ? (int)$row['id'] : 1;
}

if (isset($_POST['auto_scenario'])) {
  $scenario = $_POST['auto_scenario'];

  if ($scenario === 'success') {
    // Admin has 24-hour rule, so this scenario always gives green success.
    $msg = process_simulation($conn, door_id_by_code($conn, 'DOOR-02'), 'ADMIN001', '123456', 'ACCESS_ATTEMPT', 'auto_success_access');
  } elseif ($scenario === 'failed') {
    $msg = process_simulation($conn, door_id_by_code($conn, 'DOOR-01'), 'HACKER999', '999999', 'ACCESS_ATTEMPT', 'auto_failed_access');
  } elseif ($scenario === 'tamper') {
    $msg = process_simulation($conn, door_id_by_code($conn, 'DOOR-02'), 'ADMIN001', '123456', 'TAMPER_DETECTED', 'auto_tamper');
  } elseif ($scenario === 'restart') {
    $msg = process_simulation($conn, door_id_by_code($conn, 'DOOR-01'), 'ADMIN001', '123456', 'DEVICE_RESTART', 'auto_restart');
  } elseif ($scenario === 'battery') {
    $msg = process_simulation($conn, door_id_by_code($conn, 'DOOR-03'), 'ADMIN001', '123456', 'BATTERY_LOW', 'auto_battery');
  }
}

if (isset($_POST['simulate'])) {
  $msg = process_simulation($conn, $_POST['door_id'], $_POST['rfid_uid'], $_POST['pin_code'], $_POST['event_type'], 'manual_form');
}

$doors = $conn->query('SELECT * FROM doors');
header_html('Simulator');
?>
<h2>Smart Door Simulator</h2>
<p class="small-muted">Use this to simulate Wokwi/ESP32, RFID sensor, keypad, forced restart, tamper, and battery events.</p>
<?= $msg ?>

<div class="card p-4 mb-3">
  <h5 class="mb-3">One-click Auto Simulation</h5>
  <p class="small-muted">Use these buttons for fast demo. After clicking, check <b>Forensic Logs</b> and <b>Timeline</b>.</p>
  <div class="row g-2">
    <div class="col-md-2">
      <form method="post"><button class="btn btn-success w-100" name="auto_scenario" value="success">RFID Success</button></form>
    </div>
    <div class="col-md-2">
      <form method="post"><button class="btn btn-danger w-100" name="auto_scenario" value="failed">Failed PIN/RFID</button></form>
    </div>
    <div class="col-md-2">
      <form method="post"><button class="btn btn-warning w-100" name="auto_scenario" value="tamper">Tamper Alert</button></form>
    </div>
    <div class="col-md-2">
      <form method="post"><button class="btn btn-warning w-100" name="auto_scenario" value="restart">Device Restart</button></form>
    </div>
    <div class="col-md-2">
      <form method="post"><button class="btn btn-warning w-100" name="auto_scenario" value="battery">Low Battery</button></form>
    </div>
  </div>
</div>

<div class="card p-4">
  <h5 class="mb-3">Manual Simulation</h5>
  <form method="post" class="row g-3">
    <div class="col-md-4">
      <label>Door Device</label>
      <select class="form-select" name="door_id">
        <?php while($d=$doors->fetch_assoc()): ?>
          <option value="<?=$d['id']?>"><?=h($d['door_code'].' - '.$d['door_name'])?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label>RFID UID</label>
      <input class="form-control" name="rfid_uid" value="STAFF001">
    </div>
    <div class="col-md-4">
      <label>Keypad PIN</label>
      <input class="form-control" name="pin_code" value="111111">
    </div>
    <div class="col-md-4">
      <label>Event Type</label>
      <select class="form-select" name="event_type">
        <option>ACCESS_ATTEMPT</option>
        <option>DEVICE_RESTART</option>
        <option>TAMPER_DETECTED</option>
        <option>BATTERY_LOW</option>
        <option>UNKNOWN_IP_TRAFFIC</option>
      </select>
    </div>
    <div class="col-md-12">
      <button class="btn btn-primary" name="simulate">Send Event to Centralized Log</button>
    </div>
  </form>
</div>

<div class="alert alert-info mt-3">Node-RED/Wokwi can send the same fields to <b>api_log.php</b> using HTTP POST.</div>
<?php footer_html(); ?>

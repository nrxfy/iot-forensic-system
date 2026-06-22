<?php
require 'includes/auth.php';
require_login();
include 'config/db.php';
include 'includes/layout.php';
include 'includes/log_func.php';
include 'includes/rule_helper.php';

$msg = '';
$resultBox = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $door_id = (int)($_POST['door_id'] ?? 0);
  $rfid_uid = trim($_POST['rfid_uid'] ?? '');
  $pin_code = trim($_POST['pin_code'] ?? '');

  $doorStmt = $conn->prepare('SELECT * FROM doors WHERE id=?');
  $doorStmt->bind_param('i', $door_id);
  $doorStmt->execute();
  $door = $doorStmt->get_result()->fetch_assoc();

  if(!$door){
    $msg = '<div class="alert alert-danger">Door not found.</div>';
  } else {
    $raw = json_encode([
      'source' => 'usb_keypad_access_page',
      'door_id' => $door_id,
      'rfid_uid' => $rfid_uid,
      'pin_entered' => $pin_code ? 'YES' : 'NO'
    ]);

    $userStmt = $conn->prepare('SELECT * FROM users WHERE rfid_uid=? AND pin_code=? AND is_active=1 LIMIT 1');
    $userStmt->bind_param('ss', $rfid_uid, $pin_code);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();

    if(!$user){
      create_log(
        $conn,
        $door['door_code'],
        $door_id,
        [],
        'AUTHENTICATION_FAILED',
        'USB_KEYPAD',
        'DENIED',
        'HIGH',
        'Invalid RFID/ID or PIN entered using USB keypad',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $raw
      );

      $conn->query("UPDATE doors SET status='LOCKED' WHERE id=".$door_id);
      $resultBox = '<div class="alert alert-danger"><h4>ACCESS DENIED</h4><p>Invalid RFID/ID or PIN. Forensic log created.</p></div>';
    } else {
      $allowed = is_allowed_by_rule($conn, $door_id, $user['role']);

      if($door['status'] === 'OFFLINE'){
        create_log(
          $conn,
          $door['door_code'],
          $door_id,
          $user,
          'ACCESS_DENIED',
          'USB_KEYPAD',
          'DENIED',
          'HIGH',
          'Door is offline. Access attempt blocked.',
          $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
          $raw
        );
        $resultBox = '<div class="alert alert-danger"><h4>ACCESS DENIED</h4><p>Door is OFFLINE. Attempt logged.</p></div>';
      } elseif($allowed){
        create_log(
          $conn,
          $door['door_code'],
          $door_id,
          $user,
          'ACCESS_GRANTED',
          'USB_KEYPAD',
          'GRANTED',
          'LOW',
          'User authenticated using RFID/ID and USB keypad PIN. Role/time rule allowed.',
          $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
          $raw
        );

        $conn->query("UPDATE doors SET status='UNLOCKED' WHERE id=".$door_id);
        $resultBox = '<div class="alert alert-success"><h4>ACCESS GRANTED</h4><p>'.h($user['full_name']).' is allowed to access '.h($door['door_name']).'.</p></div>';
      } else {
        create_log(
          $conn,
          $door['door_code'],
          $door_id,
          $user,
          'ACCESS_DENIED',
          'USB_KEYPAD',
          'DENIED',
          'HIGH',
          'RFID/ID and PIN valid, but role/time rule blocked access.',
          $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
          $raw
        );

        $conn->query("UPDATE doors SET status='LOCKED' WHERE id=".$door_id);
        $resultBox = '<div class="alert alert-warning"><h4>NOT ALLOWED NOW</h4><p>User is valid, but current time or role rule does not allow this door.</p></div>';
      }
    }
  }
}

$doors = $conn->query('SELECT * FROM doors ORDER BY id');
$users = $conn->query('SELECT full_name, role, rfid_uid, pin_code FROM users WHERE is_active=1 ORDER BY role, full_name');

header_html('USB Keypad Access');
?>

<h2>USB Keypad Access Control</h2>
<p class="small-muted">
Use this page when your PIN keypad is a USB numeric keypad connected to the laptop.
The USB keypad acts like a normal keyboard: click the PIN field and type the PIN.
</p>

<?=$msg?>
<?=$resultBox?>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card p-3">
      <h5>Authenticate Door Access</h5>
      <form method="post">
        <label class="form-label mt-2">Door</label>
        <select name="door_id" class="form-select" required>
          <?php while($d=$doors->fetch_assoc()): ?>
            <option value="<?=$d['id']?>"><?=h($d['door_code'].' - '.$d['door_name'].' ('.$d['status'].')')?></option>
          <?php endwhile; ?>
        </select>

        <label class="form-label mt-3">RFID / User ID</label>
        <input class="form-control" name="rfid_uid" placeholder="Example: STAFF001" required autofocus>

        <label class="form-label mt-3">PIN Code</label>
        <input class="form-control" type="password" name="pin_code" placeholder="Use USB keypad here" required>

        <button class="btn btn-primary w-100 mt-3">Authenticate Access</button>
      </form>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card p-3">
      <h5>Test Accounts</h5>
      <p class="small-muted">Use these to test with your USB keypad.</p>
      <table class="table table-sm">
        <tr><th>User</th><th>Role</th><th>RFID/ID</th><th>PIN</th></tr>
        <?php while($u=$users->fetch_assoc()): ?>
          <tr>
            <td><?=h($u['full_name'])?></td>
            <td><span class="badge bg-secondary"><?=h($u['role'])?></span></td>
            <td><?=h($u['rfid_uid'])?></td>
            <td><?=h($u['pin_code'])?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>

    <div class="alert alert-info mt-3">
      <b>Demo explanation:</b> RFID/User ID represents the scanned identity. The USB keypad represents PIN input.
      Every attempt is stored as a forensic log with timestamp, door, actor, result, severity, and hash.
    </div>
  </div>
</div>

<?php footer_html(); ?>

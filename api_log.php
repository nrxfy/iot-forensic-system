<?php
include 'config/db.php'; include 'includes/log_func.php'; include 'includes/rule_helper.php';
header('Content-Type: application/json');
$door_code=$_POST['device_id'] ?? $_POST['door_code'] ?? 'DOOR-01';
$rfid=$_POST['rfid_uid'] ?? ''; $pin=$_POST['pin_code'] ?? ''; $event=$_POST['event_type'] ?? 'ACCESS_ATTEMPT';
$doorStmt=$conn->prepare('SELECT * FROM doors WHERE door_code=?'); $doorStmt->bind_param('s',$door_code); $doorStmt->execute(); $door=$doorStmt->get_result()->fetch_assoc();
if(!$door){ http_response_code(404); echo json_encode(['status'=>'error','message'=>'Door not found']); exit; }
$ip=$_SERVER['REMOTE_ADDR'] ?? 'node-red'; $raw=json_encode($_POST);
$deviceEvents=['TAMPER_DETECTED','DEVICE_RESTART','BATTERY_LOW','UNKNOWN_IP_TRAFFIC'];
if(in_array($event,$deviceEvents)){
  $severity = ($event==='TAMPER_DETECTED' || $event==='UNKNOWN_IP_TRAFFIC') ? 'CRITICAL' : 'MEDIUM';
  $reason = 'Device event received from ESP32/Node-RED: '.$event;
  create_log($conn,$door_code,$door['id'],[],$event,'DEVICE','WARNING',$severity,$reason,$ip,$raw);
  if($event==='TAMPER_DETECTED' || $event==='DEVICE_RESTART'){
    $id=(int)$door['id']; $conn->query("UPDATE doors SET status='LOCKED' WHERE id=$id");
  }
  echo json_encode(['status'=>'ok','message'=>'device event stored']); exit;
}
$userStmt=$conn->prepare('SELECT * FROM users WHERE rfid_uid=? AND pin_code=? AND is_active=1');
$userStmt->bind_param('ss',$rfid,$pin); $userStmt->execute(); $user=$userStmt->get_result()->fetch_assoc();
if(!$user){ create_log($conn,$door_code,$door['id'],[],'AUTHENTICATION_FAILED','RFID+KEYPAD','DENIED','HIGH','Invalid RFID or PIN from IoT device',$ip,$raw); echo json_encode(['status'=>'denied','message'=>'invalid RFID or PIN']); exit; }
$allowed = is_allowed_by_rule($conn, (int)$door['id'], $user['role']);
if($allowed){
  create_log($conn,$door_code,$door['id'],$user,'ACCESS_GRANTED','RFID+KEYPAD','GRANTED','LOW','User allowed by role and time access rule',$ip,$raw);
  $id=(int)$door['id']; $conn->query("UPDATE doors SET status='UNLOCKED' WHERE id=$id");
  echo json_encode(['status'=>'ok','message'=>'access granted']); exit;
}
create_log($conn,$door_code,$door['id'],$user,'ACCESS_DENIED','RFID+KEYPAD','DENIED','HIGH','Role or time restriction violated',$ip,$raw);
$id=(int)$door['id']; $conn->query("UPDATE doors SET status='LOCKED' WHERE id=$id");
echo json_encode(['status'=>'denied','message'=>'rule or time restriction violated']);
?>

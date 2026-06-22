<?php
require 'includes/auth.php'; require_login(); include 'config/db.php'; include 'includes/layout.php'; include 'includes/log_func.php'; include 'includes/status_helper.php'; include 'includes/rule_helper.php';
$msg='';
$user_id=(int)$_SESSION['user_id']; $role=$_SESSION['role'];
$userStmt=$conn->prepare('SELECT * FROM users WHERE id=?'); $userStmt->bind_param('i',$user_id); $userStmt->execute(); $user=$userStmt->get_result()->fetch_assoc();
function is_allowed_now($conn,$door_id,$role){ return is_allowed_by_rule($conn,$door_id,$role); }
if(isset($_POST['request_access'])){
  $door_id=(int)$_POST['door_id'];
  $door=$conn->query("SELECT * FROM doors WHERE id=$door_id")->fetch_assoc();
  if($door){
    $raw=json_encode(['source'=>'user_dashboard','user_id'=>$user_id,'door_id'=>$door_id,'action'=>'request_unlock']);
    if($door['status']==='OFFLINE'){
      create_log($conn,$door['door_code'],$door_id,$user,'ACCESS_DENIED','WEB','DENIED','HIGH','Door is offline, user cannot unlock',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',$raw);
      $msg='<div class="alert alert-danger">Door is offline. Access denied and logged.</div>';
    } elseif(is_allowed_now($conn,$door_id,$role)){
      create_log($conn,$door['door_code'],$door_id,$user,'ACCESS_GRANTED','WEB','GRANTED','LOW','User unlocked allowed door from dashboard',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',$raw);
      $conn->query("UPDATE doors SET status='UNLOCKED' WHERE id=$door_id");
      $msg='<div class="alert alert-success">Access granted. Door changed to UNLOCKED and forensic log created.</div>';
    } else {
      create_log($conn,$door['door_code'],$door_id,$user,'ACCESS_DENIED','WEB','DENIED','HIGH','User role or time rule does not allow this door',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',$raw);
      $msg='<div class="alert alert-danger">Access denied by role/time rule. Forensic log created.</div>';
    }
  }
}
$doors=$conn->query('SELECT d.*, GROUP_CONCAT(CONCAT(r.role," (",TIME_FORMAT(r.allowed_start,"%H:%i"),"-",TIME_FORMAT(r.allowed_end,"%H:%i"),")") SEPARATOR ", ") AS rules FROM doors d LEFT JOIN access_rules r ON r.door_id=d.id AND r.is_enabled=1 GROUP BY d.id ORDER BY d.id');
$mylogs=$conn->prepare('SELECT l.*, d.door_name FROM event_logs l LEFT JOIN doors d ON d.id=l.door_id WHERE l.user_id=? OR l.actor_name=? ORDER BY l.event_time DESC LIMIT 8');
$fullname=$_SESSION['full_name'] ?? $_SESSION['username']; $mylogs->bind_param('is',$user_id,$fullname); $mylogs->execute(); $logres=$mylogs->get_result();
header_html('My Accessible Doors'); ?>
<h2>My Accessible Doors</h2>
<p class="small-muted">Normal users cannot use the Door Simulator. This page shows which doors they can use based on role and time rules.</p>
<?=$msg?>
<div class="row g-3 mb-4">
<?php while($d=$doors->fetch_assoc()): $allowed=is_allowed_now($conn,(int)$d['id'],$role); ?>
  <div class="col-md-4"><div class="card p-3 <?= $allowed?'allowed-card':'restricted-card' ?>">
    <div class="d-flex justify-content-between align-items-center"><h5><?=h($d['door_code'].' - '.$d['door_name'])?></h5><span class="door-state state-<?=h($d['status'])?>"><?=h($d['status'])?></span></div>
    <div class="small-muted mb-2"><?=h($d['location'])?></div>
    <p><b>Your access:</b> <?= $allowed?'<span class="badge bg-success">Allowed now</span>':'<span class="badge bg-danger">Not allowed now</span>' ?></p>
    <p class="small"><b>Rules:</b> <?=h($d['rules'] ?: 'No active rule')?></p>
    <form method="post" class="no-print"><input type="hidden" name="door_id" value="<?=$d['id']?>"><button class="btn <?= $allowed?'btn-success':'btn-outline-danger' ?> w-100" name="request_access">Request Unlock / Use Door</button></form>
  </div></div>
<?php endwhile; ?>
</div>
<div class="card p-3"><h5>My Latest Activity</h5><table class="table table-sm"><tr><th>Time</th><th>Door</th><th>Event</th><th>Status</th><th>Reason</th></tr><?php while($r=$logres->fetch_assoc()): ?><tr class="<?=row_class($r['result'],$r['event_type'],$r['integrity_status'])?>"><td><?=h($r['event_time'])?></td><td><?=h($r['door_name'] ?: $r['device_id'])?></td><td><?=h($r['event_type'])?></td><td><?=status_badge($r['result'],$r['event_type'],$r['integrity_status'])?></td><td><?=h($r['reason'])?></td></tr><?php endwhile; ?></table></div>
<?php footer_html(); ?>

<?php require 'includes/auth.php'; require_login(); include 'config/db.php'; include 'includes/layout.php'; include 'includes/status_helper.php';
$stats=[]; foreach(['GRANTED','DENIED','WARNING','INFO'] as $r){$q=$conn->query("SELECT COUNT(*) c FROM event_logs WHERE result='$r'")->fetch_assoc();$stats[$r]=$q['c'];}
$doors=$conn->query('SELECT d.*, (SELECT COUNT(*) FROM access_rules r WHERE r.door_id=d.id AND r.is_enabled=1) rule_count FROM doors d ORDER BY d.id');
$latest=$conn->query('SELECT l.*, d.door_name, d.status door_status FROM event_logs l LEFT JOIN doors d ON d.id=l.door_id ORDER BY l.event_time DESC LIMIT 10');
$activeUsers=$conn->query('SELECT COUNT(*) c FROM users WHERE is_active=1')->fetch_assoc()['c'];
$role=$_SESSION['role'] ?? '';
header_html('Dashboard'); ?>
<h2>Forensic Access Control Dashboard</h2>
<p class="small-muted">Friendly overview for multi-door IoT smart lock logging, door status, integrity, user login/logout, and incident reconstruction.</p>
<div class="row g-3 mb-4">
<?php foreach($stats as $k=>$v): ?><div class="col-md-2"><div class="card p-3"><div class="small-muted"><?=h($k)?></div><h3><?=$v?></h3></div></div><?php endforeach; ?>
<div class="col-md-2"><div class="card p-3"><div class="small-muted">ACTIVE USERS</div><h3><?=$activeUsers?></h3></div></div>
</div>
<?php if($role!=='admin'): ?>
<div class="alert alert-primary"><b>User mode:</b> You can view and request access only for doors allowed by your role/time rules. Door simulator and admin controls are hidden for security.</div>
<?php else: ?>
<div class="alert alert-info"><b>Admin mode:</b> Admin can edit users, roles, ID/RFID/PIN, active status, door lock state, and access rules. Login/logout events are logged for forensics.</div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <div class="col-md-5"><div class="card p-3"><h5>Door Status & Rules</h5><table class="table table-sm"><tr><th>Door</th><th>Location</th><th>Status</th><th>Rules</th></tr><?php while($d=$doors->fetch_assoc()): ?><tr><td><?=h($d['door_code'].' - '.$d['door_name'])?></td><td><?=h($d['location'])?></td><td><span class="status-badge <?= $d['status']=='UNLOCKED'?'status-success':($d['status']=='OFFLINE'?'status-warning':'status-info')?>"><?=h($d['status'])?></span></td><td><?=h($d['rule_count'])?> enabled</td></tr><?php endwhile; ?></table><a class="btn btn-sm btn-outline-primary" href="my_doors.php">View my accessible doors</a></div></div>
  <div class="col-md-7"><div class="card p-3"><h5>Latest Forensic & User Activity Logs</h5><table class="table table-sm"><tr><th>Time</th><th>Door/System</th><th>Who</th><th>What happened</th><th>Status</th></tr><?php while($r=$latest->fetch_assoc()): ?><tr class="<?=row_class($r['result'],$r['event_type'],$r['integrity_status'])?>"><td><?=h($r['event_time'])?></td><td><?=h($r['door_name'] ?? $r['device_id'])?></td><td><?=h($r['actor_name'])?> <small class="text-muted">(<?=h($r['actor_role'])?>)</small></td><td><?=h($r['event_type'])?> - <?=h($r['reason'])?></td><td><?=status_badge($r['result'],$r['event_type'],$r['integrity_status'])?></td></tr><?php endwhile; ?></table><a class="btn btn-sm btn-outline-dark" href="report.php">Generate / Print Report</a></div></div>
</div>
<?php footer_html(); ?>

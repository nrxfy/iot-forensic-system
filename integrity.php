<?php require 'includes/auth.php'; require_login(); include 'config/db.php'; include 'includes/layout.php'; include 'includes/log_func.php'; include 'includes/status_helper.php';
$rows=$conn->query('SELECT * FROM event_logs ORDER BY event_time DESC LIMIT 200'); $checked=[];
while($r=$rows->fetch_assoc()){ $new=recompute_log_hash($r); $r['check_status']=($new===$r['stored_hash'])?'VALID':'TAMPERED'; $checked[]=$r; }
header_html('Integrity'); ?>
<h2>Log Integrity Verification</h2><p class="small-muted">Recalculates SHA-256 hash to detect if forensic logs were modified. Tampered records are highlighted yellow.</p><div class="card p-3"><table class="table table-sm"><tr><th>ID</th><th>Event</th><th>Stored Hash</th><th>Status</th></tr><?php foreach($checked as $r): ?><tr class="<?=row_class($r['check_status'],$r['event_type'],$r['check_status'])?>"><td><?=$r['id']?></td><td><?=h($r['event_time'].' '.$r['event_type'])?></td><td class="hash"><?=h($r['stored_hash'])?></td><td><?=status_badge($r['check_status'],$r['event_type'],$r['check_status'])?></td></tr><?php endforeach; ?></table></div>
<?php footer_html(); ?>

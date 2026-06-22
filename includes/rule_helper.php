<?php
function is_allowed_by_rule($conn, $door_id, $role){
  if($role === 'admin') return true;
  $now = date('H:i:s');

  $stmt = $conn->prepare('SELECT * FROM access_rules WHERE door_id=? AND role=? AND is_enabled=1');
  $stmt->bind_param('is', $door_id, $role);
  $stmt->execute();
  $res = $stmt->get_result();

  while($r = $res->fetch_assoc()){
    $start = $r['allowed_start'];
    $end = $r['allowed_end'];

    // Normal same-day rule, e.g. 08:00:00 - 18:00:00
    if($start <= $end){
      if($now >= $start && $now <= $end) return true;
    }

    // Overnight rule, e.g. 22:00:00 - 06:00:00
    if($start > $end){
      if($now >= $start || $now <= $end) return true;
    }
  }

  return false;
}
?>

<?php
function create_log($conn,$device_id,$door_id,$user,$event_type,$auth_method,$result,$severity,$reason,$source_ip='127.0.0.1',$raw=''){
  $time=date('Y-m-d H:i:s');
  $user_id=$user['id'] ?? null; $actor=$user['full_name'] ?? 'Unknown'; $role=$user['role'] ?? 'unknown';
  $base=$time.'|'.$device_id.'|'.$door_id.'|'.$user_id.'|'.$actor.'|'.$role.'|'.$event_type.'|'.$auth_method.'|'.$result.'|'.$severity.'|'.$reason.'|'.$source_ip.'|'.$raw;
  $hash=hash('sha256',$base);
  $stmt=$conn->prepare('INSERT INTO event_logs(event_time,door_id,device_id,user_id,actor_name,actor_role,event_type,auth_method,result,severity,source_ip,reason,raw_payload,hash_value,stored_hash,integrity_status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"VALID")');
  $stmt->bind_param('sisisssssssssss',$time,$door_id,$device_id,$user_id,$actor,$role,$event_type,$auth_method,$result,$severity,$source_ip,$reason,$raw,$hash,$hash);
  return $stmt->execute();
}
function recompute_log_hash($row){
  $base=$row['event_time'].'|'.$row['device_id'].'|'.$row['door_id'].'|'.$row['user_id'].'|'.$row['actor_name'].'|'.$row['actor_role'].'|'.$row['event_type'].'|'.$row['auth_method'].'|'.$row['result'].'|'.$row['severity'].'|'.$row['reason'].'|'.$row['source_ip'].'|'.$row['raw_payload'];
  return hash('sha256',$base);
}
?>

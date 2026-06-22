<?php
function status_class($result, $event_type='', $integrity_status=''){
  $r = strtoupper((string)$result);
  $e = strtoupper((string)$event_type);
  $i = strtoupper((string)$integrity_status);
  if($r==='GRANTED' || $r==='SUCCESS' || $r==='VALID') return 'status-success';
  if($r==='DENIED' || $r==='FAILED') return 'status-failed';
  if($r==='WARNING' || $r==='TAMPERED' || $i==='TAMPERED' || strpos($e,'TAMPER')!==false) return 'status-warning';
  return 'status-info';
}
function row_class($result, $event_type='', $integrity_status=''){
  $c = status_class($result,$event_type,$integrity_status);
  if($c==='status-success') return 'row-success';
  if($c==='status-failed') return 'row-failed';
  if($c==='status-warning') return 'row-warning';
  return '';
}
function status_badge($result, $event_type='', $integrity_status=''){
  return '<span class="status-badge '.status_class($result,$event_type,$integrity_status).'">'.h($result).'</span>';
}
?>

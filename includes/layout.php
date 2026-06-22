<?php
function header_html($title='Dashboard'){
  $role = $_SESSION['role'] ?? '';
  $isAdmin = ($role === 'admin');
  echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet"></head><body><div class="container-fluid"><div class="row"><div class="col-md-2 sidebar p-3"><h5 class="mb-4">IoT Forensic</h5>';
  echo '<a href="index.php">Dashboard</a>';
  echo '<a href="my_doors.php">My Accessible Doors</a>';
  echo '<a href="access_control.php">USB Keypad Access</a>';
  echo '<a href="logs.php">Forensic Logs</a>';
  echo '<a href="timeline.php">Timeline</a>';
  echo '<a href="report.php">Generate Report / Print</a>';
  echo '<a href="integrity.php">Integrity Check</a>';
  if($isAdmin){
    echo '<a href="simulator.php">Door Simulator</a>';
    echo '<a href="users.php">Admin: Users</a>';
    echo '<a href="doors.php">Admin: Doors & Rules</a>';
    echo '<a href="standards.php">Malaysia IoT Standard</a>';
  }
  echo '<hr><div class="small">'.h($_SESSION['full_name'] ?? $_SESSION['username'] ?? '').'<br><span class="badge bg-primary">'.h($role).'</span></div><a class="mt-3 text-warning" href="logout.php">Logout</a></div><main class="col-md-10 p-4">';
}
function footer_html(){ echo '</main></div></div></body></html>'; }
?>

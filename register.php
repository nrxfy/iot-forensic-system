<?php
session_start(); include 'config/db.php';
$msg='';
if(isset($_POST['register'])){
  $full=trim($_POST['full_name']); $username=trim($_POST['username']); $pass=$_POST['password']; $role=$_POST['role'];
  $rfid=trim($_POST['rfid_uid']); $pin=trim($_POST['pin_code']);
  $check=$conn->prepare('SELECT id FROM users WHERE username=? OR rfid_uid=?'); $check->bind_param('ss',$username,$rfid); $check->execute();
  if($check->get_result()->num_rows>0){ $msg='<div class="alert alert-danger">Username or RFID already exists.</div>'; }
  else{
    $hash=password_hash($pass,PASSWORD_DEFAULT);
    $stmt=$conn->prepare('INSERT INTO users(full_name,username,password,role,rfid_uid,pin_code) VALUES(?,?,?,?,?,?)');
    $stmt->bind_param('ssssss',$full,$username,$hash,$role,$rfid,$pin);
    $msg=$stmt->execute()?'<div class="alert alert-success">Registered successfully. <a href="login.php" class="btn btn-sm btn-primary ms-2">Go to Login</a></div>':'<div class="alert alert-danger">Registration failed.</div>';
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Register</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet"></head><body class="container py-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card p-4"><h3>Register User</h3><p class="small-muted">Create admin, analyst, staff, or cleaner account.</p><?= $msg ?><form method="post"><input class="form-control mb-2" name="full_name" placeholder="Full name" required><input class="form-control mb-2" name="username" placeholder="Username" required><input class="form-control mb-2" type="password" name="password" placeholder="Password" required><select class="form-select mb-2" name="role"><option value="staff">Staff</option><option value="cleaner">Cleaner</option><option value="analyst">Security Analyst</option><option value="admin">Admin</option></select><input class="form-control mb-2" name="rfid_uid" placeholder="RFID UID e.g. STAFF002" required><input class="form-control mb-3" name="pin_code" placeholder="Keypad PIN e.g. 123456" required><button class="btn btn-success w-100" name="register">Register</button></form><a href="login.php" class="btn btn-outline-secondary w-100 mt-2">Back to Login</a></div></div></div></body></html>

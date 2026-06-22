<?php
session_start(); include 'config/db.php'; include 'includes/log_func.php';
$msg='';
if(isset($_POST['login'])){
  $username=trim($_POST['username'] ?? ''); $password=$_POST['password'] ?? '';
  $stmt=$conn->prepare('SELECT * FROM users WHERE username=? AND is_active=1');
  $stmt->bind_param('s',$username); $stmt->execute(); $user=$stmt->get_result()->fetch_assoc();
  if($user && password_verify($password,$user['password'])){
    $otp=(string)random_int(100000,999999);
    $_SESSION['pending_user']=$user;
    $_SESSION['otp']=$otp;
    $_SESSION['otp_expiry']=time()+300;
    header('Location: otp.php'); exit;
  } else {
    $msg='Invalid username/password or disabled account.';
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet"></head><body class="d-flex align-items-center" style="min-height:100vh"><div class="container"><div class="row justify-content-center"><div class="col-md-5"><div class="card p-4"><h3>IoT Device Behavior Logging</h3><p class="small-muted">Forensic Analysis System with demo OTP security</p><?php if($msg) echo '<div class="alert alert-danger">'.htmlspecialchars($msg).'</div>'; ?><form method="post"><input class="form-control mb-3" name="username" placeholder="Username" required><input class="form-control mb-3" type="password" name="password" placeholder="Password" required><button class="btn btn-primary w-100" name="login">Login</button></form><a href="register.php" class="btn btn-outline-success w-100 mt-3">Register New Account</a><p class="small-muted mt-3">Demo seeded accounts: <b>admin / 123456</b>, <b>analyst / 123456</b></p></div></div></div></div></body></html>

<?php
session_start(); include 'config/db.php'; include 'includes/log_func.php';
if(!isset($_SESSION['pending_user'])){ header('Location: login.php'); exit; }
$msg=''; $demo_otp=$_SESSION['otp'] ?? '';
if(isset($_POST['verify'])){
  $entered=trim($_POST['otp'] ?? '');
  if(time() > ($_SESSION['otp_expiry'] ?? 0)){
    $msg='OTP expired. Please login again.';
    unset($_SESSION['pending_user'],$_SESSION['otp'],$_SESSION['otp_expiry']);
  } elseif($entered === ($_SESSION['otp'] ?? '')){
    $user=$_SESSION['pending_user'];
    $_SESSION['user_id']=$user['id']; $_SESSION['username']=$user['username']; $_SESSION['role']=$user['role']; $_SESSION['full_name']=$user['full_name'];
    unset($_SESSION['pending_user'],$_SESSION['otp'],$_SESSION['otp_expiry']);
    create_log($conn,'WEB-LOGIN',null,$user,'USER_LOGIN','PASSWORD+OTP','INFO','LOW','User logged in successfully using OTP verification',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1','login_success');
    header('Location: index.php'); exit;
  } else $msg='Wrong OTP. Please try again.';
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>OTP Verification</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet"></head><body class="d-flex align-items-center" style="min-height:100vh"><div class="container"><div class="row justify-content-center"><div class="col-md-5"><div class="card p-4"><h3>OTP Verification</h3><p class="small-muted">Demo OTP is shown here because email/SMS is not configured for local XAMPP.</p><div class="alert alert-info">Demo OTP: <b><?=htmlspecialchars($demo_otp)?></b></div><?php if($msg) echo '<div class="alert alert-danger">'.htmlspecialchars($msg).'</div>'; ?><form method="post"><input class="form-control mb-3" name="otp" placeholder="Enter 6-digit OTP" required><button class="btn btn-primary w-100" name="verify">Verify OTP</button></form><a href="login.php" class="btn btn-outline-secondary w-100 mt-3">Back to Login</a></div></div></div></div></body></html>

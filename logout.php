<?php
session_start();
if(isset($_SESSION['user_id'])){
  include 'config/db.php'; include 'includes/log_func.php';
  $user=['id'=>$_SESSION['user_id'],'full_name'=>$_SESSION['full_name'] ?? $_SESSION['username'],'role'=>$_SESSION['role'] ?? 'unknown'];
  create_log($conn,'WEB-LOGOUT',null,$user,'USER_LOGOUT','SESSION','INFO','LOW','User logged out from the forensic web system',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1','logout');
}
session_destroy(); header('Location: login.php');
?>

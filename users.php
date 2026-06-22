<?php require 'includes/auth.php'; require_admin(); include 'config/db.php'; include 'includes/layout.php'; include 'includes/log_func.php';
$msg='';
if(isset($_POST['update_user'])){
  $id=(int)$_POST['id']; $full=trim($_POST['full_name']); $username=trim($_POST['username']); $role=$_POST['role']; $rfid=trim($_POST['rfid_uid']); $pin=trim($_POST['pin_code']); $active=isset($_POST['is_active'])?1:0;
  $stmt=$conn->prepare('UPDATE users SET full_name=?, username=?, role=?, rfid_uid=?, pin_code=?, is_active=? WHERE id=?');
  $stmt->bind_param('sssssii',$full,$username,$role,$rfid,$pin,$active,$id);
  if($stmt->execute()){
    $admin=['id'=>$_SESSION['user_id'],'full_name'=>$_SESSION['full_name'],'role'=>$_SESSION['role']];
    create_log($conn,'ADMIN-PANEL',null,$admin,'ADMIN_EDIT_USER','WEB','INFO','LOW','Admin updated user ID '.$id,$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',json_encode($_POST));
    $msg='<div class="alert alert-success">User updated successfully.</div>';
  } else $msg='<div class="alert alert-danger">Error: '.h($conn->error).'</div>';
}
if(isset($_POST['add_user'])){
  $full=trim($_POST['full_name']); $username=trim($_POST['username']); $password=password_hash($_POST['password'],PASSWORD_DEFAULT); $role=$_POST['role']; $rfid=trim($_POST['rfid_uid']); $pin=trim($_POST['pin_code']);
  $stmt=$conn->prepare('INSERT INTO users(full_name,username,password,role,rfid_uid,pin_code,is_active) VALUES(?,?,?,?,?,?,1)');
  $stmt->bind_param('ssssss',$full,$username,$password,$role,$rfid,$pin);
  if($stmt->execute()) $msg='<div class="alert alert-success">New user added successfully.</div>'; else $msg='<div class="alert alert-danger">Error: '.h($conn->error).'</div>';
}
$users=$conn->query('SELECT * FROM users ORDER BY id'); header_html('Admin Users'); ?>
<h2>Admin Page: User Control</h2><p class="small-muted">Edit user role, RFID ID, PIN, and account status. Disabled users cannot login or access doors.</p><?=$msg?>
<div class="card p-3 mb-4"><h5>Add New User</h5><form method="post" class="row g-2"><div class="col-md-2"><input class="form-control" name="full_name" placeholder="Full name" required></div><div class="col-md-2"><input class="form-control" name="username" placeholder="Username" required></div><div class="col-md-2"><input class="form-control" type="password" name="password" placeholder="Password" required></div><div class="col-md-2"><select class="form-select" name="role"><option>admin</option><option>analyst</option><option>staff</option><option>cleaner</option></select></div><div class="col-md-2"><input class="form-control" name="rfid_uid" placeholder="RFID/ID e.g STAFF002"></div><div class="col-md-1"><input class="form-control" name="pin_code" placeholder="PIN"></div><div class="col-md-1"><button class="btn btn-success w-100" name="add_user">Add</button></div></form></div>
<div class="card p-3"><h5>Existing Users</h5><table class="table table-sm"><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>RFID/ID</th><th>PIN</th><th>Status</th><th>Action</th></tr><?php while($u=$users->fetch_assoc()): ?><tr><form method="post"><td><?=h($u['id'])?><input type="hidden" name="id" value="<?=$u['id']?>"></td><td><input class="form-control form-control-sm" name="full_name" value="<?=h($u['full_name'])?>"></td><td><input class="form-control form-control-sm" name="username" value="<?=h($u['username'])?>"></td><td><select class="form-select form-select-sm" name="role"><?php foreach(['admin','analyst','staff','cleaner'] as $role): ?><option <?=$u['role']==$role?'selected':''?>><?=$role?></option><?php endforeach; ?></select></td><td><input class="form-control form-control-sm" name="rfid_uid" value="<?=h($u['rfid_uid'])?>"></td><td><input class="form-control form-control-sm" name="pin_code" value="<?=h($u['pin_code'])?>"></td><td><label><input type="checkbox" name="is_active" <?=$u['is_active']?'checked':''?>> Active</label></td><td><button class="btn btn-primary btn-sm" name="update_user">Save</button></td></form></tr><?php endwhile; ?></table></div>
<?php footer_html(); ?>

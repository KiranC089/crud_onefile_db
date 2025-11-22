<?php
session_start();

/* CONFIG */
$DB_HOST="localhost"; $DB_USER="root"; $DB_PASS=""; $DB_NAME="crud_slide_v4";

/* DB CREATE + CONNECT */
$g=mysqli_connect($DB_HOST,$DB_USER,$DB_PASS);
mysqli_query($g,"CREATE DATABASE IF NOT EXISTS $DB_NAME");
$con=mysqli_connect($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);

/* TABLES */
mysqli_query($con,"CREATE TABLE IF NOT EXISTS auth_users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(200) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);");

mysqli_query($con,"CREATE TABLE IF NOT EXISTS users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20)
);");

/* HELPERS */
function e($v){ return htmlspecialchars($v,ENT_QUOTES); }
function isLogged(){ return isset($_SESSION['uid']); }
function needLogin(){ if(!isLogged()){ header("Location: crud_onefile_db.php?page=login"); exit; } }

$reg_errors=[]; $login_error="";

/* ================= REGISTER (NO AUTO LOGIN) ================= */
if(isset($_POST['action']) && $_POST['action']=="register"){
    $n=trim($_POST['name']);
    $em=trim($_POST['email']);
    $p=$_POST['password'];
    $p2=$_POST['password2'];

    if($n=="") $reg_errors[]="Name required";
    if(!filter_var($em,FILTER_VALIDATE_EMAIL)) $reg_errors[]="Valid email required";
    if(strlen($p)<6) $reg_errors[]="Password min 6 chars";
    if($p!=$p2) $reg_errors[]="Passwords not match";

    if(empty($reg_errors)){
        $q=mysqli_prepare($con,"SELECT id FROM auth_users WHERE email=?");
        mysqli_stmt_bind_param($q,"s",$em);
        mysqli_stmt_execute($q);
        mysqli_stmt_store_result($q);

        if(mysqli_stmt_num_rows($q)>0){
            $reg_errors[]="Email exists";
        } else {
            $hash=password_hash($p,PASSWORD_DEFAULT);
            $ins=mysqli_prepare($con,"INSERT INTO auth_users (name,email,password) VALUES(?,?,?)");
            mysqli_stmt_bind_param($ins,"sss",$n,$em,$hash);
            mysqli_stmt_execute($ins);
            header("Location: crud_onefile_db.php?page=login&msg=registered");
            exit;
        }
    }
}

/* ================= LOGIN ================= */
if(isset($_POST['action']) && $_POST['action']=="login"){
    $em=trim($_POST['email']);
    $p=$_POST['password'];

    $q=mysqli_prepare($con,"SELECT id,name,password FROM auth_users WHERE email=?");
    mysqli_stmt_bind_param($q,"s",$em);
    mysqli_stmt_execute($q);
    mysqli_stmt_bind_result($q,$uid,$uname,$hash);

    if(mysqli_stmt_fetch($q)){
        if(password_verify($p,$hash)){
            $_SESSION['uid']=$uid;
            $_SESSION['uname']=$uname;
            header("Location: crud_onefile_db.php?page=dashboard");
            exit;
        } else {
            $login_error="Wrong password";
        }
    } else {
        $login_error="Email not found";
    }
}

/* ================= LOGOUT ================= */
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: crud_onefile_db.php?page=login");
    exit;
}

/* ================= CRUD ================= */
if(isset($_POST['add_user']) && isLogged()){
    // basic escaping to avoid syntax issues
    $name = mysqli_real_escape_string($con, $_POST['u_name']);
    $email = mysqli_real_escape_string($con, $_POST['u_email']);
    $phone = mysqli_real_escape_string($con, $_POST['u_phone']);
    mysqli_query($con,"INSERT INTO users(name,email,phone) VALUES('$name','$email','$phone')");
    header("Location: crud_onefile_db.php?page=users");
    exit;
}

if(isset($_POST['update_user']) && isLogged()){
    $id = intval($_POST['u_id']);
    $name = mysqli_real_escape_string($con, $_POST['u_name']);
    $email = mysqli_real_escape_string($con, $_POST['u_email']);
    $phone = mysqli_real_escape_string($con, $_POST['u_phone']);
    mysqli_query($con,"UPDATE users SET name='$name',email='$email',phone='$phone' WHERE id=$id");
    header("Location: crud_onefile_db.php?page=users");
    exit;
}

/* ================ DELETE WITHOUT GAPS ================ */
/*
 When deleting a user we:
 1) Delete the target row
 2) Re-number ids to be continuous using a user-defined variable
 3) Reset AUTO_INCREMENT to last id + 1
*/
if(isset($_GET['delete_user']) && isLogged()){
    $delid = intval($_GET['delete_user']);

    // Delete the row first
    mysqli_query($con, "DELETE FROM users WHERE id = $delid");

    // Re-number ids to remove gaps
    // Use a transaction to be safer (note: ALTER TABLE may cause implicit commit)
    mysqli_query($con, "START TRANSACTION");
    mysqli_query($con, "SET @count = 0");
    mysqli_query($con, "UPDATE users SET id = (@count := @count + 1) ORDER BY id");
    mysqli_query($con, "ALTER TABLE users AUTO_INCREMENT = 1");
    mysqli_query($con, "COMMIT");

    header("Location: crud_onefile_db.php?page=users");
    exit;
}

$page = $_GET['page'] ?? (isLogged() ? "dashboard" : "login");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>CRUD Slide App</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
body{background:#eef2fb;font-family:Arial;}
.sidebar{width:220px;position:fixed;top:0;bottom:0;background:#212529;padding-top:20px;}
.sidebar a{color:white;display:block;padding:10px 16px;text-decoration:none;}
.sidebar a:hover{background:#333;}
.main{margin-left:240px;padding:20px;}
.topbar{background:#0d6efd;color:white;padding:10px 20px;}
.auth-wrap{max-width:600px;margin:60px auto;}
.auth-card{background:white;padding:25px;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,.1);overflow:hidden;width:100%;min-height:380px;}
.panels{display:flex;width:200%;transition:.5s ease;}
.panel{width:50%;padding:10px 15px;}
</style>
</head>

<body>

<?php if(isLogged()): ?>

<!-- Topbar -->
<div class="topbar">
  Welcome <?=e($_SESSION['uname'])?>
  <a href="?logout=1" class="btn btn-light btn-sm float-end">Logout</a>
</div>

<!-- Sidebar -->
<div class="sidebar">
  <a href="?page=dashboard">Dashboard</a>
  <a href="?page=users">Users</a>
  <a href="?page=reports">Reports</a>
  <a href="?page=profile">Profile</a>
</div>

<!-- Main Content -->
<div class="main">

<?php if($page=="dashboard"): ?>
<h2>Dashboard</h2>
<p>Total Users: <?= mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) c FROM users"))['c'] ?></p><br><br><br><br><br><br>
<h1 align=center>"EVERY STUDENT COUNTS,<br>EVERY RECORD MATTERS"</b></h1>

<?php elseif($page=="users"): needLogin(); ?>
<h2>Manage Users</h2>

<?php if(isset($_GET['edit_user'])):
$d=mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM users WHERE id=".intval($_GET['edit_user']))); ?>

<form method="POST" class="row g-2 mb-2">
<input type="hidden" name="u_id" value="<?=$d['id']?>">
<div class="col-md-4"><input class="form-control" name="u_name" value="<?=e($d['name'])?>"></div>
<div class="col-md-4"><input class="form-control" name="u_email" value="<?=e($d['email'])?>"></div>
<div class="col-md-3"><input class="form-control" name="u_phone" value="<?=e($d['phone'])?>"></div>
<div class="col-md-1"><button name="update_user" class="btn btn-primary">Save</button></div>
</form>

<?php else: ?>

<form method="POST" class="row g-2 mb-2">
<div class="col-md-4"><input class="form-control" name="u_name" placeholder="Name"></div>
<div class="col-md-4"><input class="form-control" name="u_email" placeholder="Email"></div>
<div class="col-md-3"><input class="form-control" name="u_phone" placeholder="Phone"></div>
<div class="col-md-1"><button name="add_user" class="btn btn-success">Add</button></div>
</form>

<?php endif; ?>

<table class="table table-bordered">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Action</th></tr>

<?php $q=mysqli_query($con,"SELECT * FROM users ORDER BY id");
while($r=mysqli_fetch_assoc($q)){ ?>
<tr>
<td><?=e($r['id'])?></td>
<td><?=e($r['name'])?></td>
<td><?=e($r['email'])?></td>
<td><?=e($r['phone'])?></td>
<td>
<a class="btn btn-primary btn-sm" href="?page=users&edit_user=<?=e($r['id'])?>">Edit</a>
<a class="btn btn-danger btn-sm" onclick="return confirm('Delete?')" href="?page=users&delete_user=<?=e($r['id'])?>">Delete</a>
</td>
</tr>
<?php } ?>
</table>

<?php elseif($page=="reports"): needLogin(); ?>
<h2>Reports</h2>
<table class="table table-bordered">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>
<?php $q=mysqli_query($con,"SELECT * FROM users ORDER BY id");
while($r=mysqli_fetch_assoc($q)){ ?>
<tr><td><?=e($r['id'])?></td><td><?=e($r['name'])?></td><td><?=e($r['email'])?></td><td><?=e($r['phone'])?></td></tr>
<?php } ?>
</table>

<?php elseif($page=="profile"): needLogin(); ?>
<h2>Your Profile</h2>
<?php $u=mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM auth_users WHERE id=".intval($_SESSION['uid']))); ?>
<p><b>Name:</b> <?=e($u['name'])?></p>
<p><b>Email:</b> <?=e($u['email'])?></p>
<p><b>Joined:</b> <?=e($u['created_at'])?></p>

<?php endif; ?>

</div>

<?php else: ?>

<!-- PUBLIC AUTH -->
<div class="auth-wrap">
<div class="auth-card">

<div id="panels" class="panels">

<!-- LOGIN LEFT -->
<div class="panel">
<h3>Login</h3>

<?php if(isset($_GET['msg']) && $_GET['msg']=="registered"): ?>
<div class="alert alert-success">Registration successful! Login now.</div>
<?php endif; ?>

<?php if($login_error): ?>
<div class="alert alert-danger"><?=e($login_error)?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="action" value="login">
<input class="form-control mb-2" name="email" placeholder="Email">
<input class="form-control mb-2" type="password" name="password" placeholder="Password">
<button class="btn btn-primary w-100">Login</button>
</form>

<button onclick="goRegister()" class="btn btn-link mt-2">Go to Register</button>
</div>

<!-- REGISTER RIGHT -->
<div class="panel">
<h3>Register</h3>

<?php if($reg_errors): ?>
<div class="alert alert-danger"><?php foreach($reg_errors as $e) echo e($e)."<br>"; ?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="action" value="register">
<input class="form-control mb-2" name="name" placeholder="Name">
<input class="form-control mb-2" name="email" placeholder="Email">
<input class="form-control mb-2" type="password" name="password" placeholder="Password">
<input class="form-control mb-2" type="password" name="password2" placeholder="Confirm Password">
<button class="btn btn-success w-100">Register</button>
</form>

<button onclick="goLogin()" class="btn btn-link mt-2">Go to Login</button>
</div>

</div>
</div>
</div>

<script>
function goRegister(){ document.getElementById("panels").style.transform="translateX(-50%)"; }
function goLogin(){ document.getElementById("panels").style.transform="translateX(0%)"; }
</script>

<?php endif; ?>
</body>
</html>

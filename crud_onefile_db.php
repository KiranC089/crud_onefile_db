<?php

// STEP 1: Connect to MySQL WITHOUT database
$server = "localhost";
$user = "root";
$pass = "";

// First connection
$con = mysqli_connect($server, $user, $pass);

if (!$con) {
    die("Connection Failed: " . mysqli_connect_error());
}

// STEP 2: Create database if not exists
mysqli_query($con, "CREATE DATABASE IF NOT EXISTS crud_auto");

// STEP 3: Connect again WITH database
$con = mysqli_connect($server, $user, $pass, "crud_auto");

if (!$con) {
    die("DB Connection Failed: " . mysqli_connect_error());
}

// STEP 4: Create users table
mysqli_query($con, "CREATE TABLE IF NOT EXISTS users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20)
)");


// ---------------- CRUD LOGIC ---------------- //

// ADD — NO REDIRECT
if (isset($_POST['add'])) {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    mysqli_query($con, "INSERT INTO users (name,email,phone)
                         VALUES ('$name','$email','$phone')");
}

// UPDATE — NO REDIRECT
if (isset($_POST['update'])) {
    $id    = $_POST['id'];
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    mysqli_query($con, "UPDATE users SET 
        name='$name',
        email='$email',
        phone='$phone'
        WHERE id=$id");
}

// DELETE — NO REDIRECT
if (isset($_GET['delete'])) {
    mysqli_query($con, "DELETE FROM users WHERE id=".$_GET['delete']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Instant CRUD</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container mt-5">

<h3 class="mb-4">Instant CRUD — No Redirect Version</h3>

<div class="card p-4 mb-4">

<?php if (isset($_GET['edit'])): 
    $id = $_GET['edit'];
    $e = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id=$id"));
?>

<h4>Edit User</h4>

<!-- FIXED ACTION -->
<form method="POST" action="crud_onefile_db.php">
    <input type="hidden" name="id" value="<?= $e['id'] ?>">

    <input class="form-control mb-2" name="name" value="<?= $e['name'] ?>" required>
    <input class="form-control mb-2" name="email" value="<?= $e['email'] ?>" required>
    <input class="form-control mb-2" name="phone" value="<?= $e['phone'] ?>" required>

    <button name="update" class="btn btn-primary">Update</button>
    <a href="crud_onefile_db.php" class="btn btn-secondary">Cancel</a>
</form>

<?php else: ?>

<h4>Add User</h4>

<!-- FIXED ACTION -->
<form method="POST" action="crud_onefile_db.php">
    <input class="form-control mb-2" name="name" placeholder="Name" required>
    <input class="form-control mb-2" name="email" placeholder="Email" required>
    <input class="form-control mb-2" name="phone" placeholder="Phone" required>

    <button name="add" class="btn btn-success">Add</button>
</form>

<?php endif; ?>

</div>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Action</th>
</tr>
</thead>

<tbody>

<?php
$data = mysqli_query($con, "SELECT * FROM users ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($data)) {
?>

<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['email'] ?></td>
    <td><?= $row['phone'] ?></td>
    <td>
        <a href="crud_onefile_db.php?edit=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
        <a href="crud_onefile_db.php?delete=<?= $row['id'] ?>" 
           onclick="return confirm('Delete?')"
           class="btn btn-danger btn-sm">Delete</a>
    </td>
</tr>

<?php } ?>

</tbody>

</table>

</div>

</body>
</html>

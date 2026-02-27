<?php

include 'includes/db.php';
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

/* 🔒 ADMIN ONLY ACCESS */
if($_SESSION['role'] !== 'admin'){
    echo "<div style='padding:20px;font-family:Segoe UI;'>
            <h3 style='color:red;'>Access Denied</h3>
            <p>You do not have permission to access this page.</p>
          </div>";
    exit;
}

/* ADD USER */
if(isset($_POST['submit'])){

    $user_id = $conn->real_escape_string($_POST['user_id']);
    $password = md5($_POST['password']);
    $role = $_POST['role'];

    $check = $conn->query("SELECT * FROM users WHERE user_id='$user_id'");

    if($check->num_rows > 0){
        $error = "User ID already exists!";
    } else {
        $insert = $conn->query("INSERT INTO users (user_id,password,role) 
                                VALUES ('$user_id','$password','$role')");
        if($insert){
            $success = "User Added Successfully!";
        } else {
            $error = "Something went wrong!";
        }
    }
}

/* FETCH ALL USERS */
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
body { background: #f4f6f9; font-family: 'Segoe UI'; }

.main-container { padding: 20px; }

.page-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; }

.card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.form-group { margin-bottom: 12px; }

.form-group label {
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 4px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}

.password-wrapper {
    position: relative;
}

.password-wrapper i {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #64748b;
}

.btn-primary {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 4px;
    border: none;
    background: #2563eb;
    color: #fff;
    cursor: pointer;
}

.btn-primary:hover { background: #1e40af; }

.alert-success {
    background: #dcfce7;
    color: #166534;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
    margin-bottom: 10px;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
    margin-bottom: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

th, td {
    padding: 8px;
    border: 1px solid #e5e7eb;
    text-align: left;
}

th {
    background: #f3f4f6;
}

.role-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    color: #fff;
}

.admin-badge { background: #2563eb; }
.subadmin-badge { background: #16a34a; }
</style>

<div class="main-container">

    <h2 class="page-title">Add User</h2>

    <div class="card">

        <?php if(isset($error)) echo "<div class='alert-danger'>$error</div>"; ?>
        <?php if(isset($success)) echo "<div class='alert-success'>$success</div>"; ?>

        <form method="POST">

            <div class="form-group">
                <label>User ID</label>
                <input type="text" name="user_id" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="sub_admin">Sub Admin</option>
                </select>
            </div>

            <button type="submit" name="submit" class="btn-primary">
                Add User
            </button>

        </form>
    </div>

    <!-- USER LIST -->
    <h2 class="page-title">All Users</h2>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['user_id']); ?></td>
                    <td>
                        <?php if($row['role'] == 'admin'): ?>
                            <span class="role-badge admin-badge">Admin</span>
                        <?php else: ?>
                            <span class="role-badge subadmin-badge">Sub Admin</span>
                        <?php endif; ?>
                    </td>
                    <td><?= ucfirst($row['status']); ?></td>
                    <td><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", function () {
    const type = password.getAttribute("type") === "password" ? "text" : "password";
    password.setAttribute("type", type);
    this.classList.toggle("fa-eye");
    this.classList.toggle("fa-eye-slash");
});
</script>
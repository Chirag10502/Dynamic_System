<?php
session_start();
include 'includes/db.php';



/* Redirect if already logged in */
if(isset($_SESSION['user_id'])){
    header("Location: View_Records.php");
    exit;
}



if(isset($_POST['login'])){

    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $password = md5($_POST['password']);

    $query = "SELECT * FROM users 
              WHERE user_id='$user_id' 
              AND password='$password' 
              AND status='active'";

    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        header("Location: View_Records.php");
        exit;
    } else {
        $error = "Invalid User ID or Password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Dynamic CRM</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body{
            margin:0;
            font-family: 'Segoe UI', sans-serif;
            background: #f1f5f9;
        }

        /* Top Header */
        .topbar {
            background: #03204d;
            padding: 12px 25px;
            text-align: center;
        }

        .topbar img {
            height: 40px;
        }

        /* Center Login Box */
        .login-wrapper{
            display:flex;
            justify-content:center;
            align-items:center;
            height:calc(100vh - 70px);
        }

        .login-box{
            background:#fff;
            padding:35px;
            width:350px;
            border-radius:8px;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        .login-box h2{
            text-align:center;
            margin-bottom:20px;
            font-size:18px;
            font-weight:600;
        }

        .login-box input{
            width:100%;
            padding:8px 10px;
            margin:10px 0;
            border-radius:4px;
            border:1px solid #cbd5e1;
            font-size:13px;
        }

        .password-wrapper{
            position:relative;
        }

        .password-wrapper i{
            position:absolute;
            right:10px;
            top:50%;
            transform:translateY(-50%);
            cursor:pointer;
            color:#64748b;
            font-size:14px;
        }

        .login-box button{
            width:100%;
            padding:8px;
            background:#2563eb;
            border:none;
            color:#fff;
            font-weight:500;
            font-size:13px;
            border-radius:4px;
            cursor:pointer;
            margin-top:5px;
        }

        .login-box button:hover{
            background:#1e40af;
        }

        .error{
            background:#fee2e2;
            color:#991b1b;
            padding:6px 10px;
            border-radius:4px;
            font-size:13px;
            margin-bottom:10px;
            text-align:center;
        }
    </style>
</head>
<body>

<div class="topbar">
    <img src="Assets/logo.jpg" alt="Logo">
</div>

<div class="login-wrapper">
    <div class="login-box">

        <h2>Dynamic CRM Login</h2>

        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST">
            <input type="text" name="user_id" placeholder="User ID" required>

            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

            <button type="submit" name="login">Login</button>
        </form>

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

</body>
</html>


<?php if(isset($_GET['timeout'])): ?>
    <div class="error">
        Session expired. Please login again.
    </div>
<?php endif; ?>
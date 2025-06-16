<?php
include '../includes/db.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "<p class='error'>Please fill in all fields.</p>";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                setcookie("user_id", $row['id'], time() + (86400 * 30), "/"); // 30 days
                setcookie("username", $row['username'], time() + (86400 * 30), "/"); // 30 days
                header("Location: index.php");
                exit();
            } else {
                $message = "<p class='error'>Invalid username or password.</p>";
            }
        } else {
            $message = "<p class='error'>Invalid username or password.</p>";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/login_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">

    <title>Login</title>
    <style>
        .error {
            color: red;
            margin: 10px 0;
        }

        .success {
            color: green;
            margin: 10px 0;
        }
    </style>
</head>

<body style="background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../images/salon1.jpg'); background-size: cover; background-position: center;height: 100vh;color:white">
    <form action="" method="post" class="form-container p-3 py-4 px-4 bg-dark" style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); width: 30vw;">
        <h2>Welcome to Salon!</h1>

            <h2>Login</h2>
            <?php echo isset($message) ? $message : ''; ?>

            <input type="text" name="username" placeholder="Username" class="bg-light-subtle"> <br>
            <input type="password" name="password" placeholder="Password" class="bg-light-subtle"> <br>
            <button type="submit" name="login">Login</button>
            <div class="mt-2">

                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>

    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>

</body>

</html>
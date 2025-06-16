<?php
include '../includes/db.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    if (!empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "<p class='error'>Invalid email address.</p>";
        } else {
            $check_sql = "SELECT * FROM users WHERE username = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $message = "<p class='error'>Username already exists. Please choose another one.</p>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insert_sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
                $stmt_insert = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt_insert, "sss", $username, $hashed_password, $email);

                if (mysqli_stmt_execute($stmt_insert)) {
                    $message = "<p class='success'>Registration successful! You can now <a href='login.php'>log in</a>.</p>";
                } else {
                    $message = "<p class='error'>Error: " . mysqli_error($conn) . "</p>";
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $message = "<p class='error'>Please fill in all fields.</p>";
    }
    mysqli_close($conn);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/login_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">

    <title>Register</title>
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

<body style="background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../images/salon1.jpg'); background-size: cover; background-position: center; color:white">
    <form action="" method="post" class="form-container p-4 bg-dark" style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); width: 30vw;">
    <h2>Welcome to Salon!</h1>    
    <h2>Register</h2>
        <?php echo $message; ?>
        <input type="text" name="username" placeholder="Username" class="bg-light-subtle"><br>
        <input type="email" name="email" placeholder="Email" class="bg-light-subtle"><br>
        <input type="password" name="password" placeholder="Password" class="bg-light-subtle"><br>
        <button type="submit" name="register" class=" border-rounded"> Register</button>
        <p class="mt-2">Already have an account? <a href="login.php">Login</a></p>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>

</body>

</html>
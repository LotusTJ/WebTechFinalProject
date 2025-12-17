<?php
session_start();
require_once '../settings/config.php';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($username === "" || $email === "" || $password === "" || $confirm_password === "") {
        $error_message = "All fields are required. One or more fields are empty";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {

        $conn = getDBConnection();

     
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "An account with this email already exists. Maybe you should try logging in?";
        } else {

          
            $hash_password = password_hash($password,PASSWORD_BCRYPT); 
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hash_password);//this is inserting the parameters into the database.

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;

                header("Location: login_page.php");
                exit();
            } else {
                $error_message = "Error: Could not create account.";
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Meal-Maker</title>
    <link href="../styling/welcomepagestyling.css" rel="stylesheet" type="text/css">

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f5f5f5;
            margin: 0;
        }
        .form-container {
            width: 400px;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10;
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #ff6347;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #ff6347;
            color: white;
            border: none;
            border-radius: 8px;
            margin-top: 10px;
            cursor: pointer;
        }
        button:hover {
            background: #e37a32;
        }
        .error {
            text-align: center;
            color: red;
            margin-bottom: 10px;
        }
        .top-right-image {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 150px;
            border-radius: 50%;
            z-index: 20;
        }
    </style>
</head>

<body>

    <img src="../images/mealmaker_logosquared.png" class="top-right-image">

    <div class="background"></div>

    <div class="form-container">
        <h2>Create an Account</h2>

        <?php if ($error_message !== ""): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>

        <form action="" method="POST">

            <input type="text" name="username" placeholder="Username" required>

            <input type="email" name="email" placeholder="Email" required>

            <input type="password" name="password" placeholder="Password" required>

            <input type="password" name="confirm_password" placeholder="Confirm Password" required>

            <button type="submit">Register</button>
        </form>

        <p style="text-align:center; margin-top:15px;">
            Already have an account? <a href="login_page.php">Login here</a>
        </p>
    </div>
<script>
    document.getElementById('registerForm').addEventListener('submit', function(event) {
        
        const username = document.getElementsByName('username')[0].value;
        const email = document.getElementsByName('email')[0].value;
        const password = document.getElementsByName('password')[0].value;
        const confirmPassword = document.getElementsByName('confirm_password')[0].value;
        
        
        const usernameRegex = /^[a-zA-Z0-9_]{4,30}$/;
        
       
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
   
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;

        let errorMessage = "";

        
        if (!usernameRegex.test(username)) {
            errorMessage += "Username must be 4-50 characters and contain only letters, numbers, or underscores.\n";
        }

        if (!emailRegex.test(email)) {
            errorMessage += "Invalid email address type.\n";
        }

        if (!passwordRegex.test(password)) {
            errorMessage += "Password needs at least character, minimum one uppercase letter, one lowercase letter, and one number.\n";
        }

        if (password !== confirmPassword) {
            errorMessage += "Passwords do not match.\n";
        }

    
        if (errorMessage !== "") {
            alert(errorMessage); 
            event.preventDefault(); 
        }
    });
</script>
</body>
</html>

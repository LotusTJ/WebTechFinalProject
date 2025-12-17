<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
require_once '../settings/config.php';

$error_message = "";




if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email === "" || $password === "") {
        $error_message = "FILL IN ALL FIELDS.";
    } else {
        $conn = getDBConnection();

        $stmt = $conn->prepare("select user_id, username, password from users where email = ?");
        $stmt->bind_param("s", $email);

        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1){
            $stmt->bind_result($user_id, $username, $stored_password);
            $stmt->fetch();

           
            if (password_verify($password, $stored_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;

                header("Location: browse_recipes.php");
                exit();
            } else {
                $error_message = "Wrong Password Entered. Try again.";
            }
        } else {
            $error_message = "Email matching error, that email is not registered with an account.";
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
    <title>Login - Meal-Maker</title>
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
        <h2>Login</h2>

        <?php if ($error_message !== ""): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>

        <form action="" method="POST">

            <input type="email" name="email" placeholder="Email" required>

            <input type="password" name="password" placeholder="Password" required>

            <button type="submit">Login</button>
        </form>

        <p style="text-align:center; margin-top:15px;">
            Don't have an account? <a href="register_page.php">Register here</a>
        </p>
    </div>

</body>
</html>

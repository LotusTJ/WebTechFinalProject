<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_page.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Meal-Maker</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            background: #ff9d00b9;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin-bottom: 40px;
            text-align: center;
        }
        
        .header-section h1 {
            color: #ff6347;
            margin: 0 0 10px 0;
            font-size: 42px;
            font-weight: bold;
        }
        
        .header-section p {
            color: #666;
            font-size: 18px;
            margin: 0;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: #ff6347;
            border: 2px solid #ff6347;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #ff6347;
            color: white;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .dashboard-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .card-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ff6347;
        }
        
        .card-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
                
        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 32px;
            }
            
            .logout-btn {
                position: static;
                display: block;
                margin: 20px auto 0;
                width: fit-content;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <a href="logout.php" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>

    <div class="dashboard-container">
        
        <div class="header-section">
            <h1>Welcome to Meal-Maker!</h1>
            <p>Hello, <strong><?= htmlspecialchars($username) ?></strong>! What would you like to do today?</p>
        </div>
        
        <div class="dashboard-cards">
            
            <!-- Browse Recipes Card -->
            <a href="browse_recipes.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h2 class="card-title">Browse Recipes</h2>
                
            </a>
            
            <!-- Add Recipe Card -->
            <a href="user_add_recipe.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fa-solid fa-plus-circle"></i>
                </div>
                <h2 class="card-title">Add Your Recipe</h2>
                
            </a>
            
            <!-- Profile Card -->
            <a href="user_profile_view.php" class="dashboard-card">
                <div class="card-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <h2 class="card-title">My Profile</h2>
                
            </a>
            
        </div>
        
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
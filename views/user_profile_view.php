<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../settings/config.php'; 

$conn = getDBConnection(); 
$user_id = $_SESSION['user_id'] ?? null;

//Fetch all uploaded recipes by the logged-in user
$recipes = []; 

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Meal-Maker</title>
    
  
    <!-- Custom CSS -->
    <link href="../styling/userprofileviewstyling.css" rel="stylesheet" type="text/css">

</head>
<a href="browse_recipes.php" class="back-link">← Back to Browse Recipes</a>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Your Profile</h1>
        <h2 class="mb-3">Your Uploaded Recipes</h2>
        <?php if (empty($recipes)): ?>
            <p>You haven't uploaded any recipes yet. <a href="user_add_recipe.php">Add a new recipe</a> now!</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($recipes as $recipe): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            
                            
                        
                            <div class="card-body">
                                <h5 class="card-title
"><?= htmlspecialchars($recipe['name']) ?></h5>
                                <p class="card-text">Country: <?= htmlspecialchars($recipe['country']) ?></p>
                                <a href="recipe_details.php?id=<?= $recipe['recipe_id'] ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    
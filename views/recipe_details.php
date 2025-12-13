<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../settings/config.php';

$recipe = null;
$ingredients = [];
$error_message = "";

//Check if recipe ID is provided in URL, to then joint the database and get the recipe details
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $recipe_id = (int)$_GET['id'];
    
    $conn = getDBConnection();
    
    //Get recipe details with username (JOIN with users table)
    $stmt = $conn->prepare("SELECT r.*, u.username FROM recipes r JOIN users u ON r.user_id = u.user_id WHERE r.recipe_id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $recipe = $result->fetch_assoc();
    } else {
        $error_message = "Recipe not found.";
    }
    $stmt->close();
    
    //Get ingredients for this recipe
    if ($recipe) {
        $stmt = $conn->prepare("SELECT ingredient_name FROM ingredients WHERE recipe_id = ? ORDER BY ingredient_id ASC");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ingredients = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    $conn->close();
} else {
    $error_message = "No recipe selected.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $recipe ? htmlspecialchars($recipe['name']) : 'Recipe Details' ?> - Meal-Maker</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="../styling/recipedetailsstyling.css" rel="stylesheet" type="text/css">
</head>
<body>

    <div class="container">
        <!-- Bootstrap Back Button with Font Awesome Icon -->
        <a href="browse_recipes.php" class="btn btn-primary mb-3">
            <i class="fa-solid fa-arrow-left"></i> Back to Browse Recipes
        </a>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <h2><?= htmlspecialchars($error_message) ?></h2>
                <p>The recipe you're looking for doesn't exist or has been removed.</p>
            </div>
        <?php elseif ($recipe): ?>
            
            <div class="recipe-header">
                <h1><?= htmlspecialchars($recipe['name']) ?></h1>
                <p class="text-muted mb-3">
                    <i class="fa-solid fa-user"></i> Created by: <strong><?= htmlspecialchars($recipe['username']) ?></strong>
                </p>
                <div class="recipe-meta">
                    <span class="meta-item"><i class="fa-solid fa-location-dot"></i> Location: <?= htmlspecialchars($recipe['country']) ?></span>
                    <span class="meta-item"><i class="fa-solid fa-utensils"></i> Type: <?= htmlspecialchars(ucfirst($recipe['type'])) ?></span>
                    <span class="meta-item"><i class="fa-solid fa-fire"></i> Estimated Calories: <?= htmlspecialchars($recipe['calories']) ?> kcal</span>
                    <span class="meta-item"><i class="fa-solid fa-dollar-sign"></i> Estimated Budget: $<?= number_format($recipe['budget'], 2) ?></span>
                    <span class="meta-item"><i class="fa-solid fa-calendar"></i> Date Created: <?= date('M j, Y', strtotime($recipe['created_at'])) ?></span>
                </div>
            </div>

            <div class="recipe-content">
                
                <?php if (!empty($recipe['image_name'])): ?>
                    <div class="recipe-image-container">
                        <img src="../images/<?= htmlspecialchars($recipe['image_name']) ?>" 
                             alt="<?= htmlspecialchars($recipe['name']) ?>" 
                             class="recipe-image img-fluid">
                    </div>
                <?php endif; ?>

                <div class="ingredients-section">
                    <h2><i class="fa-solid fa-list"></i> Ingredients</h2>
                    <?php if (!empty($ingredients)): ?>
                        <ul class="ingredients-list list-unstyled">
                            <?php foreach ($ingredients as $ingredient): ?>
                                <li><i class="fa-solid fa-check text-success"></i> <?= htmlspecialchars($ingredient['ingredient_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-data text-muted fst-italic">No ingredients listed for this recipe.</p>
                    <?php endif; ?>
                </div>

                <div class="instructions-section">
                    <h2><i class="fa-solid fa-book-open"></i> Instructions</h2>
                    <div class="instructions-text">
                        <?= nl2br(htmlspecialchars($recipe['instructions'])) ?>
                    </div>
                </div>

            </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
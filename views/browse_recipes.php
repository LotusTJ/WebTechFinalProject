<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../settings/config.php'; 

$conn = getDBConnection(); 

$where_clauses = [];
$params = [];
$param_types = '';
$error_message = null;

$search_ingredient_input = trim($_GET['ingredient'] ?? '');
$search_ingredients = [];
if (!empty($search_ingredient_input)) {
    $search_ingredients = array_filter(array_map('trim', explode(',', $search_ingredient_input)));
}

$country_of_origin = trim($_GET['country'] ?? '');

$recipe_types_selected = $_GET['type'] ?? [];
if (!is_array($recipe_types_selected)) {
    $recipe_types_selected = [$recipe_types_selected];
}
$recipe_types_selected = array_filter(array_map('trim', $recipe_types_selected));

$sql_select = "SELECT r.* FROM recipes r";
$sql_join = "";
$sql_group = "";
$sql_having = "";

if (!empty($search_ingredients)) {
    $sql_join .= " JOIN ingredients i ON r.recipe_id = i.recipe_id ";
    
    $ingredient_conditions = [];
    foreach ($search_ingredients as $ingredient) {
        $ingredient_conditions[] = " LOWER(i.ingredient_name) LIKE LOWER(?) "; 
        $params[] = '%' . $ingredient . '%';
        $param_types .= 's';
    }
    
  
    $where_clauses[] = "(" . implode(" OR ", $ingredient_conditions) . ")";
    
    $sql_group = " GROUP BY r.recipe_id ";
    
    $sql_having = " HAVING COUNT(DISTINCT i.ingredient_id) >= " . count($search_ingredients);
}

if (!empty($country_of_origin)) {
    $where_clauses[] = " r.country = ? ";
    $params[] = $country_of_origin;
    $param_types .= 's';
}

if (!empty($recipe_types_selected)) {
    $type_placeholders = implode(',', array_fill(0, count($recipe_types_selected), '?'));
    $where_clauses[] = " r.type IN ({$type_placeholders}) ";
    
    foreach ($recipe_types_selected as $type) {
        $params[] = $type;
        $param_types .= 's';
    }
}

$sql = $sql_select . $sql_join;

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= $sql_group . $sql_having . " ORDER BY r.name ASC";

$recipes = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params); 
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $recipes = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $error_message = "Error executing query: " . $stmt->error;
    }
    $stmt->close();
} else {
    $error_message = "Error preparing statement: " . $conn->error;
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Recipes</title>
    <link href="../styling/browserecipesstyling.css" rel="stylesheet" type="text/css">
    <style>
        .upload-recipe-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #eb2300ff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .upload-recipe-btn:hover {
            background: #e37a32;
        }

        .user-profile-btn {
            position: fixed;
            top: 70px;
            right: 20px;
            padding: 12px 24px;
            background: #fa6133ff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .user-profile-btn:hover {
            background: #e37a32;
        }

        .dashboard-btn {
            position: fixed;
            top: 120px;
            right: 20px;
            padding: 12px 24px;
            background: #ff8c42ff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .dashboard-btn:hover {
            background: #e37a32;
        }
    </style>
</head>
<body>
    <a href="user_add_recipe.php" class="upload-recipe-btn">Upload Your OWN Recipe</a>
    <a href="user_profile_view.php" class="user-profile-btn">View Your Profile</a>
    <a href="dashboard.php" class="dashboard-btn">Go to Dashboard</a>
    
    <h1>Browse Meal-Maker Recipes</h1>
    
    <div class="filter-form">
        <h2>Refine Your Search</h2>
        <form action="browse_recipes.php" method="GET">
            
            <div class="two-column">
                <div>
                    <label for="ingredient">Ingredient Tags </label>
                    <input type="text" id="ingredient" name="ingredient" value="<?= htmlspecialchars($search_ingredient_input) ?>" placeholder="e.g., Milk, Eggs, Sugar">

                    <label>Recipe Type:</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="type[]" value="sweet" 
                            <?= in_array('sweet', $recipe_types_selected) ? 'checked' : '' ?>> Sweet
                        </label>
                        <label>
                            <input type="checkbox" name="type[]" value="savory" 
                            <?= in_array('savory', $recipe_types_selected) ? 'checked' : '' ?>> Savory
                        </label>
                    </div>

                    <label for="country">Country of Origin</label>
                    <select id="country" name="country">
                        <option value="">-- All Countries --</option>
                        <?php
                        $countries = ['Italy', 'Mexico', 'United States', 'China', 'India']; 
                        foreach ($countries as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" 
                            <?= ($country_of_origin === $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>
            </div>
            
            <div class="button-group">
                <button type="submit">Filter Recipes</button>
                <a href="browse_recipes.php" class="clear-filters">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if ($error_message): ?>
        <p class="error-message"><?= $error_message ?></p>
    <?php elseif (empty($recipes)): ?>
        <p>No recipes found matching your criteria. Try adjusting your filters or <a href="browse_recipes.php">clearing them</a>.</p>
    <?php else: ?>
        <h3>Results: Found <?= count($recipes) ?> recipe(s)</h3>
        <?php foreach ($recipes as $recipe): ?>
            <div class="recipe-card">
                <h2><?= htmlspecialchars($recipe['name']) ?></h2>
                <div class="two-column">
                    <div>
                        <p><strong>Type:</strong> <?= htmlspecialchars($recipe['type']) ?></p>
                        <p><strong>Country:</strong> <?= htmlspecialchars($recipe['country']) ?></p>
                        <p><strong>Estimated Calories:</strong> <?= htmlspecialchars($recipe['calories']) ?> kcal</p>
                        <p><strong>Budget:</strong> <?= htmlspecialchars($recipe['budget']) ?></p>
                        <p><strong>Created:</strong> <?= date('M j, Y', strtotime($recipe['created_at'])) ?></p>
                    </div>
                    <div>
                        <?php if (!empty($recipe['image_name'])): ?>
                            <img src="../images/<?= htmlspecialchars($recipe['image_name']) ?>" alt="<?= htmlspecialchars($recipe['name']) ?>" class="recipe-image">
                        <?php else: ?>
                            <p>[Image not available]</p>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="recipe_details.php?id=<?= $recipe['recipe_id'] ?>" style="display: inline-block; margin-top: 15px; color: #ff6347; font-weight: bold;">View Full Recipe Details &raquo;</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
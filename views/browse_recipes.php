<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../settings/config.php'; 

$conn = getDBConnection(); 

$where_clauses = [];
$params = [];
$param_types = '';
$search_ingredient = trim($_GET['ingredient'] ?? '');
$min_calories = (int)($_GET['min_calories'] ?? 0);
$max_calories = (int)($_GET['max_calories'] ?? 10000);
$country_of_origin = trim($_GET['country'] ?? '');
$recipe_type = trim($_GET['type'] ?? '');
$error_message = null;

$sql_select = "SELECT r.* FROM recipes r";
$sql_join = "";
$sql_group = "";

if (!empty($search_ingredient)) {
    $sql_join .= " JOIN ingredients i ON r.recipe_id = i.recipe_id ";
    $where_clauses[] = " i.ingredient_name LIKE ? ";
    $params[] = '%' . $search_ingredient . '%';
    $param_types .= 's';
    $sql_group = " GROUP BY r.recipe_id ";
}

if ($min_calories > 0 || $max_calories < 10000) {
    $where_clauses[] = " r.calories BETWEEN ? AND ? ";
    $params[] = $min_calories;
    $params[] = $max_calories;
    $param_types .= 'ii';
}

if (!empty($country_of_origin)) {
    $where_clauses[] = " r.country = ? ";
    $params[] = $country_of_origin;
    $param_types .= 's';
}

if (!empty($recipe_type)) {
    $where_clauses[] = " r.type = ? ";
    $params[] = $recipe_type;
    $param_types .= 's';
}

$sql = $sql_select . $sql_join;

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= $sql_group . " ORDER BY r.name ASC";

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
    <title>Browse Recipes - Meal-Maker</title>
    <style>
        body { font-family: sans-serif; background-color: #f7f7f7; color: #333; padding: 20px; }
        .recipe-card { 
            background: white; 
            border: 1px solid #ddd; 
            padding: 20px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-form { 
            margin-bottom: 40px; 
            padding: 20px; 
            border: 1px solid #ff6347;
            background: #fff;
            border-radius: 8px;
        }
        label { 
            display: block; 
            margin-top: 10px; 
            font-weight: bold; 
            color: #ff6347;
        }
        input[type="text"], input[type="number"] { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px;
            box-sizing: border-box;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        button[type="submit"] { 
            padding: 10px 20px; 
            background: #ff6347; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            flex-grow: 1;
        }
        button[type="submit"]:hover { 
            background: #e37a32;
        }
        a.clear-filters {
            display: block;
            padding: 10px 20px;
            text-align: center;
            border: 1px solid #ff6347;
            color: #ff6347;
            border-radius: 4px;
            text-decoration: none;
            flex-grow: 1;
        }
        h1 { color: #ff6347; }
        .recipe-image { max-width: 100%; height: auto; border-radius: 4px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>🍲 Browse Meal-Maker Recipes</h1>

    <div class="filter-form">
        <h2>Refine Your Search</h2>
        <form action="browse_recipes.php" method="GET">
            
            <div class="two-column">
                <div>
                    <label for="ingredient">Ingredient Tag:</label>
                    <input type="text" id="ingredient" name="ingredient" value="<?= htmlspecialchars($search_ingredient) ?>" placeholder="e.g., Milk, Eggs">

                    <label for="type">Recipe Type:</label>
                    <input type="text" id="type" name="type" value="<?= htmlspecialchars($recipe_type) ?>" placeholder="e.g., Savor or Sweet>

                    <label for="country">Country of Origin:</label>
                    <input type="text" id="country" name="country" value="<?= htmlspecialchars($country_of_origin) ?>" placeholder="e.g., China, Italy">
                </div>
                <div>
                    
                    </div>
                    <div style="height: 10px;"></div> 
                </div>
            </div>
            
            <div class="button-group">
                <button type="submit">Filter Recipes</button>
                <a href="browse_recipes.php" class="clear-filters">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if ($error_message): ?>
        <p style="color: red; padding: 10px; border: 1px solid red; background: #fee; border-radius: 4px;"><?= $error_message ?></p>
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
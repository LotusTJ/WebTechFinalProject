<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login_page.php");
    exit();
}

require_once '../settings/config.php';

$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = trim($_POST['name']);
    $country = trim($_POST['country']);
    $type = trim($_POST['type']);
    $calories = (int)($_POST['calories']);
    $budget = (float)($_POST['budget']);
    $instructions = trim($_POST['instructions']);
    $user_id = $_SESSION['user_id'];
    
    
    $ingredients = $_POST['ingredients'] ?? [];
    $ingredients = array_filter(array_map('trim', $ingredients)); //this is just to remove empty ingredients and trim spaces.
    
  
    if (empty($name) || empty($country) || empty($type) || empty($instructions)) {
        $error_message = "Please fill in all required fields (name, country, type, and instructions).";
    } elseif (empty($ingredients)) {
        $error_message = "Please add at least one ingredient.";
    } else {
        
        //image uploading, taken from Claude
       $image_name = null;

if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] === UPLOAD_ERR_OK) {

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($_FILES['recipe_image']['tmp_name']);

    if (in_array($file_type, $allowed_types)) {

        $file_extension = strtolower(pathinfo($_FILES['recipe_image']['name'], PATHINFO_EXTENSION));
        $image_name = uniqid('recipe_', true) . '.' . $file_extension;

        // absolute server path (SAFE)
        $upload_dir = __DIR__ . '/../images/';
        $upload_path = $upload_dir . $image_name;

        // ensure directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!move_uploaded_file($_FILES['recipe_image']['tmp_name'], $upload_path)) {
            $error_message = "Failed to upload image. Please try again.";
            $image_name = null;
        }

    } else {
        $error_message = "Invalid image type. Please upload a JPG, PNG, or GIF file.";
    }
}

        
        //Insert recipe into database if no errors
        if (empty($error_message)) {
            $conn = getDBConnection();
            
            
            $conn->begin_transaction();
            
            try {
                //Insert recipe
                $stmt = $conn->prepare("INSERT INTO recipes (user_id, name, country, type, calories, budget, image_name, instructions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssidss", $user_id, $name, $country, $type, $calories, $budget, $image_name, $instructions);
                $stmt->execute();
                
               
                $recipe_id = $stmt->insert_id;
                $stmt->close();
                
                //Inserting ingredients
                $stmt = $conn->prepare("INSERT INTO ingredients (recipe_id, ingredient_name) VALUES (?, ?)");
                foreach ($ingredients as $ingredient) {
                    $stmt->bind_param("is", $recipe_id, $ingredient);
                    $stmt->execute();
                }
                $stmt->close();
                
                //Commit transaction
                $conn->commit();
                
                $success_message = "Recipe added successfully!";
                header("Location: user_add_recipe.php?success=1");
                exit();
                
            } catch (Exception $e) {
                //Rollback transaction on error
                $conn->rollback();
                $error_message = "Error adding recipe: " . $e->getMessage();
            }
            
            $conn->close();
        }
    }
}

//check the success message
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Recipe added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Recipe - Meal-Maker</title>
    <link href="../styling/addrecipestyling.css" rel="stylesheet" type="text/css">
</head>
<body>

    <div class="container">
        <a href="browse_recipes.php" class="back-link">← Back to Browse Recipes</a>
        
        <h1>Add New Recipe</h1>
        
        <?php if ($error_message): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="name">Recipe Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required placeholder="e.g., Spaghetti Carbonara">
            </div>
            
            <div class="two-column">
                <div class="form-group">
                    <label for="country">Country of Origin <span class="required">*</span></label>
                    <select id="country" name="country" required>
                        <option value="">-- Select Country --</option>
                        <option value="Italy">Italy</option>
                        <option value="Mexico">Mexico</option>
                        <option value="United States">United States</option>
                        <option value="China">China</option>
                        <option value="India">India</option>
                        <option value="France">France</option>
                        <option value="Japan">Japan</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Greece">Greece</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="type">Recipe Type <span class="required">*</span></label>
                    <select id="type" name="type" required>
                        <option value="">-- Select Type --</option>
                        <option value="savory">Savory</option>
                        <option value="sweet">Sweet</option>
                    </select>
                </div>
            </div>
            
            <div class="two-column">
                <div class="form-group">
                    <label for="calories">Estimated Calories (kcal)</label>
                    <input type="number" id="calories" name="calories" min="0" placeholder="e.g., 650">
                </div>
                
                <div class="form-group">
                    <label for="budget">Budget ($)</label>
                    <input type="number" id="budget" name="budget" min="0" step="0.01" placeholder="e.g., 15.50">
                </div>
            </div>
            
            <div class="form-group">
                <label>Ingredients <span class="required">*</span></label>
                <div id="ingredients-container">
                    <div class="ingredient-row">
                        <input type="text" name="ingredients[]" placeholder="e.g., Spaghetti" required class="ingredient-input">
                    </div>
                </div>
                <button type="button" class="add-ingredient-btn" onclick="addIngredient()">+ Add Another Ingredient</button>
            </div>
            
            <div class="form-group">
                <label for="recipe_image">Recipe Image (Optional)</label>
                <input type="file" id="recipe_image" name="recipe_image" accept="image/jpeg,image/jpg,image/png,image/gif">
                <div class="file-info">Accepted formats: JPG, PNG, GIF</div>
            </div>
            
            <div class="form-group">
                <label for="instructions">Instructions <span class="required">*</span></label>
                <textarea id="instructions" name="instructions" required placeholder="Enter step-by-step instructions for your recipe..."></textarea>
            </div>
            
            <button type="submit">Upload Recipe</button>
            
        </form>
    </div>

    <script>
        function addIngredient() {
            const container = document.getElementById('ingredients-container');
            const newRow = document.createElement('div');
            newRow.className = 'ingredient-row';
            newRow.innerHTML = `
                <input type="text" name="ingredients[]" placeholder="e.g., Salt" required>
                <button type="button" class="remove-ingredient-btn" onclick="removeIngredient(this)">Remove</button>
            `;
            container.appendChild(newRow);
        }
        
        function removeIngredient(button) {
            const row = button.parentElement;
            row.remove();
        }

        const numberRegex = /^\d+(\.\d{1,2})?$/;//matches integers or decimals

    if (!numberRegex.test(budgetInput)) {
    alert("Please enter a valid budget amount.");
    event.preventDefault();
}
        
    </script>

    

</body>
</html>
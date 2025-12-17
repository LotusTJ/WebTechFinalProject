<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../settings/config.php';

$recipe = null;
$ingredients = [];
$reviews = [];
$average_rating = 0;
$total_reviews = 0;
$error_message = "";
$success_message = "";
$review_error = "";

// Function to load blacklist words
function getBlacklistWords() {
    $blacklist_file = '../settings/blacklistwords.txt';
    if (file_exists($blacklist_file)) {
        $words = file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_map('trim', array_map('strtolower', $words));
    }
    return [];
}

// Function to check for bad words
function containsBadWords($text, $blacklist) {
    $text_lower = strtolower($text);
    foreach ($blacklist as $bad_word) {
        if (stripos($text_lower, $bad_word) !== false) {
            return true;
        }
    }
    return false;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $review_error = "You must be logged in to submit a review.";
    } else {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        $recipe_id = (int)$_POST['recipe_id'];
        $user_id = $_SESSION['user_id'];
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            $review_error = "Please select a rating between 1 and 5 stars.";
        } elseif (empty($comment)) {
            $review_error = "Please write a comment.";
        } else {
            // Check for bad words
            $blacklist = getBlacklistWords();
            if (containsBadWords($comment, $blacklist)) {
                $review_error = "Your comment contains inappropriate language. It's food, let's keep things tasteful (pun intended)!";
            } else {
                // Check if user already reviewed this recipe
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT rating_id FROM ratings WHERE user_id = ? AND recipe_id = ?");
                $stmt->bind_param("ii", $user_id, $recipe_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $review_error = "You have already reviewed this recipe.";
                } else {
                    // Insert review
                    $stmt = $conn->prepare("INSERT INTO ratings (user_id, recipe_id, rating, comment) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiis", $user_id, $recipe_id, $rating, $comment);
                    
                    if ($stmt->execute()) {
                        $success_message = "Review submitted successfully!";
                        header("Location: recipe_details.php?id=" . $recipe_id . "&success=1");
                        exit();
                    } else {
                        $review_error = "Error submitting review. Please try again.";
                    }
                }
                $stmt->close();
                $conn->close();
            }
        }
    }
}

// Check for success message
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Review submitted successfully!";
}

//Check if recipe ID is provided in URL, to then join the database and get the recipe details
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
        
        // Get average rating and total reviews
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM ratings WHERE recipe_id = ?");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rating_data = $result->fetch_assoc();
        $average_rating = round($rating_data['avg_rating'], 1);
        $total_reviews = $rating_data['total'];
        $stmt->close();
        
        // Get all reviews for this recipe
        $stmt = $conn->prepare("SELECT r.*, u.username FROM ratings r JOIN users u ON r.user_id = u.user_id WHERE r.recipe_id = ? ORDER BY r.created_at DESC");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    $conn->close();
} else {
    $error_message = "No recipe selected.";
}

// Function to display stars
function displayStars($rating) {
    $output = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<i class="fa-solid fa-star"></i>';
    }
    if ($half_star) {
        $output .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<i class="fa-regular fa-star"></i>';
    }
    return $output;
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
    
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 24px;
        }
        .rating-stars-small {
            color: #ffc107;
            font-size: 18px;
        }
        .average-rating-box {
            background: #fff9e6;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid #ffc107;
        }
        .review-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .review-username {
            font-weight: bold;
            color: #333;
        }
        .review-date {
            color: #666;
            font-size: 14px;
        }
        .review-comment {
            color: #444;
            line-height: 1.6;
        }
        .star-rating-input {
            font-size: 30px;
            direction: rtl;
            display: inline-block;
        }
        .star-rating-input input {
            display: none;
        }
        .star-rating-input label {
            color: #ddd;
            cursor: pointer;
            padding: 0 5px;
        }
        .star-rating-input label:hover,
        .star-rating-input label:hover ~ label,
        .star-rating-input input:checked ~ label {
            color: #ffc107;
        }
    </style>
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
                    <span class="meta-item"><i class="fa-solid fa-location-dot"></i> Country of Origin: <?= htmlspecialchars($recipe['country']) ?></span>
                    <span class="meta-item"><i class="fa-solid fa-utensils"></i> Type: <?= htmlspecialchars(ucfirst($recipe['type'])) ?></span>
                    <span class="meta-item"><i class="fa-solid fa-fire"></i> Estimated Calories: <?= htmlspecialchars($recipe['calories']) ?> kcal</span>
                    <span class="meta-item"><i class="fa-solid fa-dollar-sign"></i> Estimated Budget: $<?= number_format($recipe['budget'], 2) ?></span>
                    <span class="meta-item"><i class="fa-solid fa-calendar"></i> Date Created: <?= date('M j, Y', strtotime($recipe['created_at'])) ?></span>
                </div>
            </div>

            <!-- Average Rating Display -->
            <?php if ($total_reviews > 0): ?>
                <div class="average-rating-box">
                    <h3 style="margin: 0 0 10px 0;">Average Rating</h3>
                    <div class="rating-stars">
                        <?= displayStars($average_rating) ?>
                    </div>
                    <p style="margin: 10px 0 0 0; font-size: 18px;">
                        <strong><?= $average_rating ?></strong> out of 5 (<?= $total_reviews ?> review<?= $total_reviews != 1 ? 's' : '' ?>)
                    </p>
                </div>
            <?php endif; ?>

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

                <!-- Reviews Section -->
                <div class="reviews-section" style="margin-top: 40px;">
                    <h2 style="color: #ff6347; margin-bottom: 25px;">
                        <i class="fa-solid fa-comments"></i> Reviews (<?= $total_reviews ?>)
                    </h2>
                    
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div>
                                        <span class="review-username">
                                            <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($review['username']) ?>
                                        </span>
                                        <div class="rating-stars-small" style="display: inline-block; margin-left: 10px;">
                                            <?= displayStars($review['rating']) ?>
                                        </div>
                                    </div>
                                    <span class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No reviews yet. Be the first to review this recipe!</p>
                    <?php endif; ?>
                </div>

                <!-- Add Review Form -->
                <div class="add-review-section" style="margin-top: 40px; padding: 30px; background: #f9f9f9; border-radius: 10px;">
                    <h3 style="color: #ff6347; margin-bottom: 20px;">
                        <i class="fa-solid fa-pen"></i> Leave a Review
                    </h3>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($review_error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($review_error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="recipe_id" value="<?= $recipe['recipe_id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Your Rating:</label>
                                <div class="star-rating-input">
                                    <input type="radio" name="rating" value="5" id="star5" required>
                                    <label for="star5"><i class="fa-solid fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4"><i class="fa-solid fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3"><i class="fa-solid fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2"><i class="fa-solid fa-star"></i></label>
                                    
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1"><i class="fa-solid fa-star"></i></label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comment" class="form-label fw-bold">Your Comment:</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required placeholder="Share your thoughts about this recipe..."></textarea>
                            </div>
                            
                            <button type="submit" name="submit_review" class="btn btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> Submit Review
                            </button>
                        </form>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle"></i> 
                            Please <a href="login_page.php">log in</a> to leave a review.
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
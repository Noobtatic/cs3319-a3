<?php
$pageTitle = 'Ratings';
include 'includes/header.php';

$conn = getConnection();
$error = '';
$success = '';

// Handle form submission (add or update rating)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id']);
    $contentId = intval($_POST['content_id']);
    $rating = intval($_POST['rating']);
    $ratingDate = sanitize($conn, $_POST['rating_date']);
    
    // Check if rating already exists for this user-content pair
    $checkSql = "SELECT rate_id FROM rates WHERE user_id = ? AND content_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $userId, $contentId);
    $checkStmt->execute();
    $existingRating = $checkStmt->get_result()->fetch_assoc();
    
    if ($existingRating) {
        // Update existing rating
        $updateSql = "UPDATE rates SET rating = ?, rating_date = ? WHERE rate_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("isi", $rating, $ratingDate, $existingRating['rate_id']);
        
        if ($updateStmt->execute()) {
            $success = "Rating updated successfully!";
        } else {
            $error = "Error updating rating: " . $conn->error;
        }
    } else {
        // Insert new rating
        $rateId = intval($_POST['rate_id']);
        
        // Check if rate_id exists
        $checkIdSql = "SELECT rate_id FROM rates WHERE rate_id = ?";
        $checkIdStmt = $conn->prepare($checkIdSql);
        $checkIdStmt->bind_param("i", $rateId);
        $checkIdStmt->execute();
        
        if ($checkIdStmt->get_result()->num_rows > 0) {
            $error = "Rate ID $rateId already exists.";
        } else {
            $insertSql = "INSERT INTO rates (rate_id, user_id, content_id, rating, rating_date) VALUES (?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iiiis", $rateId, $userId, $contentId, $rating, $ratingDate);
            
            if ($insertStmt->execute()) {
                $success = "Rating added successfully!";
            } else {
                $error = "Error adding rating: " . $conn->error;
            }
        }
    }
}

// Get all users
$usersSql = "SELECT user_id, name FROM users ORDER BY name";
$usersResult = $conn->query($usersSql);

// Get all content
$contentSql = "SELECT content_id, title FROM content ORDER BY title";
$contentResult = $conn->query($contentSql);

// Get all ratings with details
$ratingsSql = "SELECT r.*, u.name as user_name, c.title as content_title
               FROM rates r
               JOIN users u ON r.user_id = u.user_id
               JOIN content c ON r.content_id = c.content_id
               ORDER BY r.rating_date DESC";
$ratingsResult = $conn->query($ratingsSql);

// Get next rate_id
$maxIdSql = "SELECT MAX(rate_id) as max_id FROM rates";
$suggestedId = ($conn->query($maxIdSql)->fetch_assoc()['max_id'] ?? 0) + 1;
?>

<div class="grid-2">
    <!-- Add/Update Rating Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">⭐ Add Rating</h2>
        </div>
        
        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <strong>Note:</strong> A user can only rate each content once. If a rating already exists, it will be updated.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="rate_id">Rate ID *</label>
                <input type="number" id="rate_id" name="rate_id" 
                       value="<?php echo $suggestedId; ?>" required min="1">
                <small class="text-muted">Only used for new ratings</small>
            </div>
            
            <div class="form-group">
                <label for="user_id">User *</label>
                <select id="user_id" name="user_id" required>
                    <option value="">Select User</option>
                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="content_id">Content *</label>
                <select id="content_id" name="content_id" required>
                    <option value="">Select Content</option>
                    <?php while ($content = $contentResult->fetch_assoc()): ?>
                        <option value="<?php echo $content['content_id']; ?>">
                            <?php echo htmlspecialchars($content['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="rating">Rating (1-5) *</label>
                <select id="rating" name="rating" required>
                    <option value="">Select Rating</option>
                    <option value="1">★ (1)</option>
                    <option value="2">★★ (2)</option>
                    <option value="3">★★★ (3)</option>
                    <option value="4">★★★★ (4)</option>
                    <option value="5">★★★★★ (5)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="rating_date">Rating Date *</label>
                <input type="date" id="rating_date" name="rating_date" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Add/Update Rating</button>
        </form>
    </div>
    
    <!-- Ratings List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 All Ratings</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Content</th>
                        <th>Rating</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ratingsResult && $ratingsResult->num_rows > 0): ?>
                        <?php while ($rate = $ratingsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $rate['rate_id']; ?></td>
                                <td><?php echo htmlspecialchars($rate['user_name']); ?></td>
                                <td>
                                    <a href="content_view.php?id=<?php echo $rate['content_id']; ?>">
                                        <?php echo htmlspecialchars($rate['content_title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="stars">
                                        <?php echo str_repeat('★', $rate['rating']); ?>
                                    </span>
                                    (<?php echo $rate['rating']; ?>)
                                </td>
                                <td><?php echo $rate['rating_date']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No ratings yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
$pageTitle = 'Add Content';
include 'includes/header.php';

$conn = getConnection();
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentId = intval($_POST['content_id']);
    $title = sanitize($conn, $_POST['title']);
    $releaseYear = !empty($_POST['release_year']) ? intval($_POST['release_year']) : null;
    $ageRating = sanitize($conn, $_POST['age_rating']);
    $type = sanitize($conn, $_POST['type']);
    $durationMin = ($type === 'movie' && !empty($_POST['duration_min'])) ? intval($_POST['duration_min']) : null;
    
    // Validate content_id is unique
    $checkSql = "SELECT content_id FROM content WHERE content_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $contentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = "Content ID $contentId already exists. Please use a different ID.";
    } elseif (empty($title)) {
        $error = "Title is required.";
    } elseif ($contentId <= 0) {
        $error = "Please enter a valid Content ID.";
    } else {
        // Insert content
        $sql = "INSERT INTO content (content_id, title, release_year, age_rating, type, duration_min) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isissi", $contentId, $title, $releaseYear, $ageRating, $type, $durationMin);
        
        if ($stmt->execute()) {
            if ($type === 'series') {
                redirect("episode_add.php?content_id=$contentId", "Series '$title' added successfully! Now add episodes.", 'success');
            } else {
                redirect('index.php', "Movie '$title' added successfully!", 'success');
            }
        } else {
            $error = "Error adding content: " . $conn->error;
        }
    }
}

// Get next available content_id suggestion
$maxIdSql = "SELECT MAX(content_id) as max_id FROM content";
$maxIdResult = $conn->query($maxIdSql);
$suggestedId = 1;
if ($row = $maxIdResult->fetch_assoc()) {
    $suggestedId = ($row['max_id'] ?? 0) + 1;
}
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">➕ Add New Content</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>
    
    <?php if ($error): ?>
        <?php echo showError($error); ?>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="grid-2">
            <div class="form-group">
                <label for="content_id">Content ID *</label>
                <input type="number" id="content_id" name="content_id" 
                       value="<?php echo isset($_POST['content_id']) ? $_POST['content_id'] : $suggestedId; ?>" 
                       required min="1">
                <small class="text-muted">Suggested next ID: <?php echo $suggestedId; ?></small>
            </div>
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" 
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="content_type">Type *</label>
                <select id="content_type" name="type" required onchange="toggleContentType()">
                    <option value="movie" <?php echo (isset($_POST['type']) && $_POST['type'] === 'movie') ? 'selected' : ''; ?>>Movie</option>
                    <option value="series" <?php echo (isset($_POST['type']) && $_POST['type'] === 'series') ? 'selected' : ''; ?>>TV Series</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="release_year">Release Year</label>
                <input type="number" id="release_year" name="release_year" 
                       value="<?php echo isset($_POST['release_year']) ? $_POST['release_year'] : ''; ?>" 
                       min="1900" max="<?php echo date('Y') + 5; ?>">
            </div>
            
            <div class="form-group">
                <label for="age_rating">Age Rating</label>
                <select id="age_rating" name="age_rating">
                    <option value="">Select Rating</option>
                    <option value="G" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'G') ? 'selected' : ''; ?>>G</option>
                    <option value="PG" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'PG') ? 'selected' : ''; ?>>PG</option>
                    <option value="PG-13" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'PG-13') ? 'selected' : ''; ?>>PG-13</option>
                    <option value="R" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'R') ? 'selected' : ''; ?>>R</option>
                    <option value="TV-Y" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'TV-Y') ? 'selected' : ''; ?>>TV-Y</option>
                    <option value="TV-G" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'TV-G') ? 'selected' : ''; ?>>TV-G</option>
                    <option value="TV-PG" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'TV-PG') ? 'selected' : ''; ?>>TV-PG</option>
                    <option value="TV-14" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'TV-14') ? 'selected' : ''; ?>>TV-14</option>
                    <option value="TV-MA" <?php echo (isset($_POST['age_rating']) && $_POST['age_rating'] === 'TV-MA') ? 'selected' : ''; ?>>TV-MA</option>
                </select>
            </div>
            
            <div class="form-group" id="duration_field">
                <label for="duration_min">Duration (minutes) *</label>
                <input type="number" id="duration_min" name="duration_min" 
                       value="<?php echo isset($_POST['duration_min']) ? $_POST['duration_min'] : ''; ?>" 
                       min="1">
                <small class="text-muted">Required for movies only</small>
            </div>
        </div>
        
        <div class="mt-1">
            <button type="submit" class="btn btn-primary">Add Content</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    toggleContentType();
});
</script>

<?php include 'includes/footer.php'; ?>

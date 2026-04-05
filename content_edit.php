<?php
$pageTitle = 'Edit Content';
include 'includes/header.php';

$conn = getConnection();
$error = '';

// Get content ID
$contentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contentId <= 0) {
    redirect('index.php', 'Invalid content ID.', 'error');
}

// Fetch current content
$sql = "SELECT * FROM content WHERE content_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $contentId);
$stmt->execute();
$result = $stmt->get_result();
$content = $result->fetch_assoc();

if (!$content) {
    redirect('index.php', 'Content not found.', 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($conn, $_POST['title']);
    $ageRating = sanitize($conn, $_POST['age_rating']);
    
    if (empty($title)) {
        $error = "Title is required.";
    } else {
        // Update content (only title and age_rating as per requirements)
        $updateSql = "UPDATE content SET title = ?, age_rating = ? WHERE content_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ssi", $title, $ageRating, $contentId);
        
        if ($updateStmt->execute()) {
            redirect("content_view.php?id=$contentId", "Content updated successfully!", 'success');
        } else {
            $error = "Error updating content: " . $conn->error;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">✏️ Edit Content</h1>
        <a href="content_view.php?id=<?php echo $contentId; ?>" class="btn btn-secondary">Back to Details</a>
    </div>
    
    <?php if ($error): ?>
        <?php echo showError($error); ?>
    <?php endif; ?>
    
    <div class="alert alert-warning">
        <strong>Note:</strong> Content ID cannot be modified. Only title and age rating can be updated.
    </div>
    
    <form method="POST" action="">
        <div class="grid-2">
            <div class="form-group">
                <label>Content ID</label>
                <input type="text" value="<?php echo $content['content_id']; ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>Type</label>
                <input type="text" value="<?php echo ucfirst($content['type']); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" 
                       value="<?php echo htmlspecialchars($content['title']); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="age_rating">Age Rating</label>
                <select id="age_rating" name="age_rating">
                    <option value="">Select Rating</option>
                    <?php
                    $ratings = ['G', 'PG', 'PG-13', 'R', 'TV-Y', 'TV-G', 'TV-PG', 'TV-14', 'TV-MA', 'Classic'];
                    foreach ($ratings as $rating):
                    ?>
                        <option value="<?php echo $rating; ?>" <?php echo ($content['age_rating'] === $rating) ? 'selected' : ''; ?>>
                            <?php echo $rating; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Release Year</label>
                <input type="text" value="<?php echo $content['release_year'] ?? 'N/A'; ?>" disabled>
                <small class="text-muted">Cannot be modified</small>
            </div>
            
            <?php if ($content['type'] == 'movie'): ?>
            <div class="form-group">
                <label>Duration</label>
                <input type="text" value="<?php echo $content['duration_min'] ? $content['duration_min'] . ' minutes' : 'N/A'; ?>" disabled>
                <small class="text-muted">Cannot be modified</small>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-1">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="content_view.php?id=<?php echo $contentId; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

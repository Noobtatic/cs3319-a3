<?php
$pageTitle = 'Delete Content';
include 'includes/header.php';

$conn = getConnection();

// Get content ID
$contentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contentId <= 0) {
    redirect('index.php', 'Invalid content ID.', 'error');
}

// Fetch content details
$sql = "SELECT c.*, 
        COUNT(DISTINCT w.watch_id) AS watch_count,
        COUNT(DISTINCT r.rate_id) AS rating_count
        FROM content c
        LEFT JOIN watches w ON c.content_id = w.content_id
        LEFT JOIN rates r ON c.content_id = r.content_id
        WHERE c.content_id = ?
        GROUP BY c.content_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $contentId);
$stmt->execute();
$result = $stmt->get_result();
$content = $result->fetch_assoc();

if (!$content) {
    redirect('index.php', 'Content not found.', 'error');
}

// Count episodes if series
$episodeCount = 0;
if ($content['type'] == 'series') {
    $epSql = "SELECT COUNT(*) as count FROM episodes WHERE content_id = ?";
    $epStmt = $conn->prepare($epSql);
    $epStmt->bind_param("i", $contentId);
    $epStmt->execute();
    $epResult = $epStmt->get_result();
    $episodeCount = $epResult->fetch_assoc()['count'];
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $deleteSql = "DELETE FROM content WHERE content_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $contentId);
    
    if ($deleteStmt->execute()) {
        redirect('index.php', "Content '{$content['title']}' has been deleted.", 'success');
    } else {
        redirect('index.php', "Error deleting content: " . $conn->error, 'error');
    }
}
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">⚠️ Delete Content</h1>
    </div>
    
    <div class="alert alert-error">
        <strong>Warning:</strong> You are about to permanently delete this content. This action cannot be undone!
    </div>
    
    <div class="content-details mb-1">
        <div class="detail-item">
            <div class="detail-label">Content ID</div>
            <div class="detail-value"><?php echo $content['content_id']; ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Title</div>
            <div class="detail-value"><?php echo htmlspecialchars($content['title']); ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Type</div>
            <div class="detail-value"><?php echo ucfirst($content['type']); ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Release Year</div>
            <div class="detail-value"><?php echo $content['release_year'] ?? 'N/A'; ?></div>
        </div>
    </div>
    
    <div class="alert alert-warning">
        <strong>The following related data will also be deleted:</strong>
        <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
            <li><?php echo $content['watch_count']; ?> viewing events</li>
            <li><?php echo $content['rating_count']; ?> ratings</li>
            <?php if ($content['type'] == 'series'): ?>
                <li><?php echo $episodeCount; ?> episodes</li>
            <?php endif; ?>
        </ul>
    </div>
    
    <form method="POST" action="">
        <p style="margin-bottom: 1rem;">Are you sure you want to delete "<strong><?php echo htmlspecialchars($content['title']); ?></strong>"?</p>
        
        <div class="btn-group">
            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger" 
                    onclick="return confirm('Final confirmation: Delete this content and all related data?')">
                Yes, Delete Permanently
            </button>
            <a href="content_view.php?id=<?php echo $contentId; ?>" class="btn btn-secondary">Cancel</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

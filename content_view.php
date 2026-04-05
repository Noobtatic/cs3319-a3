<?php
$pageTitle = 'Content Details';
include 'includes/header.php';

$conn = getConnection();

// Get content ID
$contentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contentId <= 0) {
    redirect('index.php', 'Invalid content ID.', 'error');
}

// Fetch content details with analytics
$sql = "SELECT c.*, 
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(DISTINCT r.rate_id) AS rating_count,
        COUNT(DISTINCT w.watch_id) AS view_count
        FROM content c
        LEFT JOIN rates r ON c.content_id = r.content_id
        LEFT JOIN watches w ON c.content_id = w.content_id
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

// Fetch episodes if it's a series
$episodes = [];
if ($content['type'] == 'series') {
    $epSql = "SELECT * FROM episodes WHERE content_id = ? ORDER BY season_num, episode_num";
    $epStmt = $conn->prepare($epSql);
    $epStmt->bind_param("i", $contentId);
    $epStmt->execute();
    $epResult = $epStmt->get_result();
    while ($ep = $epResult->fetch_assoc()) {
        $episodes[] = $ep;
    }
}
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">
            <?php echo htmlspecialchars($content['title']); ?>
            <span class="badge badge-<?php echo $content['type']; ?>"><?php echo ucfirst($content['type']); ?></span>
        </h1>
        <div class="btn-group">
            <a href="content_edit.php?id=<?php echo $contentId; ?>" class="btn btn-secondary">Edit</a>
            <a href="content_delete.php?id=<?php echo $contentId; ?>" class="btn btn-danger">Delete</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
    
    <!-- Content Details -->
    <div class="content-details">
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
        <div class="detail-item">
            <div class="detail-label">Age Rating</div>
            <div class="detail-value"><?php echo htmlspecialchars($content['age_rating'] ?? 'N/A'); ?></div>
        </div>
        <?php if ($content['type'] == 'movie'): ?>
        <div class="detail-item">
            <div class="detail-label">Duration</div>
            <div class="detail-value"><?php echo $content['duration_min'] ? $content['duration_min'] . ' minutes' : 'N/A'; ?></div>
        </div>
        <?php endif; ?>
        <div class="detail-item">
            <div class="detail-label">Average Rating</div>
            <div class="detail-value">
                <?php if ($content['rating_count'] > 0): ?>
                    <span class="stars">★</span> <?php echo number_format($content['avg_rating'], 1); ?>/5
                    <span class="text-muted">(<?php echo $content['rating_count']; ?> ratings)</span>
                <?php else: ?>
                    No ratings yet
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Total Views</div>
            <div class="detail-value"><?php echo $content['view_count']; ?></div>
        </div>
    </div>
</div>

<?php if ($content['type'] == 'series'): ?>
<!-- Episodes Section -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📋 Episodes</h2>
        <a href="episode_add.php?content_id=<?php echo $contentId; ?>" class="btn btn-primary btn-sm">+ Add Episode</a>
    </div>
    
    <?php if (count($episodes) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Episode ID</th>
                        <th>Season</th>
                        <th>Episode</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($episodes as $ep): ?>
                        <tr>
                            <td><?php echo $ep['episode_id']; ?></td>
                            <td>Season <?php echo $ep['season_num']; ?></td>
                            <td>Episode <?php echo $ep['episode_num']; ?></td>
                            <td><?php echo $ep['duration'] ? $ep['duration'] . ' min' : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No episodes added yet.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

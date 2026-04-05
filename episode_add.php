<?php
$pageTitle = 'Add Episode';
include 'includes/header.php';

$conn = getConnection();
$error = '';

// Get content ID
$contentId = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0;

if ($contentId <= 0) {
    redirect('index.php', 'Invalid content ID.', 'error');
}

// Verify content exists and is a series
$sql = "SELECT * FROM content WHERE content_id = ? AND type = 'series'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $contentId);
$stmt->execute();
$result = $stmt->get_result();
$series = $result->fetch_assoc();

if (!$series) {
    redirect('index.php', 'Series not found or content is not a TV series.', 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $episodeId = intval($_POST['episode_id']);
    $seasonNum = intval($_POST['season_num']);
    $episodeNum = intval($_POST['episode_num']);
    $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : null;
    
    // Check if episode_id already exists
    $checkSql = "SELECT episode_id FROM episodes WHERE episode_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $episodeId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = "Episode ID $episodeId already exists. Please use a different ID.";
    } elseif ($episodeId <= 0) {
        $error = "Please enter a valid Episode ID.";
    } elseif ($seasonNum <= 0 || $episodeNum <= 0) {
        $error = "Season and Episode numbers must be positive.";
    } else {
        $insertSql = "INSERT INTO episodes (episode_id, content_id, season_num, episode_num, duration) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iiiii", $episodeId, $contentId, $seasonNum, $episodeNum, $duration);
        
        if ($insertStmt->execute()) {
            // Check if user wants to add another
            if (isset($_POST['add_another'])) {
                redirect("episode_add.php?content_id=$contentId", "Episode S{$seasonNum}E{$episodeNum} added! Add another episode.", 'success');
            } else {
                redirect("content_view.php?id=$contentId", "Episode added successfully!", 'success');
            }
        } else {
            $error = "Error adding episode: " . $conn->error;
        }
    }
}

// Get next available episode_id
$maxIdSql = "SELECT MAX(episode_id) as max_id FROM episodes";
$maxIdResult = $conn->query($maxIdSql);
$suggestedId = 1;
if ($row = $maxIdResult->fetch_assoc()) {
    $suggestedId = ($row['max_id'] ?? 0) + 1;
}

// Get last episode info for this series
$lastEpSql = "SELECT MAX(season_num) as last_season, MAX(episode_num) as last_ep FROM episodes WHERE content_id = ?";
$lastEpStmt = $conn->prepare($lastEpSql);
$lastEpStmt->bind_param("i", $contentId);
$lastEpStmt->execute();
$lastEp = $lastEpStmt->get_result()->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">➕ Add Episode to "<?php echo htmlspecialchars($series['title']); ?>"</h1>
        <a href="content_view.php?id=<?php echo $contentId; ?>" class="btn btn-secondary">Back to Series</a>
    </div>
    
    <?php if ($error): ?>
        <?php echo showError($error); ?>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="grid-2">
            <div class="form-group">
                <label for="episode_id">Episode ID *</label>
                <input type="number" id="episode_id" name="episode_id" 
                       value="<?php echo isset($_POST['episode_id']) ? $_POST['episode_id'] : $suggestedId; ?>" 
                       required min="1">
                <small class="text-muted">Suggested next ID: <?php echo $suggestedId; ?></small>
            </div>
            
            <div class="form-group">
                <label for="season_num">Season Number *</label>
                <input type="number" id="season_num" name="season_num" 
                       value="<?php echo isset($_POST['season_num']) ? $_POST['season_num'] : ($lastEp['last_season'] ?? 1); ?>" 
                       required min="1">
            </div>
            
            <div class="form-group">
                <label for="episode_num">Episode Number *</label>
                <input type="number" id="episode_num" name="episode_num" 
                       value="<?php echo isset($_POST['episode_num']) ? $_POST['episode_num'] : (($lastEp['last_ep'] ?? 0) + 1); ?>" 
                       required min="1">
            </div>
            
            <div class="form-group">
                <label for="duration">Duration (minutes)</label>
                <input type="number" id="duration" name="duration" 
                       value="<?php echo isset($_POST['duration']) ? $_POST['duration'] : ''; ?>" 
                       min="1">
            </div>
        </div>
        
        <div class="mt-1">
            <button type="submit" class="btn btn-primary">Add Episode</button>
            <button type="submit" name="add_another" value="1" class="btn btn-secondary">Add & Create Another</button>
            <a href="content_view.php?id=<?php echo $contentId; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

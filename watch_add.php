<?php
$pageTitle = 'Add Viewing Event';
include 'includes/header.php';

$conn = getConnection();
$error = '';

// Get user ID
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Get user info
$userSql = "SELECT * FROM users WHERE user_id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    redirect('users.php', 'User not found.', 'error');
}

// Get user's profiles
$profilesSql = "SELECT * FROM profile WHERE user_id = ?";
$profilesStmt = $conn->prepare($profilesSql);
$profilesStmt->bind_param("i", $userId);
$profilesStmt->execute();
$profiles = $profilesStmt->get_result();

// Get all content
$contentSql = "SELECT content_id, title, type FROM content ORDER BY title";
$contentResult = $conn->query($contentSql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $watchId = intval($_POST['watch_id']);
    $profileId = intval($_POST['profile_id']);
    $contentId = intval($_POST['content_id']);
    $watchDate = sanitize($conn, $_POST['watch_date']);
    $watchDuration = intval($_POST['watch_duration']);
    $completed = isset($_POST['completed']) ? 1 : 0;
    
    // Check if watch_id already exists
    $checkSql = "SELECT watch_id FROM watches WHERE watch_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $watchId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        $error = "Watch ID $watchId already exists.";
    } elseif ($watchId <= 0) {
        $error = "Please enter a valid Watch ID.";
    } else {
        $insertSql = "INSERT INTO watches (watch_id, profile_id, content_id, watch_date, watch_duration, completed) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iiisii", $watchId, $profileId, $contentId, $watchDate, $watchDuration, $completed);
        
        if ($insertStmt->execute()) {
            redirect("users.php?user_id=$userId", "Viewing event added successfully!", 'success');
        } else {
            $error = "Error adding viewing event: " . $conn->error;
        }
    }
}

// Get next watch_id
$maxIdSql = "SELECT MAX(watch_id) as max_id FROM watches";
$maxIdResult = $conn->query($maxIdSql);
$suggestedId = ($maxIdResult->fetch_assoc()['max_id'] ?? 0) + 1;
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">➕ Add Viewing Event for <?php echo htmlspecialchars($user['name']); ?></h1>
        <a href="users.php?user_id=<?php echo $userId; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <?php if ($error): ?>
        <?php echo showError($error); ?>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="grid-2">
            <div class="form-group">
                <label for="watch_id">Watch ID *</label>
                <input type="number" id="watch_id" name="watch_id" 
                       value="<?php echo $suggestedId; ?>" required min="1">
            </div>
            
            <div class="form-group">
                <label for="profile_id">Profile *</label>
                <select id="profile_id" name="profile_id" required>
                    <option value="">Select Profile</option>
                    <?php while ($profile = $profiles->fetch_assoc()): ?>
                        <option value="<?php echo $profile['profile_id']; ?>">
                            <?php echo htmlspecialchars($profile['profile_name']); ?>
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
                            <?php echo htmlspecialchars($content['title']); ?> (<?php echo ucfirst($content['type']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="watch_date">Watch Date *</label>
                <input type="date" id="watch_date" name="watch_date" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="watch_duration">Watch Duration (minutes) *</label>
                <input type="number" id="watch_duration" name="watch_duration" 
                       required min="1">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="completed" value="1"> Completed
                </label>
            </div>
        </div>
        
        <div class="mt-1">
            <button type="submit" class="btn btn-primary">Add Viewing Event</button>
            <a href="users.php?user_id=<?php echo $userId; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

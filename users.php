<?php
$pageTitle = 'Users & Viewing History';
include 'includes/header.php';

$conn = getConnection();

// Get all users
$usersSql = "SELECT u.*, p.plan_name, COUNT(DISTINCT pr.profile_id) as profile_count
             FROM users u 
             LEFT JOIN subscription_plans p ON u.plan_id = p.plan_id
             LEFT JOIN profile pr ON u.user_id = pr.user_id
             GROUP BY u.user_id
             ORDER BY u.name";
$usersResult = $conn->query($usersSql);

// Get selected user
$selectedUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Date filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch viewing history if user selected
$watchHistory = [];
if ($selectedUserId > 0) {
    $historySql = "SELECT w.*, c.title, c.type, p.profile_name
                   FROM watches w
                   JOIN profile p ON w.profile_id = p.profile_id
                   JOIN content c ON w.content_id = c.content_id
                   WHERE p.user_id = ?";
    
    $params = [$selectedUserId];
    $types = "i";
    
    if (!empty($startDate)) {
        $historySql .= " AND w.watch_date >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if (!empty($endDate)) {
        $historySql .= " AND w.watch_date <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    $historySql .= " ORDER BY w.watch_date DESC";
    
    $historyStmt = $conn->prepare($historySql);
    $historyStmt->bind_param($types, ...$params);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    
    while ($row = $historyResult->fetch_assoc()) {
        $watchHistory[] = $row;
    }
}
?>

<div class="grid-2">
    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">👥 Users</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                        <tr class="<?php echo ($selectedUserId == $user['user_id']) ? 'active' : ''; ?>">
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['plan_name'] ?? 'None'); ?></td>
                            <td>
                                <a href="?user_id=<?php echo $user['user_id']; ?>" class="btn btn-secondary btn-sm">
                                    View History
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Viewing History -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📺 Viewing History</h2>
            <?php if ($selectedUserId > 0): ?>
                <a href="watch_add.php?user_id=<?php echo $selectedUserId; ?>" class="btn btn-primary btn-sm">+ Add Watch Event</a>
            <?php endif; ?>
        </div>
        
        <?php if ($selectedUserId > 0): ?>
            <!-- Date Filter -->
            <form method="GET" class="form-inline mb-1">
                <input type="hidden" name="user_id" value="<?php echo $selectedUserId; ?>">
                <div class="form-group">
                    <label for="start_date">From:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">To:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <a href="?user_id=<?php echo $selectedUserId; ?>" class="btn btn-secondary btn-sm">Clear</a>
            </form>
            
            <?php if (count($watchHistory) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Content</th>
                                <th>Profile</th>
                                <th>Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($watchHistory as $watch): ?>
                                <tr>
                                    <td>
                                        <a href="content_view.php?id=<?php echo $watch['content_id']; ?>">
                                            <?php echo htmlspecialchars($watch['title']); ?>
                                        </a>
                                        <span class="badge badge-<?php echo $watch['type']; ?>" style="font-size: 0.7rem;">
                                            <?php echo ucfirst($watch['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($watch['profile_name']); ?></td>
                                    <td><?php echo $watch['watch_date']; ?></td>
                                    <td><?php echo $watch['watch_duration']; ?> min</td>
                                    <td>
                                        <span class="badge badge-<?php echo $watch['completed'] ? 'completed' : 'incomplete'; ?>">
                                            <?php echo $watch['completed'] ? 'Completed' : 'In Progress'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No viewing history found for the selected criteria.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted">Select a user to view their watching history.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

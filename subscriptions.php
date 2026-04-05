<?php
$pageTitle = 'Subscription Plans';
include 'includes/header.php';

$conn = getConnection();
$error = '';
$success = '';

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $planId = intval($_POST['plan_id']);
    $newPrice = floatval($_POST['new_price']);
    
    if ($newPrice <= 0) {
        $error = "Price must be greater than 0.";
    } else {
        $updateSql = "UPDATE subscription_plans SET monthly_price = ? WHERE plan_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("di", $newPrice, $planId);
        
        if ($updateStmt->execute()) {
            $success = "Plan price updated successfully!";
        } else {
            $error = "Error updating price: " . $conn->error;
        }
    }
}

// Handle plan deletion attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plan'])) {
    $planId = intval($_POST['plan_id']);
    
    // Check if there are users subscribed
    $checkSql = "SELECT COUNT(*) as count FROM users WHERE plan_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $planId);
    $checkStmt->execute();
    $userCount = $checkStmt->get_result()->fetch_assoc()['count'];
    
    if ($userCount > 0) {
        $error = "Cannot delete this plan: $userCount user(s) are still subscribed to it.";
    } else {
        $deleteSql = "DELETE FROM subscription_plans WHERE plan_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $planId);
        
        if ($deleteStmt->execute()) {
            $success = "Plan deleted successfully!";
        } else {
            $error = "Error deleting plan: " . $conn->error;
        }
    }
}

// Get all plans with subscriber counts
$plansSql = "SELECT p.*, COUNT(u.user_id) as subscriber_count
             FROM subscription_plans p
             LEFT JOIN users u ON p.plan_id = u.plan_id
             GROUP BY p.plan_id
             ORDER BY p.monthly_price";
$plansResult = $conn->query($plansSql);

// Calculate total subscribers and revenue
$statsSql = "SELECT 
             COUNT(DISTINCT u.user_id) as total_subscribers,
             SUM(p.monthly_price) as total_monthly_revenue
             FROM users u
             JOIN subscription_plans p ON u.plan_id = p.plan_id";
$stats = $conn->query($statsSql)->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">💳 Subscription Plans</h1>
    </div>
    
    <?php if ($error): ?>
        <?php echo showError($error); ?>
    <?php endif; ?>
    <?php if ($success): ?>
        <?php echo showSuccess($success); ?>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total_subscribers'] ?? 0; ?></div>
            <div class="stat-label">Total Subscribers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?php echo number_format($stats['total_monthly_revenue'] ?? 0, 2); ?></div>
            <div class="stat-label">Monthly Revenue</div>
        </div>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Plan ID</th>
                    <th>Plan Name</th>
                    <th>Monthly Price</th>
                    <th>Subscribers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($plan = $plansResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $plan['plan_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($plan['plan_name']); ?></strong></td>
                        <td>$<?php echo number_format($plan['monthly_price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $plan['subscriber_count'] > 0 ? 'badge-completed' : 'badge-incomplete'; ?>">
                                <?php echo $plan['subscriber_count']; ?> users
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-secondary btn-sm" 
                                    onclick="openPriceModal(<?php echo $plan['plan_id']; ?>, '<?php echo htmlspecialchars($plan['plan_name']); ?>', <?php echo $plan['monthly_price']; ?>)">
                                Update Price
                            </button>
                            <?php if ($plan['subscriber_count'] == 0): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Delete this plan?')">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['plan_id']; ?>">
                                    <button type="submit" name="delete_plan" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Price Update Modal -->
<div id="priceModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Update Plan Price</h3>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <p>Updating price for: <strong id="modalPlanName"></strong></p>
                <input type="hidden" name="plan_id" id="modalPlanId">
                <div class="form-group">
                    <label for="new_price">New Monthly Price ($)</label>
                    <input type="number" id="new_price" name="new_price" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('priceModal')">Cancel</button>
                <button type="submit" name="update_price" class="btn btn-primary">Update Price</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPriceModal(planId, planName, currentPrice) {
    document.getElementById('modalPlanId').value = planId;
    document.getElementById('modalPlanName').textContent = planName;
    document.getElementById('new_price').value = currentPrice;
    document.getElementById('priceModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('priceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('priceModal');
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<?php
$pageTitle = 'Content Browsing';
include 'includes/header.php';

$conn = getConnection();

// Get sorting parameters
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sorting parameters
$validSortColumns = ['title', 'release_year'];
$validOrders = ['ASC', 'DESC'];

if (!in_array($sortBy, $validSortColumns)) $sortBy = 'title';
if (!in_array($sortOrder, $validOrders)) $sortOrder = 'ASC';

// Query all content with average rating and view count
$sql = "SELECT c.*, 
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(DISTINCT r.rate_id) AS rating_count,
        COUNT(DISTINCT w.watch_id) AS view_count
        FROM content c
        LEFT JOIN rates r ON c.content_id = r.content_id
        LEFT JOIN watches w ON c.content_id = w.content_id
        GROUP BY c.content_id
        ORDER BY $sortBy $sortOrder";

$result = $conn->query($sql);
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">📺 Content Library</h1>
        <a href="content_add.php" class="btn btn-primary">+ Add Content</a>
    </div>
    
    <!-- Sorting Controls -->
    <form method="GET" class="form-inline mb-1">
        <div class="form-group">
            <label for="sort">Sort By:</label>
            <select name="sort" id="sort">
                <option value="title" <?php echo $sortBy == 'title' ? 'selected' : ''; ?>>Title</option>
                <option value="release_year" <?php echo $sortBy == 'release_year' ? 'selected' : ''; ?>>Release Year</option>
            </select>
        </div>
        <div class="form-group">
            <label for="order">Order:</label>
            <select name="order" id="order">
                <option value="ASC" <?php echo $sortOrder == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $sortOrder == 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Apply</button>
    </form>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Year</th>
                    <th>Age Rating</th>
                    <th>Duration</th>
                    <th>Avg Rating</th>
                    <th>Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['content_id']; ?></td>
                            <td>
                                <a href="content_view.php?id=<?php echo $row['content_id']; ?>">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $row['type']; ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['release_year']; ?></td>
                            <td><?php echo htmlspecialchars($row['age_rating'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                if ($row['type'] == 'movie' && $row['duration_min']) {
                                    echo $row['duration_min'] . ' min';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($row['rating_count'] > 0): ?>
                                    <span class="stars">★</span> 
                                    <?php echo number_format($row['avg_rating'], 1); ?>
                                    <span class="text-muted">(<?php echo $row['rating_count']; ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">No ratings</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['view_count']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="content_view.php?id=<?php echo $row['content_id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                    <a href="content_edit.php?id=<?php echo $row['content_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <a href="content_delete.php?id=<?php echo $row['content_id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No content found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

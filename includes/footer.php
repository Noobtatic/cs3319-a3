    </main>
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Streaming Platform - CS3319 Assignment 3</p>
    </footer>
    <script src="js/main.js"></script>
</body>
</html>
<?php
// Close database connection if exists
if (isset($conn)) {
    closeConnection($conn);
}
?>

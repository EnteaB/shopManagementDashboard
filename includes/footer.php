        </div> <!-- End of content-wrapper -->
        <footer class="main-footer">
            <div class="footer-container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>About FashShop</h3>
                        <p>Fashion Shop Management System for efficient inventory and user management.</p>
                    </div>
                    <div class="footer-section">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="<?php echo isset($isAdmin) ? '../index.php' : 'index.php'; ?>">Home</a></li>
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="../admin/dashboard.php">Dashboard</a></li>
                            <li><a href="../admin/reports.php">Reports</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>Contact</h3>
                        <p><i class="fas fa-envelope"></i> support@fashshop.com</p>
                        <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> FashShop. All rights reserved.</p>
                </div>
            </div>
        </footer>
        <script>
            // Add any JavaScript code here
            $(document).ready(function() {
                // Footer animations or functionality
            });
        </script>
    </body>
</html>
<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGNIS - Home</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="Assets/logo.png" alt="WebGNIS Logo">
            </div>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="about.php">About</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="account.php">My Account</a></li>
                    <?php if (getCurrentUser()['user_type'] === 'admin'): ?>
                        <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
            <div class="cart-icon">
                <a href="cart.php">
                    <img src="Assets/cart.png" alt="Cart">
                    <span class="cart-count" id="cartCount">0</span>
                </a>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Welcome to WebGNIS</h1>
            <p>Your one-stop solution for geodetic control points and station information</p>
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search for stations...">
                <button onclick="searchStations()">Search</button>
            </div>
        </section>

        <section class="features">
            <h2>Our Services</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <img src="Assets/horizontal.png" alt="Horizontal Control">
                    <h3>Horizontal Control Points</h3>
                    <p>Access information about horizontal control points across the country</p>
                </div>
                <div class="feature-card">
                    <img src="Assets/benchmark.png" alt="Benchmarks">
                    <h3>Benchmarks</h3>
                    <p>Find detailed information about benchmark stations</p>
                </div>
                <div class="feature-card">
                    <img src="Assets/gravity.png" alt="Gravity Stations">
                    <h3>Gravity Stations</h3>
                    <p>Access gravity station data and measurements</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>Email: info@webgnis.gov.ph</p>
                <p>Phone: (02) 123-4567</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> WebGNIS. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Function to update cart count
        function updateCartCount() {
            fetch('cart_api.php?action=get')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cartCount').textContent = data.length;
                })
                .catch(error => console.error('Error:', error));
        }

        // Update cart count on page load
        document.addEventListener('DOMContentLoaded', updateCartCount);

        // Function to search stations
        function searchStations() {
            const searchTerm = document.getElementById('searchInput').value;
            if (searchTerm.trim()) {
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        }

        // Add enter key support for search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStations();
            }
        });
    </script>
</body>
</html> 
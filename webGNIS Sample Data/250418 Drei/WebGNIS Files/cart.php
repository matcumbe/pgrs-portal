<?php
require_once 'auth_check.php';

// Get cart items
$cartItems = getCartItems();
$cartTotal = calculateCartTotal($cartItems);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - WebGNIS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <img src="Assets/logo.png" alt="WebGNIS Logo">
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
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
                <a href="cart.php" class="active">
                    <img src="Assets/cart.png" alt="Cart">
                    <span class="cart-count" id="cartCount"><?php echo count($cartItems); ?></span>
                </a>
            </div>
        </nav>
    </header>

    <main class="cart-container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="index.php" class="btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <table>
                    <thead>
                        <tr>
                            <th>Station ID</th>
                            <th>Station Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['station_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['station_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['station_type']); ?></td>
                                <td>
                                    <?php
                                    switch ($item['station_type']) {
                                        case 'horizontal':
                                            echo '₱' . number_format(HORIZONTAL_STATION_PRICE, 2);
                                            break;
                                        case 'benchmark':
                                            echo '₱' . number_format(BENCHMARK_STATION_PRICE, 2);
                                            break;
                                        case 'gravity':
                                            echo '₱' . number_format(GRAVITY_STATION_PRICE, 2);
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button onclick="removeFromCart(<?php echo $item['cart_id']; ?>)" class="btn-danger">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
                            <td colspan="2"><strong>₱<?php echo number_format($cartTotal, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="cart-actions">
                    <button onclick="clearCart()" class="btn-secondary">Clear Cart</button>
                    <?php if (isLoggedIn()): ?>
                        <a href="checkout.php" class="btn-primary">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="login.php?redirect=cart.php" class="btn-primary">Login to Checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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
        // Function to remove item from cart
        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('cart_api.php?action=remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ cartId: cartId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // Function to clear cart
        function clearCart() {
            if (confirm('Are you sure you want to clear your cart?')) {
                fetch('cart_api.php?action=clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html> 
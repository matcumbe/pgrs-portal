// cart-service.js - Persistent cart functionality for WebGNIS

// Base API URL - adjust as needed
const API_BASE_URL = 'requests_api.php?action=';

// Cart session ID stored in localStorage
const CART_SESSION_KEY = 'webgnis_cart_session';

// Cart class to handle all cart operations
class CartService {
    constructor() {
        this.sessionId = this.getCartSessionId();
        this.items = [];
        this.totalPrice = 0;
        this.isLoading = false;
    }
    
    // Get or initialize cart session ID
    getCartSessionId() {
        let sessionId = localStorage.getItem(CART_SESSION_KEY);
        if (!sessionId && !this.isAuthenticated()) {
            // Only generate a session ID if not authenticated
            // If authenticated, the user_id will be used instead
            sessionId = this.generateSessionId();
            localStorage.setItem(CART_SESSION_KEY, sessionId);
        }
        return sessionId;
    }
    
    // Generate a random session ID
    generateSessionId() {
        return 'cart_' + Math.random().toString(36).substring(2, 15) + 
               Math.random().toString(36).substring(2, 15);
    }
    
    // Check if user is authenticated
    isAuthenticated() {
        return !!localStorage.getItem('auth_token');
    }
    
    // Get authentication headers
    getAuthHeaders() {
        if (this.isAuthenticated()) {
            return {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            };
        }
        return {};
    }
    
    // Load cart from server
    async loadCart() {
        this.isLoading = true;
        
        try {
            let url = API_BASE_URL + 'cart';
            
            // Add session ID if not authenticated
            if (!this.isAuthenticated() && this.sessionId) {
                url += '&session_id=' + this.sessionId;
            }
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.getAuthHeaders()
                }
            });
            
            const result = await response.json();
            
            if (result.status === 200) {
                this.items = result.data.items || [];
                this.totalPrice = result.data.total_price || 0;
                
                // If we got a new session ID (first time), save it
                if (result.data.session_id && !this.isAuthenticated()) {
                    this.sessionId = result.data.session_id;
                    localStorage.setItem(CART_SESSION_KEY, this.sessionId);
                }
                
                // Update UI
                this.updateCartUI();
                return this.items;
            } else {
                console.error('Failed to load cart:', result.message);
            }
        } catch (error) {
            console.error('Error loading cart:', error);
        } finally {
            this.isLoading = false;
        }
        
        return [];
    }
    
    // Add item to cart
    async addToCart(station) {
        if (this.isLoading) return false;
        this.isLoading = true;
        
        try {
            const payload = {
                station_id: station.id,
                station_name: station.name,
                station_type: station.type
            };
            
            // Add session ID if not authenticated
            if (!this.isAuthenticated() && this.sessionId) {
                payload.session_id = this.sessionId;
            }
            
            const response = await fetch(API_BASE_URL + 'cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.getAuthHeaders()
                },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (result.status === 201) {
                // Successfully added, reload cart
                await this.loadCart();
                return true;
            } else if (result.status === 200 && result.message === "Item already in cart") {
                // Item was already in cart
                console.log('Item already in cart');
                return true;
            } else {
                console.error('Failed to add item to cart:', result.message);
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
        } finally {
            this.isLoading = false;
        }
        
        return false;
    }
    
    // Remove item from cart
    async removeFromCart(cartItemId) {
        if (this.isLoading) return false;
        this.isLoading = true;
        
        try {
            let url = API_BASE_URL + 'cart/' + cartItemId;
            
            // Add session ID if not authenticated
            if (!this.isAuthenticated() && this.sessionId) {
                url += '&session_id=' + this.sessionId;
            }
            
            const response = await fetch(url, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });
            
            const result = await response.json();
            
            if (result.status === 200) {
                // Successfully removed, reload cart
                await this.loadCart();
                return true;
            } else {
                console.error('Failed to remove item from cart:', result.message);
            }
        } catch (error) {
            console.error('Error removing from cart:', error);
        } finally {
            this.isLoading = false;
        }
        
        return false;
    }
    
    // Clear all items from cart
    async clearCart() {
        if (this.isLoading) return false;
        this.isLoading = true;
        
        try {
            let url = API_BASE_URL + 'cart/clear';
            
            // Add session ID if not authenticated
            if (!this.isAuthenticated() && this.sessionId) {
                url += '&session_id=' + this.sessionId;
            }
            
            const response = await fetch(url, {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });
            
            const result = await response.json();
            
            if (result.status === 200) {
                // Successfully cleared, reload cart (will be empty)
                await this.loadCart();
                return true;
            } else {
                console.error('Failed to clear cart:', result.message);
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
        } finally {
            this.isLoading = false;
        }
        
        return false;
    }
    
    // Create request from cart items
    async createRequest() {
        if (this.isLoading || this.items.length === 0) return false;
        this.isLoading = true;
        
        try {
            // User must be authenticated to create a request
            if (!this.isAuthenticated()) {
                alert('Please log in to create a request');
                return false;
            }
            
            // Format stations data for the request
            const stations = this.items.map(item => ({
                station_id: item.station_id,
                station_name: item.station_name,
                station_type: item.station_type
            }));
            
            const response = await fetch(API_BASE_URL + 'requests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.getAuthHeaders()
                },
                body: JSON.stringify({ stations })
            });
            
            const result = await response.json();
            
            if (result.status === 201) {
                // Successfully created request
                alert('Request created successfully! Reference: ' + result.data.reference);
                
                // Clear cart after successful request
                await this.clearCart();
                return result.data;
            } else {
                console.error('Failed to create request:', result.message);
                alert('Failed to create request: ' + result.message);
            }
        } catch (error) {
            console.error('Error creating request:', error);
            alert('Error creating request. Please try again.');
        } finally {
            this.isLoading = false;
        }
        
        return false;
    }
    
    // Update cart UI elements
    updateCartUI() {
        // Update cart counter
        const cartCounters = document.querySelectorAll('.cart-counter');
        cartCounters.forEach(counter => {
            counter.textContent = this.items.length;
        });
        
        // Update cart table if it exists
        const cartTable = document.getElementById('selectedPoints');
        if (cartTable) {
            cartTable.innerHTML = '';
            
            if (this.items.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="2" class="text-center">Your cart is empty</td>';
                cartTable.appendChild(emptyRow);
            } else {
                this.items.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.station_name}</td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="cartService.removeFromCart(${item.cart_id})">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                        </td>
                    `;
                    cartTable.appendChild(row);
                });
            }
        }
        
        // Update total price if it exists
        const totalPriceElement = document.getElementById('cartTotalPrice');
        if (totalPriceElement) {
            totalPriceElement.textContent = this.totalPrice.toFixed(2);
        }
        
        // Update checkout button if it exists
        const checkoutButton = document.getElementById('checkoutButton');
        if (checkoutButton) {
            checkoutButton.disabled = this.items.length === 0;
        }
    }
}

// Create global cart service instance
const cartService = new CartService();

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load cart from server
    cartService.loadCart();
    
    // Override existing cart functions if they exist
    if (window.directAddToSelected) {
        const originalAddToSelected = window.directAddToSelected;
        window.directAddToSelected = function(stationId, stationName, stationType) {
            originalAddToSelected(stationId, stationName);
            
            // Also add to persistent cart
            cartService.addToCart({
                id: stationId,
                name: stationName,
                type: stationType || 'horizontal' // Default to horizontal if not specified
            });
        };
    }
    
    if (window.removeFromSelected) {
        const originalRemoveFromSelected = window.removeFromSelected;
        window.removeFromSelected = function(stationId) {
            originalRemoveFromSelected(stationId);
            
            // Find matching cart item and remove it
            const cartItem = cartService.items.find(item => item.station_id === stationId);
            if (cartItem) {
                cartService.removeFromCart(cartItem.cart_id);
            }
        };
    }
    
    // Listen for login events to reload cart
    document.addEventListener('user-logged-in', function() {
        cartService.loadCart();
    });
});

// Login function that includes cart session ID
async function loginWithCart(username, password) {
    try {
        const response = await fetch('users_api.php?action=login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                password: password,
                session_id: cartService.sessionId
            })
        });
        
        const result = await response.json();
        
        if (result.status === 200) {
            // Store auth token
            localStorage.setItem('auth_token', result.data.token);
            localStorage.setItem('user_data', JSON.stringify(result.data.user));
            
            // Reload cart after login
            await cartService.loadCart();
            
            // Dispatch login event
            document.dispatchEvent(new CustomEvent('user-logged-in', {
                detail: result.data.user
            }));
            
            return result.data;
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Login error:', error);
        throw error;
    }
} 
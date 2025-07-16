/**
 * cart-api.js - WebGNIS Cart API Client
 * API client for interacting with the cart API
 */

class CartAPI {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('webgnis_token') || null;
        this.sessionId = this.getOrCreateSessionId();
        
        // Listen for auth events
        this.setupAuthListeners();
    }

    // Generate or retrieve session ID for non-logged in users
    getOrCreateSessionId() {
        let sessionId = localStorage.getItem('webgnis_cart_session');
        if (!sessionId) {
            sessionId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substring(2, 15);
            localStorage.setItem('webgnis_cart_session', sessionId);
        }
        return sessionId;
    }

    // Set authentication token
    setToken(token) {
        this.token = token;
        localStorage.setItem('webgnis_token', token);
    }

    // Clear authentication token
    clearToken() {
        this.token = null;
        localStorage.removeItem('webgnis_token');
    }
    
    // Set up listeners for auth events
    setupAuthListeners() {
        document.addEventListener('webgnis:auth:login', (event) => {
            console.log('Cart API: Auth login event detected');
            
            // Get the token after login event
            this.token = localStorage.getItem('webgnis_token');
            
            // Delay the sync to ensure token is properly set in localStorage
            setTimeout(() => {
                this.syncCart().then((result) => {
                    console.log('Cart synced after login:', result);
                    if (result.status === 'success') {
                        // Force a cart reload 
                        if (typeof window.loadCartFromAPI === 'function') {
                            window.loadCartFromAPI();
                        }
                    }
                }).catch(error => {
                    console.error('Failed to sync cart after login:', error);
                });
            }, 1000); // 1 second delay to let auth settle
        });
        
        document.addEventListener('webgnis:auth:logout', () => {
            console.log('Cart API: Auth logout event detected');
            this.token = null;
            // Generate a new session ID for the guest cart
            this.sessionId = 'guest_' + Date.now() + '_' + Math.random().toString(36).substring(2, 15);
            localStorage.setItem('webgnis_cart_session', this.sessionId);
        });
        
        // Check token validity on initialization
        if (this.token) {
            const storedToken = localStorage.getItem('webgnis_token');
            if (storedToken !== this.token) {
                this.token = storedToken;
            }
        }
    }

    // Helper method to make API requests
    async request(endpoint, method = 'GET', data = null) {
        // Check token again before each request
        this.token = localStorage.getItem('webgnis_token') || null;
        
        // Construct the URL properly
        let url = this.baseUrl;
        if (endpoint.startsWith('/')) {
            endpoint = endpoint.substring(1);
        }
        if (endpoint) {
            url += `?action=${endpoint}`;
        }
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const options = {
            method,
            headers
        };
        
        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            console.log(`Making Cart API request to: ${url}`);
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result;
        } catch (error) {
            console.error('Cart API request error:', error);
            throw error;
        }
    }

    // Add an item to cart
    async addToCart(stationId, stationType, stationName = null) {
        // Use station ID as name if not provided
        const name = stationName || stationId;
        
        return await this.request('add', 'POST', {
            station_id: stationId,
            station_type: stationType,
            station_name: name,
            session_id: this.sessionId
        });
    }

    // Remove an item from cart
    async removeFromCart(stationId, stationType) {
        return await this.request('remove', 'DELETE', {
            station_id: stationId,
            station_type: stationType,
            session_id: this.sessionId
        });
    }

    // Remove a specific cart item by ID
    async removeCartItem(cartId) {
        return await this.request('remove', 'DELETE', {
            cart_id: cartId,
            session_id: this.sessionId
        });
    }

    // Get all items in cart
    async getCartItems() {
        let url = this.baseUrl + `?action=list`;
        if (!this.token) {
            url += `&session_id=${this.sessionId}`;
        }
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result;
        } catch (error) {
            console.error('Get cart items error:', error);
            throw error;
        }
    }

    // Clear all items in cart
    async clearCart() {
        return await this.request('clear', 'DELETE', {
            session_id: this.sessionId
        });
    }

    // Get cart count
    async getCartCount() {
        let url = this.baseUrl + `?action=count`;
        if (!this.token) {
            url += `&session_id=${this.sessionId}`;
        }
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result.data.count || 0;
        } catch (error) {
            console.error('Get cart count error:', error);
            return 0; // Default to 0 if there's an error
        }
    }

    // Sync cart after login
    async syncCart() {
        try {
            // Only sync if actually logged in
            if (!this.token) {
                console.log('Not attempting to sync cart - user not logged in');
                return { status: 'success', message: 'Not logged in, sync skipped', data: { items_added: 0 } };
            }
            
            return await this.request('sync', 'POST', {
                session_id: this.sessionId
            });
        } catch (error) {
            console.error('Sync cart error:', error);
            // Don't throw the error up, just return a failure object
            return { 
                status: 'error', 
                message: 'Failed to sync cart: ' + error.message,
                data: { items_added: 0 }
            };
        }
    }
}

// Create and export a global instance
const cartApi = new CartAPI('cart_api.php');

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { cartApi };
} else {
    window.cartApi = cartApi;
} 
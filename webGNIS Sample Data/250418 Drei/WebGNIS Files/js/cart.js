// cart.js - Selected points management functionality

// Initialize the selected points list if it doesn't exist
if (!window.selectedPointsList) {
    window.selectedPointsList = [];
}

// Load cart data from API on page load
async function loadCartFromAPI() {
    try {
        const result = await window.cartApi.getCartItems();
        if (result.data && Array.isArray(result.data)) {
            // Convert API format to local format
            window.selectedPointsList = result.data.map(item => ({
                id: item.station_id,
                name: item.station_name || item.station_id, // Use station_name if available, fall back to station_id
                type: item.station_type
            }));
            
            updateSelectedPointsTable();
            console.log("Cart loaded from API:", window.selectedPointsList);
        }
    } catch (error) {
        console.error("Failed to load cart from API:", error);
    }
}

// Add a point to the selected points list
async function directAddToSelected(stationId, stationName, stationType) {
    // Check if station ID is undefined or null
    if (!stationId) {
        console.error("Missing station ID");
        return;
    }
    
    // Use station ID as name if name is empty
    if (!stationName) {
        stationName = stationId;
    }

    // Use 'vertical' as default type if not provided
    if (!stationType) {
        stationType = 'vertical';
    }

    // Check if this ID is already in the list
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            console.log("Already in cart:", stationId);
            return; // Exit if already in list
        }
    }
    
    try {
        console.log("Adding to cart:", {id: stationId, name: stationName, type: stationType});
        
        // Call API to add to cart with station_name
        await window.cartApi.addToCart(stationId, stationType, stationName);
        
        // Add to the local list
        window.selectedPointsList.push({
            id: stationId,
            name: stationName,
            type: stationType
        });
        
        // Refresh the table
        updateSelectedPointsTable();
        
        console.log("Added to cart:", stationId);
        console.log("Current list:", window.selectedPointsList);
    } catch (error) {
        console.error("Failed to add to cart:", error);
    }
}

// Remove a point from the selected points list
async function removeFromSelected(stationId) {
    // Find item to get its type
    let stationType = null;
    let itemIndex = -1;
    
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            stationType = window.selectedPointsList[i].type;
            itemIndex = i;
            break;
        }
    }
    
    if (itemIndex === -1) {
        console.error("Item not found in cart:", stationId);
        return;
    }
    
    try {
        // Call API to remove from cart
        await window.cartApi.removeFromCart(stationId, stationType);
        
        // Remove from the local list
        window.selectedPointsList.splice(itemIndex, 1);
        
        // Refresh the table
        updateSelectedPointsTable();
        
        console.log("Removed from cart:", stationId);
        console.log("Current list:", window.selectedPointsList);
    } catch (error) {
        console.error("Failed to remove from cart:", error);
    }
}

// Update the selected points table
function updateSelectedPointsTable() {
    const tableBody = document.getElementById('selectedPoints');
    if (!tableBody) {
        console.error("Selected points table not found");
        return;
    }
    
    // Clear the table
    tableBody.innerHTML = '';
    
    // If no list, nothing to show
    if (!window.selectedPointsList || window.selectedPointsList.length === 0) {
        return;
    }
    
    // Add each item to the table
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        const item = window.selectedPointsList[i];
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.name}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeFromSelected('${item.id}')">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    }
    
    // Update cart count indicator if it exists
    updateCartCountIndicator();
}

// Alternative add to cart function for UI
async function addToCart(stationName, stationId, stationType) {
    if (!stationId) {
        console.error("Missing station ID for:", stationName);
        return;
    }
    
    // If type isn't provided, get it from the selected GCP type radio button
    if (!stationType) {
        const selectedType = document.querySelector('input[name="gcpType"]:checked');
        stationType = selectedType ? selectedType.value : 'vertical'; // Default to vertical if not found
    }
    
    await directAddToSelected(stationId, stationName, stationType);
}

// Remove point from cart (UI method)
async function removeFromCart(button) {
    const row = button.closest('tr');
    const stationName = row.cells[0].textContent;
    
    // Find the item in the selectedPointsList to get its ID
    let stationId = null;
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].name === stationName) {
            stationId = window.selectedPointsList[i].id;
            break;
        }
    }
    
    if (stationId) {
        await removeFromSelected(stationId);
    } else {
        // If we can't find the ID, just remove the row from the UI
        row.remove();
        console.warn("Removed item from UI only, could not find ID:", stationName);
    }
}

// Clear the entire cart
async function clearCart() {
    try {
        await window.cartApi.clearCart();
        window.selectedPointsList = [];
        updateSelectedPointsTable();
        console.log("Cart cleared");
    } catch (error) {
        console.error("Failed to clear cart:", error);
    }
}

// Update cart count indicator if it exists
async function updateCartCountIndicator() {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        const count = window.selectedPointsList.length;
        cartCountElement.textContent = count.toString();
        cartCountElement.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// Sync cart after login
async function syncCartAfterLogin() {
    try {
        const result = await window.cartApi.syncCart();
        console.log("Cart synced after login:", result);
        await loadCartFromAPI();
    } catch (error) {
        console.error("Failed to sync cart after login:", error);
    }
}

// Initialize the cart when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Load cart data from API
    loadCartFromAPI();
    
    // Set up cart count indicator if needed
    if (!document.getElementById('cartCount') && document.querySelector('.navbar-nav')) {
        // Create a cart count indicator in the navigation
        const navItem = document.createElement('span');
        navItem.id = 'cartCount';
        navItem.className = 'badge bg-danger cart-count';
        navItem.style.display = 'none';
        navItem.style.marginLeft = '5px';
        navItem.textContent = '0';
        
        // Find where to insert it
        const navElement = document.querySelector('.nav-link[href="index.html"],.nav-link[href="about.html"]');
        if (navElement) {
            navElement.appendChild(navItem);
        }
    }
    
    // Check auth status first
    const token = localStorage.getItem('webgnis_token');
    if (token) {
        // Make sure we're using the latest token in cartApi
        if (window.cartApi) {
            window.cartApi.token = token;
        }
    }
    
    // Listen for auth events
    document.addEventListener('webgnis:auth:login', () => {
        setTimeout(() => {
            loadCartFromAPI();
        }, 500); // Slight delay to ensure cart is synced properly
    });
    
    document.addEventListener('webgnis:auth:logout', () => {
        setTimeout(() => {
            loadCartFromAPI();
        }, 500); // Slight delay to ensure cart is cleared properly
    });
});

// Expose functions to global scope for inline event handlers
window.directAddToSelected = directAddToSelected;
window.removeFromSelected = removeFromSelected;
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.clearCart = clearCart;
window.updateSelectedPointsTable = updateSelectedPointsTable;
window.syncCartAfterLogin = syncCartAfterLogin;

// Export cart functionality for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        directAddToSelected,
        removeFromSelected,
        updateSelectedPointsTable,
        addToCart,
        removeFromCart,
        clearCart,
        syncCartAfterLogin
    };
} 
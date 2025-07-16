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
                id: item.id || item.station_id, // Use id, fallback to station_id
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

    // Check if this ID and TYPE is already in the list
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId && window.selectedPointsList[i].type === stationType) {
            console.log("Item with same ID and Type already in cart:", stationId, stationType);
            // Optionally, provide user feedback e.g., using a toast notification
            // For now, just log and return to prevent duplicate.
            return; 
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
    
    // Ensure stationId is treated as a string for comparison, as it comes from a data attribute.
    const idToCompare = String(stationId);

    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (String(window.selectedPointsList[i].id) === idToCompare) {
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
        // Capitalize the first letter of the type for display
        const displayType = item.type === 'caap' ? 'Horizontal (CAAP)' : item.type.charAt(0).toUpperCase() + item.type.slice(1);
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${displayType}</td>
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
    // We need a reliable way to get the ID. Let's assume the ID is stored in a data attribute on the button or row.
    // Since we generate the button with onclick="removeFromSelected('${item.id}')", we can't use this function directly from an event listener easily.
    // The existing implementation calls removeFromSelected with the id directly. This function is likely dead code.
    // To be safe, let's assume it *could* be called, and that the ID is on the row.
    const stationId = row.dataset.stationId;
    
    if (stationId) {
        await removeFromSelected(stationId);
    } else {
        // Fallback for old structure if needed, but it's unreliable.
        const stationName = row.cells[0].textContent;
        console.warn("Could not find station-id on row. Falling back to searching by name, which may be unreliable.", stationName);
        let foundId = null;
        for (let i = 0; i < window.selectedPointsList.length; i++) {
            if (window.selectedPointsList[i].name === stationName) {
                foundId = window.selectedPointsList[i].id;
                break;
            }
        }
        if (foundId) {
            await removeFromSelected(foundId);
        } else {
            row.remove();
            console.warn("Removed item from UI only, could not find ID:", stationName);
        }
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
function initializeCart() {
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
        const navElement = document.querySelector('.nav-link[href="index.php"],.nav-link[href="about.php"]');
        if (navElement) {
            navElement.appendChild(navItem);
        }
    }
    
    // Check auth status first
    const token = localStorage.getItem('webgnis_token');
    if (token) {
        // Make sure we're using the latest token in cartApi
        if (window.cartApi) {
            window.cartApi.setToken(token);
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
}

// Make functions available globally
window.addToCart = addToCart;
window.removeFromSelected = removeFromSelected;
window.clearCart = clearCart;
window.syncCartAfterLogin = syncCartAfterLogin;

export { initializeCart };

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
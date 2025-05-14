// cart.js - Selected points management functionality

// Initialize the selected points list if it doesn't exist
if (!window.selectedPointsList) {
    window.selectedPointsList = [];
}

// Add a point to the selected points list
function directAddToSelected(stationId, stationName, stationType) {
    // Check if this ID is already in the list
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            console.log("Already in cart:", stationId);
            return; // Exit if already in list
        }
    }
    
    // Determine station type from radio button if not provided
    if (!stationType) {
        const horizontalRadio = document.getElementById('horizontalType');
        const verticalRadio = document.getElementById('verticalType');
        const gravityRadio = document.getElementById('gravityType');
        
        if (horizontalRadio && horizontalRadio.checked) {
            stationType = 'horizontal';
        } else if (verticalRadio && verticalRadio.checked) {
            stationType = 'benchmark';
        } else if (gravityRadio && gravityRadio.checked) {
            stationType = 'gravity';
        } else {
            stationType = 'horizontal'; // Default
        }
    }
    
    // Add to the in-memory list
    window.selectedPointsList.push({
        id: stationId,
        name: stationName,
        type: stationType
    });
    
    // Refresh the table
    updateSelectedPointsTable();
    
    // Also add to the persistent cart via the cart service
    if (window.cartService) {
        window.cartService.addToCart({
            id: stationId,
            name: stationName,
            type: stationType
        });
    }
    
    console.log("Added to cart:", stationId);
    console.log("Current list:", window.selectedPointsList);
}

// Remove a point from the selected points list
function removeFromSelected(stationId) {
    let removedItem = null;
    
    // Find and remove from in-memory list
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            removedItem = window.selectedPointsList[i];
            window.selectedPointsList.splice(i, 1);
            break;
        }
    }
    
    // Refresh the table
    updateSelectedPointsTable();
    
    // Also remove from the persistent cart
    if (window.cartService && removedItem) {
        // Find the cart item with matching station_id
        const cartItem = window.cartService.items.find(item => item.station_id === stationId);
        if (cartItem) {
            window.cartService.removeFromCart(cartItem.cart_id);
        }
    }
    
    console.log("Removed from cart:", stationId);
    console.log("Current list:", window.selectedPointsList);
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
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="2" class="text-center">Your cart is empty</td>';
        tableBody.appendChild(emptyRow);
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
    
    // Update the Request Certificates button state
    const requestBtn = document.getElementById('requestCertBtn');
    if (requestBtn) {
        requestBtn.disabled = window.selectedPointsList.length === 0;
    }
}

// Alternative add to cart function for UI
function addToCart(stationName) {
    const selectedPointsTable = document.getElementById('selectedPoints');
    
    // Check if point is already in cart
    const existingRows = selectedPointsTable.getElementsByTagName('tr');
    for (let row of existingRows) {
        if (row.cells[0].textContent === stationName) {
            return; // Point already in cart
        }
    }

    // Add new row to selected points
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${stationName}</td>
        <td>
            <button class="btn btn-danger btn-sm" onclick="removeFromCart(this)">
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </td>
    `;
    selectedPointsTable.appendChild(row);
}

// Remove point from cart (UI method)
function removeFromCart(button) {
    const row = button.closest('tr');
    const stationName = row.cells[0].textContent;
    
    // Remove from the selectedPointsList if it exists there
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].name === stationName) {
            window.selectedPointsList.splice(i, 1);
            break;
        }
    }
    
    row.remove();
}

// Initialize the cart when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Check if the persistent cart service is available
    if (window.cartService) {
        // Load items from the persistent cart
        window.cartService.loadCart().then(items => {
            // Convert cart items to the format expected by selectedPointsList
            window.selectedPointsList = items.map(item => ({
                id: item.station_id,
                name: item.station_name,
                type: item.station_type
            }));
            
            // Update the UI
            updateSelectedPointsTable();
        });
    } else {
        // Fallback to local memory only
        updateSelectedPointsTable();
    }
    
    // Add event listener to the Request Certificates button
    const requestBtn = document.getElementById('requestCertBtn');
    if (requestBtn) {
        requestBtn.addEventListener('click', function() {
            if (!localStorage.getItem('auth_token')) {
                alert('Please log in to request certificates.');
                
                // Show the login modal
                const authModal = new bootstrap.Modal(document.getElementById('authModal'));
                authModal.show();
                return;
            }
            
            if (window.selectedPointsList.length === 0) {
                alert('Please select at least one station.');
                return;
            }
            
            // If using cart service, create request
            if (window.cartService) {
                window.cartService.createRequest().then(result => {
                    if (result) {
                        console.log('Request created:', result);
                        
                        // Clear the in-memory cart too
                        window.selectedPointsList = [];
                        updateSelectedPointsTable();
                    }
                });
            } else {
                alert('Request system is not available. Please try again later.');
            }
        });
    }
});

// Expose functions to global scope for inline event handlers
window.directAddToSelected = directAddToSelected;
window.removeFromSelected = removeFromSelected;
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.updateSelectedPointsTable = updateSelectedPointsTable;

// Export cart functionality for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        directAddToSelected,
        removeFromSelected,
        updateSelectedPointsTable,
        addToCart,
        removeFromCart
    };
} 
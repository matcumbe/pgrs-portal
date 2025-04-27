// cart.js - Selected points management functionality

// Add a point to the selected points list
function directAddToSelected(stationId, stationName) {
    // Create the list if it doesn't exist
    if (!window.selectedPointsList) {
        window.selectedPointsList = [];
    }
    
    // Check if this ID is already in the list
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            console.log("Already in cart:", stationId);
            return; // Exit if already in list
        }
    }
    
    // Add to the list
    window.selectedPointsList.push({
        id: stationId,
        name: stationName
    });
    
    // Refresh the table
    updateSelectedPointsTable();
    
    console.log("Added to cart:", stationId);
    console.log("Current list:", window.selectedPointsList);
}

// Remove a point from the selected points list
function removeFromSelected(stationId) {
    // If no list, nothing to remove
    if (!window.selectedPointsList) {
        return;
    }
    
    // Find and remove
    for (let i = 0; i < window.selectedPointsList.length; i++) {
        if (window.selectedPointsList[i].id === stationId) {
            window.selectedPointsList.splice(i, 1);
            break;
        }
    }
    
    // Refresh the table
    updateSelectedPointsTable();
    
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
    row.remove();
}

// Expose functions to global scope for inline event handlers
window.directAddToSelected = directAddToSelected;
window.removeFromSelected = removeFromSelected;
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;

// Export cart functionality
export {
    directAddToSelected,
    removeFromSelected,
    updateSelectedPointsTable,
    addToCart,
    removeFromCart
}; 
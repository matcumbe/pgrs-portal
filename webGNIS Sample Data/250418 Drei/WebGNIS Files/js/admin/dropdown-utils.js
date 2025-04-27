// Admin Dropdown Utilities Module

/**
 * Populates a dropdown element with options
 * @param {string} elementId - ID of the dropdown element
 * @param {Array} data - Array of data objects for options
 * @param {string} valueKey - Key to use for option value
 * @param {string} textKey - Key to use for option text
 */
export function populateDropdown(elementId, data, valueKey = 'name', textKey = 'name') {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Store current value
    const currentValue = element.value;
    
    // Clear existing options, keeping only the first placeholder option
    while (element.options.length > 1) {
        element.remove(1);
    }
    
    // Add new options from data
    if (data && data.length) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey] || '';
            option.textContent = item[textKey] || '';
            element.appendChild(option);
        });
    }
    
    // Try to restore the previous selection
    if (currentValue && element.querySelector(`option[value="${currentValue}"]`)) {
        element.value = currentValue;
    }
}

/**
 * Creates options for a dropdown from an array of values
 * @param {string} elementId - ID of the dropdown element
 * @param {Array} values - Array of simple values
 */
export function createDropdownOptions(elementId, values) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Store current value
    const currentValue = element.value;
    
    // Clear existing options, keeping only the first placeholder option
    while (element.options.length > 1) {
        element.remove(1);
    }
    
    // Add options
    if (values && values.length) {
        values.forEach(value => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            element.appendChild(option);
        });
    }
    
    // Try to restore the previous selection
    if (currentValue && element.querySelector(`option[value="${currentValue}"]`)) {
        element.value = currentValue;
    }
}

/**
 * Sets up a cascading relationship between parent and child dropdowns
 * @param {string} parentId - ID of parent dropdown
 * @param {string} childId - ID of child dropdown
 * @param {Function} fetchDataFunc - Function to fetch data for child dropdown
 */
export function setupDropdownCascade(parentId, childId, fetchDataFunc) {
    const parentElement = document.getElementById(parentId);
    if (!parentElement) return;
    
    parentElement.addEventListener('change', async function() {
        const parentValue = this.value;
        const childElement = document.getElementById(childId);
        
        if (!childElement) return;
        
        // Clear child dropdown
        while (childElement.options.length > 1) {
            childElement.remove(1);
        }
        childElement.value = '';
        
        // If no parent value, don't try to fetch child options
        if (!parentValue) return;
        
        try {
            // Fetch child options based on parent value
            const childData = await fetchDataFunc(parentValue);
            
            // Populate child dropdown
            if (childData && childData.length) {
                childData.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.name || '';
                    option.textContent = item.name || '';
                    childElement.appendChild(option);
                });
            }
        } catch (error) {
            console.error(`Error setting up cascade for ${parentId} -> ${childId}:`, error);
        }
    });
} 
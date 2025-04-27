import { usersApi } from './users/api-client.js';
import { checkAuthStatus } from './users/auth.js';

const form = document.getElementById('accountSettingsForm');
const accSettingsAlert = document.getElementById('accSettingsAlert');

// Helper to create form groups
function createFormGroup(label, input) {
    const div = document.createElement('div');
    div.className = 'mb-3';
    if (label) div.appendChild(label);
    div.appendChild(input);
    return div;
}

// Render the form fields dynamically
async function renderAccountForm(user, sexes, sectors) {
    console.log("Rendering form with user data:", user);
    
    form.innerHTML = '';
    // Username (disabled)
    const usernameLabel = document.createElement('label');
    usernameLabel.className = 'form-label';
    usernameLabel.textContent = 'Username';
    const usernameInput = document.createElement('input');
    usernameInput.type = 'text';
    usernameInput.className = 'form-control';
    usernameInput.value = user.username || '';
    usernameInput.disabled = true;
    form.appendChild(createFormGroup(usernameLabel, usernameInput));

    // Email
    const emailLabel = document.createElement('label');
    emailLabel.className = 'form-label';
    emailLabel.textContent = 'Email';
    const emailInput = document.createElement('input');
    emailInput.type = 'email';
    emailInput.className = 'form-control';
    emailInput.id = 'accEmail';
    emailInput.value = user.email || '';
    emailInput.required = true;
    form.appendChild(createFormGroup(emailLabel, emailInput));

    // Contact Number
    const contactLabel = document.createElement('label');
    contactLabel.className = 'form-label';
    contactLabel.textContent = 'Contact Number';
    const contactInput = document.createElement('input');
    contactInput.type = 'text';
    contactInput.className = 'form-control';
    contactInput.id = 'accContact';
    contactInput.value = user.contact_number || '';
    form.appendChild(createFormGroup(contactLabel, contactInput));

    // Sex (dropdown)
    const sexLabel = document.createElement('label');
    sexLabel.className = 'form-label';
    sexLabel.textContent = 'Sex';
    const sexSelect = document.createElement('select');
    sexSelect.className = 'form-select';
    sexSelect.id = 'accSex';
    
    // Create empty option first
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Select Sex';
    sexSelect.appendChild(emptyOption);
    
    // Add options for each sex
    sexes.forEach(sex => {
        const option = document.createElement('option');
        option.value = sex.id;
        option.textContent = sex.sex_name;
        
        // Match by name if ID is not available
        if (user.sex_id == sex.id) {
            option.selected = true;
        } else if (user.sex_name === sex.sex_name) {
            option.selected = true;
        }
        sexSelect.appendChild(option);
    });
    form.appendChild(createFormGroup(sexLabel, sexSelect));

    // Name on Certificate
    const nameCertLabel = document.createElement('label');
    nameCertLabel.className = 'form-label';
    nameCertLabel.textContent = 'Name on Certificate';
    const nameCertInput = document.createElement('input');
    nameCertInput.type = 'text';
    nameCertInput.className = 'form-control';
    nameCertInput.id = 'accNameOnCert';
    nameCertInput.value = user.name_on_certificate || '';
    form.appendChild(createFormGroup(nameCertLabel, nameCertInput));

    // Account Type (disabled)
    const typeLabel = document.createElement('label');
    typeLabel.className = 'form-label';
    typeLabel.textContent = 'Account Type';
    const typeInput = document.createElement('input');
    typeInput.type = 'text';
    typeInput.className = 'form-control';
    typeInput.value = (user.user_type === 'company') ? 'Company Account' : (user.user_type === 'admin' ? 'Admin Account' : 'Individual Account');
    typeInput.disabled = true;
    form.appendChild(createFormGroup(typeLabel, typeInput));

    // Individual fields
    if (user.user_type === 'individual') {
        // Full Name
        const fullNameLabel = document.createElement('label');
        fullNameLabel.className = 'form-label';
        fullNameLabel.textContent = 'Full Name';
        const fullNameInput = document.createElement('input');
        fullNameInput.type = 'text';
        fullNameInput.className = 'form-control';
        fullNameInput.id = 'accFullName';
        fullNameInput.value = user.full_name || '';
        form.appendChild(createFormGroup(fullNameLabel, fullNameInput));
        // Address
        const addressLabel = document.createElement('label');
        addressLabel.className = 'form-label';
        addressLabel.textContent = 'Address';
        const addressInput = document.createElement('input');
        addressInput.type = 'text';
        addressInput.className = 'form-control';
        addressInput.id = 'accAddress';
        addressInput.value = user.address || '';
        form.appendChild(createFormGroup(addressLabel, addressInput));
    }
    // Company fields
    if (user.user_type === 'company') {
        // Company Name
        const companyNameLabel = document.createElement('label');
        companyNameLabel.className = 'form-label';
        companyNameLabel.textContent = 'Company Name';
        const companyNameInput = document.createElement('input');
        companyNameInput.type = 'text';
        companyNameInput.className = 'form-control';
        companyNameInput.id = 'accCompanyName';
        companyNameInput.value = user.company_name || '';
        form.appendChild(createFormGroup(companyNameLabel, companyNameInput));
        // Company Address
        const companyAddressLabel = document.createElement('label');
        companyAddressLabel.className = 'form-label';
        companyAddressLabel.textContent = 'Company Address';
        const companyAddressInput = document.createElement('input');
        companyAddressInput.type = 'text';
        companyAddressInput.className = 'form-control';
        companyAddressInput.id = 'accCompanyAddress';
        companyAddressInput.value = user.company_address || '';
        form.appendChild(createFormGroup(companyAddressLabel, companyAddressInput));
        // Sector (dropdown)
        const sectorLabel = document.createElement('label');
        sectorLabel.className = 'form-label';
        sectorLabel.textContent = 'Sector';
        const sectorSelect = document.createElement('select');
        sectorSelect.className = 'form-select';
        sectorSelect.id = 'accSector';
        
        // Create empty option first
        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Select Sector';
        sectorSelect.appendChild(emptyOption);
        
        sectors.forEach(sector => {
            const option = document.createElement('option');
            option.value = sector.id;
            option.textContent = sector.sector_name;
            
            // Match by ID or name
            if (user.sector_id == sector.id) {
                option.selected = true;
            } else if (user.sector_name === sector.sector_name) {
                option.selected = true;
            }
            sectorSelect.appendChild(option);
        });
        form.appendChild(createFormGroup(sectorLabel, sectorSelect));
        // Authorized Representative
        const repLabel = document.createElement('label');
        repLabel.className = 'form-label';
        repLabel.textContent = 'Authorized Representative';
        const repInput = document.createElement('input');
        repInput.type = 'text';
        repInput.className = 'form-control';
        repInput.id = 'accRepresentative';
        repInput.value = user.authorized_representative || '';
        form.appendChild(createFormGroup(repLabel, repInput));
    }
    // Password change section
    const hr = document.createElement('hr');
    form.appendChild(hr);
    const pwTitle = document.createElement('h6');
    pwTitle.textContent = 'Change Password';
    form.appendChild(pwTitle);
    // Current Password
    const curPwLabel = document.createElement('label');
    curPwLabel.className = 'form-label';
    curPwLabel.textContent = 'Current Password';
    const curPwInput = document.createElement('input');
    curPwInput.type = 'password';
    curPwInput.className = 'form-control';
    curPwInput.id = 'accCurrentPassword';
    curPwInput.autocomplete = 'current-password';
    form.appendChild(createFormGroup(curPwLabel, curPwInput));
    // New Password
    const newPwLabel = document.createElement('label');
    newPwLabel.className = 'form-label';
    newPwLabel.textContent = 'New Password';
    const newPwInput = document.createElement('input');
    newPwInput.type = 'password';
    newPwInput.className = 'form-control';
    newPwInput.id = 'accNewPassword';
    newPwInput.autocomplete = 'new-password';
    form.appendChild(createFormGroup(newPwLabel, newPwInput));
    // Confirm New Password
    const confPwLabel = document.createElement('label');
    confPwLabel.className = 'form-label';
    confPwLabel.textContent = 'Confirm New Password';
    const confPwInput = document.createElement('input');
    confPwInput.type = 'password';
    confPwInput.className = 'form-control';
    confPwInput.id = 'accConfirmPassword';
    confPwInput.autocomplete = 'new-password';
    form.appendChild(createFormGroup(confPwLabel, confPwInput));
    // Alert
    accSettingsAlert.className = 'alert d-none';
    accSettingsAlert.id = 'accSettingsAlert';
    form.appendChild(accSettingsAlert);
    // Save button
    const saveBtn = document.createElement('button');
    saveBtn.type = 'submit';
    saveBtn.className = 'btn btn-success w-100';
    saveBtn.textContent = 'Save Changes';
    form.appendChild(saveBtn);
}

// Load reference data and user info
async function loadAccountSettings() {
    const status = await checkAuthStatus();
    if (!status.authenticated || !status.user) {
        window.location.href = 'index.html';
        return;
    }
    
    // Log the original user data for debugging
    console.log("Original user data:", status.user);
    
    // Create a new user object with all properties from status.user
    let userDetails = { ...status.user };
    
    // For company users, merge company details
    if (userDetails.user_type === 'company' && userDetails.company_details) {
        console.log("Company details found:", userDetails.company_details);
        // Copy specific fields to make sure they're at the top level
        userDetails.company_name = userDetails.company_details.company_name;
        userDetails.company_address = userDetails.company_details.company_address;
        userDetails.sector_id = userDetails.company_details.sector_id;
        userDetails.authorized_representative = userDetails.company_details.authorized_representative;
    }
    
    // For individual users, merge individual details
    if (userDetails.user_type === 'individual' && userDetails.individual_details) {
        console.log("Individual details found:", userDetails.individual_details);
        // Copy specific fields to make sure they're at the top level
        userDetails.full_name = userDetails.individual_details.full_name;
        userDetails.address = userDetails.individual_details.address;
    }
    
    // Extra fetch for missing details if needed
    if (userDetails.user_type === 'company' && !userDetails.company_details) {
        try {
            console.log("Fetching additional company details for user ID:", userDetails.user_id);
            const res = await usersApi.getCompanyById(userDetails.user_id);
            if (res && res.data) {
                console.log("Additional company details fetched:", res.data);
                // Copy specific fields
                userDetails.company_name = res.data.company_name;
                userDetails.company_address = res.data.company_address;
                userDetails.sector_id = res.data.sector_id;
                userDetails.authorized_representative = res.data.authorized_representative;
            }
        } catch (error) {
            console.error("Error fetching company details:", error);
        }
    } else if (userDetails.user_type === 'individual' && !userDetails.individual_details) {
        try {
            console.log("Fetching additional individual details for user ID:", userDetails.user_id);
            const res = await usersApi.getIndividualById(userDetails.user_id);
            if (res && res.data) {
                console.log("Additional individual details fetched:", res.data);
                // Copy specific fields
                userDetails.full_name = res.data.full_name;
                userDetails.address = res.data.address;
            }
        } catch (error) {
            console.error("Error fetching individual details:", error);
        }
    }

    // Fetch reference data
    const sexesRes = await usersApi.getSexes();
    const sectorsRes = await usersApi.getSectors();
    const sexes = sexesRes && sexesRes.data ? sexesRes.data : [];
    const sectors = sectorsRes && sectorsRes.data ? sectorsRes.data : [];
    
    console.log("Final user details for form:", userDetails);
    
    renderAccountForm(userDetails, sexes, sectors);
}

// Handle form submit
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    accSettingsAlert.classList.add('d-none');
    accSettingsAlert.classList.remove('alert-success', 'alert-danger');
    // Get user info
    const status = await checkAuthStatus();
    if (!status.authenticated || !status.user) {
        window.location.href = 'index.html';
        return;
    }
    const user = status.user;
    let updateData = {
        email: form.querySelector('#accEmail')?.value,
        contact_number: form.querySelector('#accContact')?.value,
        sex_id: form.querySelector('#accSex')?.value,
        name_on_certificate: form.querySelector('#accNameOnCert')?.value
    };
    if (user.user_type === 'individual') {
        updateData.full_name = form.querySelector('#accFullName')?.value;
        updateData.address = form.querySelector('#accAddress')?.value;
    }
    if (user.user_type === 'company') {
        updateData.company_name = form.querySelector('#accCompanyName')?.value;
        updateData.company_address = form.querySelector('#accCompanyAddress')?.value;
        updateData.sector_id = form.querySelector('#accSector')?.value;
        updateData.authorized_representative = form.querySelector('#accRepresentative')?.value;
    }
    // Remove undefined/null
    Object.keys(updateData).forEach(k => (updateData[k] == null) && delete updateData[k]);
    let updateSuccess = true;
    if (Object.keys(updateData).length > 0) {
        try {
            const result = await usersApi.updateUser(user.user_id, updateData);
            if (!result || result.status !== 200) throw new Error(result?.message || 'Failed to update account');
        } catch (err) {
            updateSuccess = false;
            accSettingsAlert.textContent = err.message;
            accSettingsAlert.classList.add('alert', 'alert-danger');
            accSettingsAlert.classList.remove('d-none');
        }
    }
    // Password change
    const curPw = form.querySelector('#accCurrentPassword')?.value;
    const newPw = form.querySelector('#accNewPassword')?.value;
    const confPw = form.querySelector('#accConfirmPassword')?.value;
    if (curPw && newPw && confPw) {
        if (newPw !== confPw) {
            accSettingsAlert.textContent = 'New passwords do not match.';
            accSettingsAlert.classList.add('alert', 'alert-danger');
            accSettingsAlert.classList.remove('d-none');
            return;
        }
        try {
            const result = await usersApi.updatePassword(user.user_id, curPw, newPw);
            if (!result) throw new Error('Failed to update password');
        } catch (err) {
            updateSuccess = false;
            accSettingsAlert.textContent = err.message;
            accSettingsAlert.classList.add('alert', 'alert-danger');
            accSettingsAlert.classList.remove('d-none');
        }
    }
    if (updateSuccess) {
        accSettingsAlert.textContent = 'Account updated successfully!';
        accSettingsAlert.classList.add('alert', 'alert-success');
        accSettingsAlert.classList.remove('d-none');
        // Clear password fields
        form.querySelector('#accCurrentPassword').value = '';
        form.querySelector('#accNewPassword').value = '';
        form.querySelector('#accConfirmPassword').value = '';
    }
});

// Initial load
loadAccountSettings(); 
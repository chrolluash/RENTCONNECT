// assets/scripts/index.js - RentConnect Main JavaScript (FIXED VERSION)

// ==================== GLOBAL VARIABLES ====================
let currentRole = ''; // Track whether user logs in as tenant or landlord
let isSubmitting = false; // Prevent double submissions

// ==================== MODAL FUNCTIONS ====================

// Open Signup Modal
function openSignupModal() {
    document.getElementById('signupModal').classList.add('active');
}

// Close Signup Modal
function closeSignupModal() {
    document.getElementById('signupModal').classList.remove('active');
    document.getElementById('signupForm').reset();
    // Clear all input fields explicitly
    document.getElementById('signupFirstName').value = '';
    document.getElementById('signupLastName').value = '';
    document.getElementById('signupEmail').value = '';
    document.getElementById('signupContact').value = '';
    document.getElementById('signupRole').value = '';
    document.getElementById('signupPassword').value = '';
    document.getElementById('confirmPassword').value = '';
}

// Open Role Login Modal
function openRoleModal(role) {
    currentRole = role;
    document.getElementById('roleModalTitle').textContent = 
        role === 'tenant' ? 'Login as Tenant' : 'Login as Landlord';
    document.getElementById('roleModal').classList.add('active');
}

// Close Role Login Modal
function closeRoleModal() {
    document.getElementById('roleModal').classList.remove('active');
    // Clear login form
    document.getElementById('loginEmail').value = '';
    document.getElementById('loginPassword').value = '';
    currentRole = '';
}

// Scroll to Cards Section
function scrollToCards() {
    document.getElementById('cardsSection')?.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
    });
}

// ==================== VALIDATION FUNCTIONS ====================

// Validate contact number (Philippine format)
function validateContactNumber(contact) {
    // Remove all non-digit characters
    const cleaned = contact.replace(/\D/g, '');
    
    // Check if it's a valid Philippine number (starts with 09 or +639, 11 digits total)
    const isValid = /^(09|\+639)\d{9}$/.test(contact) || /^\d{11}$/.test(cleaned);
    
    return {
        isValid: isValid,
        message: isValid ? '' : '⚠️ Please enter a valid Philippine contact number (e.g., 09123456789 or +639123456789)'
    };
}

// Validate password strength
function validatePassword(password) {
    if (password.length < 6) {
        return {
            isValid: false,
            message: '⚠️ Password must be at least 6 characters long'
        };
    }
    
    // Optional: Add more password rules here
    const hasNumber = /\d/.test(password);
    const hasLetter = /[a-zA-Z]/.test(password);
    
    if (!hasNumber || !hasLetter) {
        return {
            isValid: false,
            message: '⚠️ Password must contain both letters and numbers'
        };
    }
    
    return { isValid: true, message: '' };
}

// Validate email format
function validateEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return {
        isValid: emailPattern.test(email),
        message: emailPattern.test(email) ? '' : '⚠️ Please enter a valid email address'
    };
}

// ==================== AUTHENTICATION FUNCTIONS ====================

// Registration Handler - FIXED VERSION
async function handleSignup(event) {
    event.preventDefault();
    
    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    
    const firstName = document.getElementById('signupFirstName').value.trim();
    const lastName = document.getElementById('signupLastName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const contact = document.getElementById('signupContact').value.trim();
    const role = document.getElementById('signupRole').value;
    const password = document.getElementById('signupPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validation
    const emailValidation = validateEmail(email);
    if (!emailValidation.isValid) {
        alert(emailValidation.message);
        return;
    }

    if (password !== confirmPassword) {
        alert('⚠️ Passwords do not match!');
        return;
    }

    const passwordValidation = validatePassword(password);
    if (!passwordValidation.isValid) {
        alert(passwordValidation.message);
        return;
    }

    const contactValidation = validateContactNumber(contact);
    if (!contactValidation.isValid) {
        alert(contactValidation.message);
        return;
    }

    // Show loading state
    isSubmitting = true;
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Creating Account...';
    submitBtn.disabled = true;

    try {
        // Send registration data to PHP backend
        const response = await fetch('/RENTCONNECT(2)/functions/index_f/save_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                firstName: firstName,
                lastName: lastName,
                email: email,
                contact: contact,
                role: role,
                password: password,
                authProvider: 'email'
            })
        });

        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get response text first to debug
        const responseText = await response.text();
        console.log('Raw response:', responseText); // For debugging
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server. Check browser console for details.');
        }

        if (result.success) {
            alert(`✅ Account created successfully! Welcome, ${firstName}! Please log in to continue.`);
            closeSignupModal(); // This now clears all fields
            
            // Optionally scroll to login cards
            setTimeout(() => {
                scrollToCards();
            }, 300);
        } else {
            // Handle specific error messages
            if (result.message && result.message.includes('email already exists')) {
                alert('❌ This email is already registered. Please login instead.');
            } else {
                alert(`❌ Registration failed: ${result.message || 'Unknown error'}`);
            }
        }
        
    } catch (error) {
        console.error('Signup error:', error);
        
        // More specific error messages
        if (error.message.includes('Failed to fetch')) {
            alert('❌ Cannot connect to server. Please check your internet connection.');
        } else if (error.message.includes('Invalid JSON')) {
            alert('❌ Server error. Please check the browser console (F12) for details.');
        } else {
            alert(`❌ Registration failed: ${error.message}`);
        }
    } finally {
        // Reset loading state
        isSubmitting = false;
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// Login Handler - FIXED VERSION
async function handleLogin(event) {
    event.preventDefault();
    
    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    // Basic validation
    const emailValidation = validateEmail(email);
    if (!emailValidation.isValid) {
        alert(emailValidation.message);
        return;
    }

    if (!password) {
        alert('⚠️ Please enter your password');
        return;
    }

    // Show loading state
    isSubmitting = true;
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Logging in...';
    submitBtn.disabled = true;

    try {
        // Send login data to PHP backend
        const response = await fetch('/RENTCONNECT(2)/functions/index_f/check_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password,
                requestedRole: currentRole, // Send the role user is trying to login as
                action: 'login'
            })
        });

        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get response text first to debug
        const responseText = await response.text();
        console.log('Raw response:', responseText); // For debugging
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server. Check browser console for details.');
        }

        if (result.success) {
            // Check if the user's role matches the requested role
            if (currentRole && result.user.role !== currentRole) {
                alert(`❌ This account is registered as a ${result.user.role}. Please use the correct login option.`);
                return;
            }

            alert(`✅ Welcome back, ${result.user.first_name}!`);
            closeRoleModal();
            
            // Redirect to appropriate dashboard
            setTimeout(() => {
                window.location.href = `${result.user.role}-dashboard.php`;
            }, 500);
        } else {
            // Handle specific error messages
            if (result.message.includes('not found')) {
                alert('❌ No account found with this email. Please sign up first.');
            } else if (result.message.includes('password')) {
                alert('❌ Incorrect password. Please try again.');
            } else if (result.message.includes('role')) {
                alert(`❌ ${result.message}`);
            } else {
                alert(`❌ Login failed: ${result.message}`);
            }
        }
        
    } catch (error) {
        console.error('Login error:', error);
        
        // More specific error messages
        if (error.message.includes('Failed to fetch')) {
            alert('❌ Cannot connect to server. Please check your internet connection.');
        } else if (error.message.includes('Invalid JSON')) {
            alert('❌ Server error. Please check the browser console (F12) for details.');
        } else {
            alert(`❌ Login failed: ${error.message}`);
        }
    } finally {
        // Reset loading state
        isSubmitting = false;
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// ==================== UTILITY FUNCTIONS ====================

// Password toggle visibility
function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        
        // Toggle eye icon
        if (iconElement) {
            const eyeOpen = iconElement.querySelectorAll('.eye-open');
            const eyeClosed = iconElement.querySelectorAll('.eye-closed');
            
            eyeOpen.forEach(el => el.style.display = isPassword ? 'none' : 'block');
            eyeClosed.forEach(el => el.style.display = isPassword ? 'block' : 'none');
        }
    }
}

// Close modals when clicking outside
window.onclick = (event) => {
    if (event.target.classList.contains('modal')) {
        if (event.target.id === 'signupModal') {
            closeSignupModal(); // This will clear the form
        } else if (event.target.id === 'roleModal') {
            closeRoleModal(); // This will clear the login form
        }
        event.target.classList.remove('active');
    }
};

// ==================== INITIALIZATION ====================

// Console log for debugging
console.log('🏠 RentConnect JavaScript loaded successfully');

// Make functions globally accessible
window.openSignupModal = openSignupModal;
window.closeSignupModal = closeSignupModal;
window.openRoleModal = openRoleModal;
window.closeRoleModal = closeRoleModal;
window.scrollToCards = scrollToCards;
window.handleSignup = handleSignup;
window.handleLogin = handleLogin;
window.togglePassword = togglePassword;
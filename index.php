<?php
// index.php - RentConnect Landing Page
session_start();

// REMOVED: Auto-redirect check
// Users can now view the homepage even if logged in
// They will only be redirected when they manually try to login
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentConnect - Smart Rental Platform</title>
    <link rel="stylesheet" href="assets/styles/index.css">
</head>
<body>
    
    <div class="container">
        <nav>
            <div class="logo">
                <div class="logo-icon">🏠</div>
                <span>RENTCONNECT</span>
            </div>
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Show dashboard button if logged in -->
                    <button class="btn btn-login" onclick="window.location.href='<?php echo $_SESSION['user_role']; ?>-dashboard.php'">Dashboard</button>
                    <button class="btn btn-signup" onclick="window.location.href='logout.php'">Logout</button>
                <?php else: ?>
                    <!-- Show login/signup if not logged in -->
                    <button class="btn btn-login" onclick="scrollToCards()">Log In</button>
                    <button class="btn btn-signup" onclick="openSignupModal()">Sign Up</button>
                <?php endif; ?>
            </div>
        </nav>

        <div class="hero">
            <h1>Welcome to RENTCONNECT</h1>
            <p>Rent then Connects you to your shelter</p>
        </div>

        <div class="cards-container" id="cardsSection">
            <div class="card" onclick="openRoleModal('tenant')">
                <div class="card-content">
                    <div class="card-icon">🔑</div>
                    <h2>Tenants</h2>
                    <p>Find your perfect rental home and manage your lease with ease. Search through thousands of verified properties.</p>
                </div>
            </div>

            <div class="card" onclick="openRoleModal('landlord')">
                <div class="card-content">
                    <div class="card-icon">🏘️</div>
                    <h2>Landlords</h2>
                    <p>List and manage your properties effortlessly. Connect with quality tenants and streamline your rental business.</p>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <section class="about-section" id="about">
            <div class="about-content">
                <div class="about-text">
                    <h2>About RentConnect</h2>
                    <p class="about-subtitle">Revolutionizing the rental experience</p>
                    <p class="about-description">
                        RentConnect is your trusted partner in navigating the rental market. We bridge the gap between property owners and those seeking their perfect home, creating seamless connections that benefit everyone.
                    </p>
                    <p class="about-description">
                        Our platform combines cutting-edge technology with personalized service to make property rentals simple, transparent, and secure. Whether you're a tenant searching for your dream space or a landlord looking to maximize your property's potential, we're here to help.
                    </p>
                    <div class="about-features">
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <div class="feature-text">
                                <h4>Verified Listings</h4>
                                <p>All properties are thoroughly verified for authenticity</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <div class="feature-text">
                                <h4>Secure Transactions</h4>
                                <p>Safe and encrypted payment processing</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <div class="feature-text">
                                <h4>24/7 Support</h4>
                                <p>Round-the-clock customer assistance</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="about-stats">
                    <div class="stat-card">
                        <div class="stat-number">10K+</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">5K+</div>
                        <div class="stat-label">Properties Listed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">15K+</div>
                        <div class="stat-label">Successful Matches</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Satisfaction Rate</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <div class="logo-icon">🏠</div>
                        <span>RENTCONNECT</span>
                    </div>
                    <p class="footer-description">Your trusted platform for smart rental solutions. Connecting tenants with landlords seamlessly.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>For Tenants</h3>
                    <ul class="footer-links">
                        <li><a href="#browse">Browse Properties</a></li>
                        <li><a href="#saved">Saved Listings</a></li>
                        <li><a href="#applications">Applications</a></li>
                        <li><a href="#support">Tenant Support</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>For Landlords</h3>
                    <ul class="footer-links">
                        <li><a href="#list">List Property</a></li>
                        <li><a href="#manage">Manage Listings</a></li>
                        <li><a href="#tenants">Find Tenants</a></li>
                        <li><a href="#tools">Landlord Tools</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 RentConnect. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#privacy">Privacy Policy</a>
                    <span>•</span>
                    <a href="#terms">Terms of Service</a>
                    <span>•</span>
                    <a href="#cookies">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Signup Modal -->
    <div id="signupModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSignupModal()">&times;</span>
            <h2>Sign Up</h2>
            
            <form id="signupForm" onsubmit="handleSignup(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="signupFirstName" required placeholder="John">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="signupLastName" required placeholder="Doe">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="signupEmail" required placeholder="john.doe@example.com">
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" id="signupContact" required placeholder="+63 912 345 6789">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="signupRole" required>
                        <option value="">Select your role</option>
                        <option value="tenant">Tenant</option>
                        <option value="landlord">Landlord</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="signupPassword" required placeholder="••••••••">
                        <span class="toggle-password" onclick="togglePassword('signupPassword', this)">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle class="eye-open" cx="12" cy="12" r="3"></circle>
                                <line class="eye-closed" x1="1" y1="1" x2="23" y2="23" style="display:none;"></line>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" required placeholder="••••••••">
                        <span class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle class="eye-open" cx="12" cy="12" r="3"></circle>
                                <line class="eye-closed" x1="1" y1="1" x2="23" y2="23" style="display:none;"></line>
                            </svg>
                        </span>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Create Account</button>
            </form>
        </div>
    </div>

    <!-- Role Login Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeRoleModal()">&times;</span>
            <h2 id="roleModalTitle">Login as Tenant</h2>
            
            <form onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="loginEmail" required placeholder="john.doe@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="loginPassword" required placeholder="••••••••">
                        <span class="toggle-password" onclick="togglePassword('loginPassword', this)">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path class="eye-open" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle class="eye-open" cx="12" cy="12" r="3"></circle>
                                <line class="eye-closed" x1="1" y1="1" x2="23" y2="23" style="display:none;"></line>
                            </svg>
                        </span>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Login</button>
            </form>
        </div>
    </div>

    <script src="assets/scripts/index.js"></script>
</body>
</html>
<?php
// landlord-dashboard.php - Landlord Dashboard
session_start();

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'landlord') {
    header("Location: index.php");
    exit;
}

// Get user info
$userName = $_SESSION['user_name'] ?? 'Landlord';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard - RentConnect</title>
    <link rel="stylesheet" href="assets/styles/landlord-dashboard.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">🏠</div>
                <span class="logo-text">RENTCONNECT</span>
            </div>
            <button class="close-sidebar" onclick="toggleSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">
                <span><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
            </div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($userName); ?></h3>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-item active" onclick="showSection('dashboard')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="#properties" class="nav-item" onclick="showSection('properties')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>My Properties</span>
            </a>

            <a href="#list-property" class="nav-item" onclick="showSection('list-property')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>List Property</span>
            </a>

            <a href="#profile" class="nav-item" onclick="showSection('profile')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profile</span>
            </a>

            <a href="logout.php" class="nav-item logout-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <h1 class="page-title">Dashboard</h1>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd;">🏘️</div>
                    <div class="stat-info">
                        <h3>Total Properties</h3>
                        <p class="stat-number">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">👥</div>
                    <div class="stat-info">
                        <h3>Active Tenants</h3>
                        <p class="stat-number">0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">💰</div>
                    <div class="stat-info">
                        <h3>Monthly Revenue</h3>
                        <p class="stat-number">₱0</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fce4ec;">📊</div>
                    <div class="stat-info">
                        <h3>Occupancy Rate</h3>
                        <p class="stat-number">0%</p>
                    </div>
                </div>
            </div>

            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h2>
                <p>Ready to manage your properties? Start by listing your first property or check your existing listings.</p>
                <button class="btn-primary" onclick="showSection('list-property')">List a Property</button>
            </div>
        </div>

        <!-- My Properties Section -->
        <div id="properties-section" class="content-section">
            <div class="section-header">
                <h2>My Properties</h2>
                <button class="btn-primary" onclick="showSection('list-property')">+ Add New Property</button>
            </div>

            <div id="propertiesList" class="properties-grid">
                <!-- Property cards will be dynamically loaded here -->
            </div>

            <div class="empty-state" id="emptyPropertiesState">
                <div class="empty-icon">🏘️</div>
                <h3>No properties yet</h3>
                <p>Start listing your properties to connect with potential tenants.</p>
                <button class="btn-primary" onclick="showSection('list-property')">List Your First Property</button>
            </div>
        </div>

        <!-- List Property Section -->
        <div id="list-property-section" class="content-section">
            <div class="section-header">
                <h2>List a New Property</h2>
            </div>

            <div class="form-card">
                <form id="propertyForm" onsubmit="handlePropertySubmit(event)">
                    <div class="form-section">
                        <h3>Property Photos</h3>
                        
                        <div class="photo-upload-container">
                            <div class="upload-info">
                                <p>📸 Upload 6-8 photos of your property</p>
                                <small>Recommended: High quality images (JPG, PNG). Max 5MB per image.</small>
                            </div>
                            
                            <div class="photo-grid" id="photoGrid">
                                <!-- Photo preview items will be added here -->
                            </div>
                            
                            <label for="propertyPhotos" class="upload-btn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Add Photos</span>
                                <input type="file" id="propertyPhotos" accept="image/*" multiple style="display: none;" onchange="handlePhotoUpload(event)">
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Property Information</h3>
                        
                        <div class="form-group">
                            <label>Property Title *</label>
                            <input type="text" id="propertyTitle" required placeholder="e.g., Modern 2BR Apartment in Makati">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Property Type *</label>
                                <select id="propertyType" required>
                                    <option value="">Select type</option>
                                    <option value="apartment">Apartment</option>
                                    <option value="compound">Compound</option>
                                    <option value="house">House</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Monthly Rent (₱) *</label>
                                <input type="number" id="propertyRent" required placeholder="15000">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Bedrooms *</label>
                                <input type="number" id="propertyBedrooms" required min="0" placeholder="2">
                            </div>
                            <div class="form-group">
                                <label>Bathrooms *</label>
                                <input type="number" id="propertyBathrooms" required min="0" placeholder="1">
                            </div>
                            <div class="form-group">
                                <label>Floor Area (sqm) *</label>
                                <input type="number" id="propertyArea" required placeholder="50">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Location</h3>
                        
                        <div class="form-group">
                            <label>Search Location *</label>
                            <div class="location-search-container">
                                <input type="text" id="locationSearch" placeholder="Search for a place (e.g., Makati City Hall, BGC)">
                                <button type="button" class="btn-search-location" onclick="searchLocation()">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.35-4.35"></path>
                                    </svg>
                                    Search
                                </button>
                            </div>
                            <small>Search for your property location, then click "Pin Location" to mark it on the map</small>
                        </div>

                        <div class="form-group">
                            <label>Property Location Map *</label>
                            <div id="propertyMap" class="property-map" style="width: 100%; height: 400px;"></div>
                            <button type="button" class="btn-pin-location" onclick="pinCurrentLocation()">
                                📍 Pin This Location
                            </button>
                            <small id="mapCoordinates" style="display: none;">Coordinates: <span id="coordsDisplay"></span></small>
                        </div>
                        
                        <div class="form-group">
                            <label>Full Address *</label>
                            <input type="text" id="propertyAddress" required placeholder="e.g., 123 Main Street, Makati, Metro Manila">
                            <small>This will be displayed on the property listing</small>
                        </div>

                        <input type="hidden" id="propertyLatitude">
                        <input type="hidden" id="propertyLongitude">
                    </div>

                    <div class="form-section">
                        <h3>Description</h3>
                        
                        <div class="form-group">
                            <label>Property Description *</label>
                            <textarea id="propertyDescription" required rows="5" placeholder="Describe your property, amenities, nearby establishments, etc."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="resetPropertyForm()">Cancel</button>
                        <button type="submit" class="btn-primary">List Property</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile-section" class="content-section">
            <div class="section-header">
                <h2>My Profile</h2>
            </div>

            <div class="form-card">
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <span><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($userName); ?></h3>
                        <p class="profile-role">Landlord Account</p>
                    </div>
                </div>

                <form id="profileForm" onsubmit="handleProfileUpdate(event)">
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" id="profileFirstName" value="<?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" id="profileLastName" value="<?php echo htmlspecialchars(explode(' ', $userName)[1] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                            <small>Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" id="profileContact" placeholder="+63 912 345 6789">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Property Preview Modal -->
    <div id="propertyPreviewModal" class="preview-modal">
        <div class="preview-modal-content">
            <button class="preview-modal-close" onclick="closePreviewModal()">&times;</button>
            
            <div class="preview-container">
                <!-- Photo Gallery -->
                <div class="preview-gallery">
                    <div class="preview-main-image">
                        <img id="previewMainImg" src="" alt="Property">
                        <div class="preview-photo-nav">
                            <button class="preview-nav-btn prev" onclick="prevPreviewPhoto()">‹</button>
                            <button class="preview-nav-btn next" onclick="nextPreviewPhoto()">›</button>
                        </div>
                        <div class="preview-photo-counter">
                            <span id="previewPhotoIndex">1</span> / <span id="previewPhotoTotal">1</span>
                        </div>
                    </div>
                    <div class="preview-thumbnails" id="previewThumbnails">
                        <!-- Thumbnails will be inserted here -->
                    </div>
                </div>

                <!-- Property Details -->
                <div class="preview-details">
                    <div class="preview-header">
                        <h2 id="previewTitle">Property Title</h2>
                        <span class="preview-status" id="previewStatus">Available</span>
                    </div>
                    
                    <div class="preview-type" id="previewType">Apartment</div>
                    
                    <div class="preview-price" id="previewPrice">₱15,000/mo</div>
                    
                    <div class="preview-specs">
                        <div class="preview-spec-item">
                            <span class="spec-icon">🛏️</span>
                            <span id="previewBeds">2</span> Bedroom<span id="previewBedsPlural">s</span>
                        </div>
                        <div class="preview-spec-item">
                            <span class="spec-icon">🚿</span>
                            <span id="previewBaths">1</span> Bathroom<span id="previewBathsPlural">s</span>
                        </div>
                        <div class="preview-spec-item">
                            <span class="spec-icon">📐</span>
                            <span id="previewArea">50</span> sqm
                        </div>
                    </div>
                    
                    <div class="preview-section">
                        <h3>📍 Location</h3>
                        <p id="previewAddress">Address will appear here</p>
                        <div id="previewMapContainer" class="preview-map"></div>
                    </div>
                    
                    <div class="preview-section">
                        <h3>📝 Description</h3>
                        <p id="previewDescription">Property description will appear here</p>
                    </div>
                    
                    <div class="preview-notice">
                        <strong>👁️ Preview Mode</strong>
                        <p>This is how tenants will see your property listing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Property Modal -->
    <div id="editPropertyModal" class="edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h2>Edit Property</h2>
                <button class="edit-modal-close" onclick="closeEditModal()">&times;</button>
            </div>

            <form id="editPropertyForm" onsubmit="handlePropertyUpdate(event)">
                <input type="hidden" id="editPropertyId">
                
                <div class="form-section">
                    <h3>Property Information</h3>
                    
                    <div class="form-group">
                        <label>Property Title *</label>
                        <input type="text" id="editPropertyTitle" required placeholder="e.g., Modern 2BR Apartment in Makati">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Property Type *</label>
                            <select id="editPropertyType" required>
                                <option value="">Select type</option>
                                <option value="apartment">Apartment</option>
                                <option value="compound">Compound</option>
                                <option value="house">House</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monthly Rent (₱) *</label>
                            <input type="number" id="editPropertyRent" required placeholder="15000">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Bedrooms *</label>
                            <input type="number" id="editPropertyBedrooms" required min="0" placeholder="2">
                        </div>
                        <div class="form-group">
                            <label>Bathrooms *</label>
                            <input type="number" id="editPropertyBathrooms" required min="0" placeholder="1">
                        </div>
                        <div class="form-group">
                            <label>Floor Area (sqm) *</label>
                            <input type="number" id="editPropertyArea" required placeholder="50">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Location</h3>
                    
                    <div class="form-group">
                        <label>Search Location</label>
                        <div class="location-search-container">
                            <input type="text" id="editLocationSearch" placeholder="Search for a place (e.g., Makati City Hall, BGC)">
                            <button type="button" class="btn-search-location" onclick="searchEditLocation()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Search
                            </button>
                        </div>
                        <small>Search to update property location on the map</small>
                    </div>

                    <div class="form-group">
                        <label>Property Location Map</label>
                        <div id="editPropertyMap" class="property-map" style="width: 100%; height: 300px;"></div>
                        <button type="button" class="btn-pin-location" id="editPinBtn" onclick="pinEditLocation()">
                            📍 Pin This Location
                        </button>
                        <small id="editMapCoordinates" style="display: none;">Coordinates: <span id="editCoordsDisplay"></span></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Address *</label>
                        <input type="text" id="editPropertyAddress" required placeholder="e.g., 123 Main Street, Makati, Metro Manila">
                    </div>

                    <input type="hidden" id="editPropertyLatitude">
                    <input type="hidden" id="editPropertyLongitude">
                </div>

                <div class="form-section">
                    <h3>Description</h3>
                    
                    <div class="form-group">
                        <label>Property Description *</label>
                        <textarea id="editPropertyDescription" required rows="5" placeholder="Describe your property, amenities, nearby establishments, etc."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Status</h3>
                    
                    <div class="form-group">
                        <label>Property Status</label>
                        <select id="editPropertyStatus">
                            <option value="available">Available</option>
                            <option value="rented">Rented</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Property</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/scripts/landlord-dashboard.js"></script>
</body>
</html>
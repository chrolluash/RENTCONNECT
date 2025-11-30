<?php
// tenant-dashboard.php - Tenant Dashboard
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tenant') {
    header("Location: index.php");
    exit;
}

// Get user info
$userName = $_SESSION['user_name'] ?? 'Tenant';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Properties - RentConnect</title>
    <link rel="stylesheet" href="assets/styles/tenant-dashboard.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="topnav">
        <div class="nav-left">
            <div class="logo">
                <div class="logo-icon">🏠</div>
                <span class="logo-text">RENTCONNECT</span>
            </div>
        </div>
        
        <div class="nav-center">
            <div class="search-bar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="searchInput" placeholder="Search properties by location, type, or price...">
            </div>
        </div>
        
        <div class="nav-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <span><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                </div>
                <div class="user-dropdown">
                    <div class="dropdown-header">
                        <h3><?php echo htmlspecialchars($userName); ?></h3>
                        <p><?php echo htmlspecialchars($userEmail); ?></p>
                    </div>
                    <div class="dropdown-menu">
                        <a href="#" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            My Profile
                        </a>
                        <a href="#" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            </svg>
                            My Applications
                        </a>
                        <a href="#" class="dropdown-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Saved Properties
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item logout">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            <!-- Header Section -->
            <div class="page-header">
                <div>
                    <h1>Find Your Perfect Home</h1>
                    <p>Browse available properties and find your next place to call home</p>
                </div>
                <div class="filter-controls">
                    <button class="filter-btn" onclick="toggleFilters()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Filters
                    </button>
                </div>
            </div>

            <!-- Filters Panel -->
            <div id="filtersPanel" class="filters-panel">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Property Type</label>
                        <select id="filterType">
                            <option value="">All Types</option>
                            <option value="apartment">Apartment</option>
                            <option value="compound">Compound</option>
                            <option value="house">House</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Min Price (₱)</label>
                        <input type="number" id="filterMinPrice" placeholder="e.g., 5000">
                    </div>
                    <div class="filter-group">
                        <label>Max Price (₱)</label>
                        <input type="number" id="filterMaxPrice" placeholder="e.g., 20000">
                    </div>
                    <div class="filter-group">
                        <label>Bedrooms</label>
                        <select id="filterBedrooms">
                            <option value="">Any</option>
                            <option value="1">1+</option>
                            <option value="2">2+</option>
                            <option value="3">3+</option>
                            <option value="4">4+</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button class="btn-apply-filters" onclick="applyFilters()">Apply Filters</button>
                    </div>
                </div>
            </div>

            <!-- Properties Grid -->
            <div id="propertiesContainer" class="properties-container">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading properties...</p>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">🏘️</div>
                <h3>No properties found</h3>
                <p>Try adjusting your filters or search criteria</p>
            </div>
        </div>
    </main>

    <!-- Property Details Modal -->
    <div id="propertyModal" class="property-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closePropertyModal()">&times;</button>
            
            <div class="modal-container">
                <!-- Gallery Section -->
                <div class="modal-gallery">
                    <div class="gallery-main">
                        <img id="modalMainImg" src="" alt="Property">
                        <div class="gallery-nav">
                            <button class="gallery-nav-btn prev" onclick="prevModalPhoto()">‹</button>
                            <button class="gallery-nav-btn next" onclick="nextModalPhoto()">›</button>
                        </div>
                        <div class="gallery-counter">
                            <span id="modalPhotoIndex">1</span> / <span id="modalPhotoTotal">1</span>
                        </div>
                    </div>
                    <div class="gallery-thumbnails" id="modalThumbnails"></div>
                </div>

                <!-- Details Section -->
                <div class="modal-details">
                    <div class="modal-header">
                        <div>
                            <h2 id="modalTitle">Property Title</h2>
                            <span class="modal-type" id="modalType">Apartment</span>
                        </div>
                        <button class="btn-save-property">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Save
                        </button>
                    </div>
                    
                    <div class="modal-price" id="modalPrice">₱15,000/mo</div>
                    
                    <div class="modal-specs">
                        <div class="spec-item">
                            <span class="spec-icon">🛏️</span>
                            <span id="modalBeds">2</span> Bedroom<span id="modalBedsPlural">s</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-icon">🚿</span>
                            <span id="modalBaths">1</span> Bathroom<span id="modalBathsPlural">s</span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-icon">📐</span>
                            <span id="modalArea">50</span> sqm
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3>📍 Location</h3>
                        <p id="modalAddress">Address will appear here</p>
                        <div id="modalMapContainer" class="modal-map"></div>
                    </div>
                    
                    <div class="modal-section">
                        <h3>📝 Description</h3>
                        <p id="modalDescription">Property description will appear here</p>
                    </div>
                    
                    <div class="modal-section landlord-info-section">
                        <h3>👤 Property Owner</h3>
                        <div id="modalLandlordInfo" class="landlord-info-box">
                            <p>Loading landlord information...</p>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button class="btn-contact-landlord">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Contact Landlord
                        </button>
                        <button class="btn-apply">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Apply Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/scripts/tenant-dashboard.js"></script>
</body>
</html>
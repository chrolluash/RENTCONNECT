// tenant-dashboard.js - Tenant Dashboard JavaScript

let allProperties = [];
let filteredProperties = [];
let currentModalPhotos = [];
let currentModalIndex = 0;
let modalMap = null;

// ==================== HELPER FUNCTION FOR PHOTO PATHS ====================

function getPhotoUrl(photoPath) {
    if (!photoPath) {
        return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2248%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';
    }
    
    let cleanPath = String(photoPath).trim();
    
    // Remove everything after a colon
    if (cleanPath.includes(':')) {
        cleanPath = cleanPath.split(':')[0];
    }
    
    // If it's already a full HTTP URL, return it
    if (cleanPath.startsWith('http://') || cleanPath.startsWith('https://')) {
        return cleanPath;
    }
    
    // Remove leading slash if present
    if (cleanPath.startsWith('/')) {
        cleanPath = cleanPath.substring(1);
    }
    
    // Build the correct path
    let finalPath;
    if (cleanPath.includes('RENTCONNECT(2)')) {
        finalPath = '/' + cleanPath;
    } else if (cleanPath.startsWith('uploads/')) {
        finalPath = '/RENTCONNECT(2)/' + cleanPath;
    } else {
        finalPath = '/RENTCONNECT(2)/uploads/properties/' + cleanPath;
    }
    
    return finalPath;
}

// ==================== LOAD PROPERTIES ====================

async function loadProperties() {
    const container = document.getElementById('propertiesContainer');
    const emptyState = document.getElementById('emptyState');
    
    try {
        // Show loading
        container.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Loading properties...</p>
            </div>
        `;
        
        console.log('🔍 Fetching properties from API...');
        
        // Fetch all available properties - using landlord_f folder (we know this exists)
        const response = await fetch('functions/landlord_f/get_all_properties.php');
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response OK:', response.ok);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Response error:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('📄 Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON parse error:', parseError);
            console.error('Response was:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        console.log('✅ Parsed result:', result);
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to load properties');
        }
        
        allProperties = result.properties || [];
        console.log('📦 Total available properties loaded:', allProperties.length);
        
        filteredProperties = [...allProperties];
        
        renderProperties();
        
    } catch (error) {
        console.error('❌ Error loading properties:', error);
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">❌</div>
                <h3>Failed to load properties</h3>
                <p>${error.message}</p>
                <p style="font-size: 12px; opacity: 0.7; margin-top: 10px;">Check console for details</p>
            </div>
        `;
    }
}

// ==================== RENDER PROPERTIES ====================

function renderProperties() {
    const container = document.getElementById('propertiesContainer');
    const emptyState = document.getElementById('emptyState');
    
    console.log('🎨 renderProperties called with', filteredProperties.length, 'properties');
    
    container.innerHTML = '';
    
    if (filteredProperties.length === 0) {
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    filteredProperties.forEach((property, index) => {
        console.log(`📝 Creating card ${index + 1}/${filteredProperties.length} for:`, property.title);
        const card = createPropertyCard(property);
        container.appendChild(card);
    });
    
    console.log('✅ All cards rendered');
}

function createPropertyCard(property) {
    const card = document.createElement('div');
    card.className = 'property-card';
    card.onclick = () => openPropertyModal(property.id);
    
    // Get first photo or placeholder
    const photoUrl = property.photos && property.photos.length > 0 
        ? getPhotoUrl(property.photos[0])
        : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2248%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';
    
    card.innerHTML = `
        <div class="property-image-container">
            <img src="${photoUrl}" alt="${property.title}" class="property-image">
            <span class="property-badge">${capitalizeFirst(property.type)}</span>
            <button class="property-save-btn" onclick="event.stopPropagation(); saveProperty(${property.id})">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                </svg>
            </button>
        </div>
        <div class="property-content">
            <h3 class="property-title">${property.title}</h3>
            <div class="property-price">₱${parseInt(property.rent).toLocaleString()}/mo</div>
            <div class="property-specs">
                <span class="spec-item">🛏️ ${property.bedrooms} Bed${property.bedrooms > 1 ? 's' : ''}</span>
                <span class="spec-item">🚿 ${property.bathrooms} Bath${property.bathrooms > 1 ? 's' : ''}</span>
                <span class="spec-item">📐 ${property.area} sqm</span>
            </div>
            <div class="property-location">📍 ${property.address}</div>
        </div>
    `;
    
    return card;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ==================== PROPERTY MODAL ====================

function openPropertyModal(propertyId) {
    const property = allProperties.find(p => p.id === propertyId);
    if (!property) {
        alert('Property not found');
        return;
    }
    
    // Populate modal
    document.getElementById('modalTitle').textContent = property.title;
    document.getElementById('modalType').textContent = capitalizeFirst(property.type);
    document.getElementById('modalPrice').textContent = `₱${parseInt(property.rent).toLocaleString()}/mo`;
    document.getElementById('modalBeds').textContent = property.bedrooms;
    document.getElementById('modalBedsPlural').textContent = property.bedrooms > 1 ? 's' : '';
    document.getElementById('modalBaths').textContent = property.bathrooms;
    document.getElementById('modalBathsPlural').textContent = property.bathrooms > 1 ? 's' : '';
    document.getElementById('modalArea').textContent = property.area;
    document.getElementById('modalAddress').textContent = property.address;
    document.getElementById('modalDescription').textContent = property.description;
    
    // Set landlord info
    const landlordInfo = document.getElementById('modalLandlordInfo');
    if (landlordInfo) {
        landlordInfo.innerHTML = `
            <strong>Property Owner:</strong> ${property.landlordName || 'Property Owner'}
            ${property.landlordContact ? `<br><strong>Contact:</strong> ${property.landlordContact}` : ''}
        `;
    }
    
    // Load photos
    currentModalPhotos = property.photos || [];
    currentModalIndex = 0;
    
    if (currentModalPhotos.length > 0) {
        loadModalPhoto(0);
        loadModalThumbnails();
    } else {
        document.getElementById('modalMainImg').src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22800%22 height=%22500%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2272%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';
        document.getElementById('modalPhotoIndex').textContent = '0';
        document.getElementById('modalPhotoTotal').textContent = '0';
        document.getElementById('modalThumbnails').innerHTML = '';
    }
    
    // Show modal
    document.getElementById('propertyModal').classList.add('active');
    
    // Initialize map
    setTimeout(() => {
        initializeModalMap(property.latitude, property.longitude);
    }, 300);
}

function loadModalPhoto(index) {
    if (index < 0 || index >= currentModalPhotos.length) return;
    
    currentModalIndex = index;
    const photoUrl = getPhotoUrl(currentModalPhotos[index]);
    
    document.getElementById('modalMainImg').src = photoUrl;
    document.getElementById('modalPhotoIndex').textContent = index + 1;
    document.getElementById('modalPhotoTotal').textContent = currentModalPhotos.length;
    
    // Update active thumbnail
    document.querySelectorAll('.gallery-thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

function loadModalThumbnails() {
    const container = document.getElementById('modalThumbnails');
    container.innerHTML = '';
    
    currentModalPhotos.forEach((photo, index) => {
        const photoUrl = getPhotoUrl(photo);
        const thumb = document.createElement('div');
        thumb.className = `gallery-thumbnail ${index === 0 ? 'active' : ''}`;
        thumb.innerHTML = `<img src="${photoUrl}" alt="Thumbnail ${index + 1}">`;
        thumb.onclick = () => loadModalPhoto(index);
        container.appendChild(thumb);
    });
}

function prevModalPhoto() {
    const newIndex = currentModalIndex - 1;
    if (newIndex >= 0) {
        loadModalPhoto(newIndex);
    } else {
        loadModalPhoto(currentModalPhotos.length - 1);
    }
}

function nextModalPhoto() {
    const newIndex = currentModalIndex + 1;
    if (newIndex < currentModalPhotos.length) {
        loadModalPhoto(newIndex);
    } else {
        loadModalPhoto(0);
    }
}

function initializeModalMap(latitude, longitude) {
    const mapContainer = document.getElementById('modalMapContainer');
    
    if (!mapContainer) return;
    
    // Remove existing map
    if (modalMap) {
        modalMap.remove();
        modalMap = null;
    }
    
    if (!latitude || !longitude) {
        mapContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--border-color); border-radius: 12px;"><p>📍 Location not specified</p></div>';
        return;
    }
    
    try {
        modalMap = L.map('modalMapContainer').setView([latitude, longitude], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(modalMap);
        
        L.marker([latitude, longitude]).addTo(modalMap);
        
        setTimeout(() => {
            modalMap.invalidateSize();
        }, 100);
    } catch (error) {
        console.error('Modal map error:', error);
    }
}

function closePropertyModal() {
    document.getElementById('propertyModal').classList.remove('active');
    
    if (modalMap) {
        modalMap.remove();
        modalMap = null;
    }
}

// ==================== FILTERS ====================

function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    panel.classList.toggle('active');
}

function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const type = document.getElementById('filterType').value;
    const minPrice = parseFloat(document.getElementById('filterMinPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('filterMaxPrice').value) || Infinity;
    const minBedrooms = parseInt(document.getElementById('filterBedrooms').value) || 0;
    
    filteredProperties = allProperties.filter(property => {
        // Search filter
        const matchesSearch = !searchTerm || 
            property.title.toLowerCase().includes(searchTerm) ||
            property.address.toLowerCase().includes(searchTerm) ||
            property.type.toLowerCase().includes(searchTerm);
        
        // Type filter
        const matchesType = !type || property.type === type;
        
        // Price filter
        const matchesPrice = property.rent >= minPrice && property.rent <= maxPrice;
        
        // Bedrooms filter
        const matchesBedrooms = property.bedrooms >= minBedrooms;
        
        return matchesSearch && matchesType && matchesPrice && matchesBedrooms;
    });
    
    renderProperties();
}

// ==================== SEARCH ====================

document.addEventListener('DOMContentLoaded', () => {
    loadProperties();
    
    // Search on input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
});

// ==================== SAVE PROPERTY ====================

function saveProperty(propertyId) {
    // TODO: Implement save functionality
    alert(`Property ${propertyId} saved to favorites!`);
}

// Make functions globally accessible
window.toggleFilters = toggleFilters;
window.applyFilters = applyFilters;
window.openPropertyModal = openPropertyModal;
window.closePropertyModal = closePropertyModal;
window.prevModalPhoto = prevModalPhoto;
window.nextModalPhoto = nextModalPhoto;
window.saveProperty = saveProperty; 
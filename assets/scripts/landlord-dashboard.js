// ==================== GLOBAL VARIABLES ====================

let uploadedPhotos = []; // Store uploaded photo files
let properties = []; // Store all properties
let propertyMap = null; // Leaflet map instance
let propertyMarker = null; // Map marker
let currentMapCenter = { lat: 14.3832, lng: 121.0409 }; // Default: Muntinlupa City
let previewMap = null; // Preview modal map
let editMap = null; // Edit modal map
let editMarker = null; // Edit map marker
let editMapCenter = { lat: 14.3832, lng: 121.0409 }; // Edit map center
let currentPreviewPhotos = []; // Current property photos in preview
let currentPreviewIndex = 0; // Current photo index in preview

// ==================== HELPER FUNCTION FOR PHOTO PATHS ====================

function getPhotoUrl(photoPath) {
    if (!photoPath) {
        console.warn('Empty photo path provided');
        return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2248%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';
    }
    
    console.log('🔍 DEBUG - Original path:', photoPath);
    
    // Convert to string and trim
    let cleanPath = String(photoPath).trim();
    
    // **Remove EVERYTHING after a colon (including :1)**
    if (cleanPath.includes(':')) {
        cleanPath = cleanPath.split(':')[0];
        console.log('⚠️ Removed colon suffix, new path:', cleanPath);
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
    
    console.log('✅ Final URL:', finalPath);
    return finalPath;
}

// ==================== SIDEBAR TOGGLE ====================

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('open');
    sidebar.classList.toggle('closed');
    mainContent.classList.toggle('expanded');
}

// ==================== SECTION NAVIGATION ====================

function showSection(sectionName) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.classList.remove('active'));
    
    // Show selected section
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Initialize map when showing list property section
    if (sectionName === 'list-property') {
        // Wait for DOM to fully render the section
        setTimeout(() => {
            const mapContainer = document.getElementById('propertyMap');
            if (mapContainer) {
                initializeMap();
            } else {
                console.error('Map container still not found after delay');
            }
        }, 200);
    }
    
    // Update page title
    const titles = {
        'dashboard': 'Dashboard',
        'properties': 'My Properties',
        'list-property': 'List a Property',
        'profile': 'My Profile'
    };
    
    const pageTitle = document.querySelector('.page-title');
    if (pageTitle && titles[sectionName]) {
        pageTitle.textContent = titles[sectionName];
    }
    
    // Update active nav item
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    const activeNav = document.querySelector(`.nav-item[onclick*="${sectionName}"]`);
    if (activeNav) {
        activeNav.classList.add('active');
    }
    
    // Close sidebar on mobile after navigation
    if (window.innerWidth <= 1024) {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.remove('open');
        sidebar.classList.add('closed');
    }
}

// ==================== MAP FUNCTIONS ====================

function initializeMap() {
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded!');
        alert('Map library failed to load. Please refresh the page.');
        return;
    }
    
    // Check if map container exists
    const mapContainer = document.getElementById('propertyMap');
    if (!mapContainer) {
        console.error('Map container not found!');
        return;
    }
    
    // Remove existing map if any
    if (propertyMap) {
        propertyMap.remove();
        propertyMap = null;
    }
    
    try {
        // Initialize map centered on Muntinlupa
        propertyMap = L.map('propertyMap').setView([currentMapCenter.lat, currentMapCenter.lng], 13);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(propertyMap);
        
        // Add click event to map
        propertyMap.on('click', function(e) {
            const { lat, lng } = e.latlng;
            moveMarker(lat, lng);
        });
        
        // Force map to resize properly
        setTimeout(() => {
            propertyMap.invalidateSize();
        }, 100);
        
        console.log('✅ Map initialized successfully');
    } catch (error) {
        console.error('Map initialization error:', error);
        alert('Failed to initialize map. Please refresh the page.');
    }
}

function searchLocation() {
    const searchQuery = document.getElementById('locationSearch').value.trim();
    
    if (!searchQuery) {
        alert('⚠️ Please enter a location to search');
        return;
    }
    
    // Show loading
    const searchBtn = document.querySelector('.btn-search-location');
    const originalText = searchBtn.innerHTML;
    searchBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Searching...';
    searchBtn.disabled = true;
    
    // Use our PHP proxy to avoid CORS issues
    const url = `/RENTCONNECT(2)/functions/landlord_f/geocode.php?q=${encodeURIComponent(searchQuery)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data && result.data.length > 0) {
                const location = result.data[0];
                const lat = parseFloat(location.lat);
                const lng = parseFloat(location.lon);
                
                // Move map to location
                propertyMap.setView([lat, lng], 16);
                currentMapCenter = { lat, lng };
                
                // Add/move marker
                moveMarker(lat, lng);
                
                // Update address field with found location
                if (location.display_name) {
                    document.getElementById('propertyAddress').value = location.display_name;
                }
                
                alert('✅ Location found! Click "Pin This Location" to confirm.');
            } else {
                alert('❌ Location not found. Please try a different search term.');
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
            alert('❌ Failed to search location. Please try again.');
        })
        .finally(() => {
            searchBtn.innerHTML = originalText;
            searchBtn.disabled = false;
        });
}

function moveMarker(lat, lng) {
    if (propertyMarker) {
        propertyMarker.setLatLng([lat, lng]);
    } else {
        propertyMarker = L.marker([lat, lng], {
            draggable: true
        }).addTo(propertyMap);
        
        // Add drag event
        propertyMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateCoordinates(pos.lat, pos.lng);
        });
    }
    
    updateCoordinates(lat, lng);
}

function updateCoordinates(lat, lng) {
    currentMapCenter = { lat, lng };
    document.getElementById('coordsDisplay').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('mapCoordinates').classList.add('show');
}

function pinCurrentLocation() {
    if (!propertyMarker) {
        alert('⚠️ Please search for a location first or click on the map to place a marker');
        return;
    }
    
    const { lat, lng } = currentMapCenter;
    
    // Save coordinates to hidden fields
    document.getElementById('propertyLatitude').value = lat;
    document.getElementById('propertyLongitude').value = lng;
    
    // Update button style
    const pinBtn = document.querySelector('.btn-pin-location');
    pinBtn.classList.add('pinned');
    pinBtn.innerHTML = '✅ Location Pinned!';
    
    setTimeout(() => {
        pinBtn.innerHTML = '📍 Pin This Location';
    }, 2000);
    
    alert('✅ Location pinned successfully!');
}

// ==================== PHOTO UPLOAD HANDLING ====================

function handlePhotoUpload(event) {
    const files = Array.from(event.target.files);
    
    // Limit to 8 photos total
    const remainingSlots = 8 - uploadedPhotos.length;
    if (remainingSlots <= 0) {
        alert('⚠️ Maximum 8 photos allowed');
        return;
    }
    
    const filesToAdd = files.slice(0, remainingSlots);
    
    // Validate each file
    for (const file of filesToAdd) {
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(`⚠️ ${file.name} is too large. Maximum file size is 5MB.`);
            continue;
        }
        
        // Check file type
        if (!file.type.startsWith('image/')) {
            alert(`⚠️ ${file.name} is not an image file.`);
            continue;
        }
        
        uploadedPhotos.push(file);
    }
    
    renderPhotoGrid();
    event.target.value = ''; // Reset input
}

function renderPhotoGrid() {
    const photoGrid = document.getElementById('photoGrid');
    photoGrid.innerHTML = '';
    
    uploadedPhotos.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const photoItem = document.createElement('div');
            photoItem.className = 'photo-item';
            photoItem.innerHTML = `
                <img src="${e.target.result}" alt="Property photo ${index + 1}">
                <button type="button" class="photo-remove" onclick="removePhoto(${index})" title="Remove photo">×</button>
            `;
            photoGrid.appendChild(photoItem);
        };
        reader.readAsDataURL(file);
    });
}

function removePhoto(index) {
    uploadedPhotos.splice(index, 1);
    renderPhotoGrid();
}

// ==================== PROPERTY FORM HANDLING ====================

async function handlePropertySubmit(event) {
    event.preventDefault();
    
    // Validate photos
    if (uploadedPhotos.length < 1) {
        alert('⚠️ Please upload at least 1 photo');
        return;
    }
    
    if (uploadedPhotos.length < 6) {
        if (!confirm(`You've only uploaded ${uploadedPhotos.length} photo(s). We recommend 6-8 photos for better visibility. Continue anyway?`)) {
            return;
        }
    }
    
    // Validate location pin
    const latitude = document.getElementById('propertyLatitude').value;
    const longitude = document.getElementById('propertyLongitude').value;
    
    if (!latitude || !longitude) {
        alert('⚠️ Please pin your property location on the map');
        return;
    }
    
    // Get form values
    const formData = new FormData();
    formData.append('title', document.getElementById('propertyTitle').value.trim());
    formData.append('type', document.getElementById('propertyType').value);
    formData.append('rent', document.getElementById('propertyRent').value);
    formData.append('bedrooms', document.getElementById('propertyBedrooms').value);
    formData.append('bathrooms', document.getElementById('propertyBathrooms').value);
    formData.append('area', document.getElementById('propertyArea').value);
    formData.append('address', document.getElementById('propertyAddress').value.trim());
    formData.append('description', document.getElementById('propertyDescription').value.trim());
    formData.append('latitude', latitude);
    formData.append('longitude', longitude);
    
    // Append photos
    uploadedPhotos.forEach((photo, index) => {
        formData.append('photos[]', photo);
    });
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Listing Property...';
    submitBtn.disabled = true;
    
    try {
        // Send to backend API
        const response = await fetch('/RENTCONNECT(2)/functions/landlord_f/save_property.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        if (result.success) {
            alert('✅ Property listed successfully!');
            resetPropertyForm();
            showSection('properties');
            loadProperties(); // Refresh the properties list from database
        } else {
            alert(`❌ Failed to list property: ${result.message}`);
        }
        
    } catch (error) {
        console.error('Property listing error:', error);
        alert('❌ Failed to list property. Please try again.');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

function resetPropertyForm() {
    document.getElementById('propertyForm').reset();
    uploadedPhotos = [];
    renderPhotoGrid();
    
    // Reset map
    if (propertyMarker) {
        propertyMap.removeLayer(propertyMarker);
        propertyMarker = null;
    }
    document.getElementById('propertyLatitude').value = '';
    document.getElementById('propertyLongitude').value = '';
    document.getElementById('mapCoordinates').classList.remove('show');
    document.querySelector('.btn-pin-location').classList.remove('pinned');
    
    // Reset map view to Muntinlupa
    if (propertyMap) {
        propertyMap.setView([14.3832, 121.0409], 13);
    }
}

// ==================== LOAD AND DISPLAY PROPERTIES ====================

async function loadProperties() {
    const propertiesList = document.getElementById('propertiesList');
    const emptyState = document.getElementById('emptyPropertiesState');
    
    try {
        // Fetch properties from database
        const response = await fetch('/RENTCONNECT(2)/functions/landlord_f/get_properties.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        properties = result.properties || [];
        
        if (properties.length === 0) {
            propertiesList.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }
        
        propertiesList.style.display = 'grid';
        emptyState.style.display = 'none';
        propertiesList.innerHTML = '';
        
        properties.forEach(property => {
            const card = createPropertyCard(property);
            propertiesList.appendChild(card);
        });
        
    } catch (error) {
        console.error('Error loading properties:', error);
        propertiesList.style.display = 'none';
        emptyState.style.display = 'block';
    }
}

function createPropertyCard(property) {
    const card = document.createElement('div');
    card.className = 'property-card';
    
    // Create images section
    let imagesHTML = '';
    if (property.photos && property.photos.length > 0) {
        imagesHTML = `
            <div class="property-card-images" onclick="viewPropertyDetails(${property.id})">
                <div class="images-scroll-container">
                    ${property.photos.map((photo, index) => {
                        const photoUrl = getPhotoUrl(photo);  // ✅ Use the helper
                        return `
                            <div class="property-image-item">
                                <img src="${photoUrl}" 
                                     alt="${property.title} - Photo ${index + 1}" 
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2248%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';">
                            </div>
                        `;
                    }).join('')}
                </div>
                <div class="image-counter">📸 ${property.photos.length} photo${property.photos.length > 1 ? 's' : ''}</div>
            </div>
        `;
    } else {
        // Default placeholder if no photos
        imagesHTML = `
            <div class="property-card-images" onclick="viewPropertyDetails(${property.id})">
                <div class="images-scroll-container">
                    <div class="property-image-item" style="display: flex; align-items: center; justify-content: center; background: var(--border-color);">
                        <span style="font-size: 48px; opacity: 0.3;">🏠</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    card.innerHTML = `
        ${imagesHTML}
        <div class="property-card-content">
            <div class="property-card-header" onclick="viewPropertyDetails(${property.id})" style="cursor: pointer;">
                <div>
                    <h3 class="property-card-title">${property.title}</h3>
                    <span class="property-card-type">${capitalizeFirst(property.type)}</span>
                </div>
            </div>
            <div class="property-card-price" onclick="viewPropertyDetails(${property.id})" style="cursor: pointer;">₱${parseInt(property.rent).toLocaleString()}/mo</div>
            <div class="property-card-details" onclick="viewPropertyDetails(${property.id})" style="cursor: pointer;">
                <span class="property-detail">🛏️ ${property.bedrooms} Bed${property.bedrooms > 1 ? 's' : ''}</span>
                <span class="property-detail">🚿 ${property.bathrooms} Bath${property.bathrooms > 1 ? 's' : ''}</span>
                <span class="property-detail">📐 ${property.area} sqm</span>
            </div>
            <div class="property-card-location" onclick="viewPropertyDetails(${property.id})" style="cursor: pointer;">📍 ${property.address}</div>
            <div class="property-card-actions">
                <button class="btn-edit" onclick="event.stopPropagation(); editProperty(${property.id})">✏️ Edit</button>
                <button class="btn-delete" onclick="event.stopPropagation(); deleteProperty(${property.id})">🗑️ Delete</button>
            </div>
        </div>
    `;
    
    return card;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function viewPropertyDetails(propertyId) {
    console.log('Opening preview for property:', propertyId);
    
    const property = properties.find(p => p.id === propertyId);
    if (!property) {
        console.error('Property not found:', propertyId);
        alert('Property not found');
        return;
    }
    
    console.log('Property found:', property);
    
    // Populate preview modal
    document.getElementById('previewTitle').textContent = property.title;
    document.getElementById('previewType').textContent = capitalizeFirst(property.type);
    document.getElementById('previewPrice').textContent = `₱${parseInt(property.rent).toLocaleString()}/mo`;
    document.getElementById('previewBeds').textContent = property.bedrooms;
    document.getElementById('previewBedsPlural').textContent = property.bedrooms > 1 ? 's' : '';
    document.getElementById('previewBaths').textContent = property.bathrooms;
    document.getElementById('previewBathsPlural').textContent = property.bathrooms > 1 ? 's' : '';
    document.getElementById('previewArea').textContent = property.area;
    document.getElementById('previewAddress').textContent = property.address;
    document.getElementById('previewDescription').textContent = property.description;
    
    // Set status badge
    const statusBadge = document.getElementById('previewStatus');
    const status = property.status || 'available';
    statusBadge.textContent = capitalizeFirst(status);
    statusBadge.className = `preview-status ${status}`;
    
    // Load photos
    currentPreviewPhotos = property.photos || [];
    currentPreviewIndex = 0;
    
    console.log('Loading photos:', currentPreviewPhotos.length);
    
    if (currentPreviewPhotos.length > 0) {
        loadPreviewPhoto(0);
        loadPreviewThumbnails();
    } else {
        // Show placeholder
        document.getElementById('previewMainImg').src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22800%22 height=%22500%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2272%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';
        document.getElementById('previewPhotoIndex').textContent = '0';
        document.getElementById('previewPhotoTotal').textContent = '0';
        document.getElementById('previewThumbnails').innerHTML = '';
    }
    
    // Show modal FIRST
    const modal = document.getElementById('propertyPreviewModal');
    if (modal) {
        modal.classList.add('active');
        console.log('Modal opened');
    } else {
        console.error('Modal element not found!');
    }
    
    // Initialize preview map after modal is visible
    setTimeout(() => {
        initializePreviewMap(property.latitude, property.longitude);
    }, 300);
}

function loadPreviewPhoto(index) {
    if (index < 0 || index >= currentPreviewPhotos.length) return;
    
    currentPreviewIndex = index;
    const photoUrl = getPhotoUrl(currentPreviewPhotos[index]);  // ✅ Use the helper
    
    document.getElementById('previewMainImg').src = photoUrl;
    document.getElementById('previewPhotoIndex').textContent = index + 1;
    document.getElementById('previewPhotoTotal').textContent = currentPreviewPhotos.length;
    
    // Update active thumbnail
    document.querySelectorAll('.preview-thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

function loadPreviewThumbnails() {
    const container = document.getElementById('previewThumbnails');
    container.innerHTML = '';
    
    currentPreviewPhotos.forEach((photo, index) => {
        const photoUrl = getPhotoUrl(photo);  // ✅ Use the helper
        const thumb = document.createElement('div');
        thumb.className = `preview-thumbnail ${index === 0 ? 'active' : ''}`;
        thumb.innerHTML = `<img src="${photoUrl}" alt="Thumbnail ${index + 1}">`;
        thumb.onclick = () => loadPreviewPhoto(index);
        container.appendChild(thumb);
    });
}

function prevPreviewPhoto() {
    const newIndex = currentPreviewIndex - 1;
    if (newIndex >= 0) {
        loadPreviewPhoto(newIndex);
    } else {
        loadPreviewPhoto(currentPreviewPhotos.length - 1); // Loop to last
    }
}

function nextPreviewPhoto() {
    const newIndex = currentPreviewIndex + 1;
    if (newIndex < currentPreviewPhotos.length) {
        loadPreviewPhoto(newIndex);
    } else {
        loadPreviewPhoto(0); // Loop to first
    }
}

function initializePreviewMap(latitude, longitude) {
    const mapContainer = document.getElementById('previewMapContainer');
    
    if (!mapContainer) return;
    
    // Remove existing map
    if (previewMap) {
        previewMap.remove();
        previewMap = null;
    }
    
    // Check if coordinates exist
    if (!latitude || !longitude) {
        mapContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--border-color); border-radius: 12px;"><p style="color: var(--text-primary); opacity: 0.5;">📍 Location not specified</p></div>';
        return;
    }
    
    try {
        // Initialize map
        previewMap = L.map('previewMapContainer').setView([latitude, longitude], 15);
        
        // Add tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(previewMap);
        
        // Add marker
        L.marker([latitude, longitude]).addTo(previewMap);
        
        // Fix map display
        setTimeout(() => {
            previewMap.invalidateSize();
        }, 100);
    } catch (error) {
        console.error('Preview map error:', error);
    }
}

function closePreviewModal() {
    document.getElementById('propertyPreviewModal').classList.remove('active');
    
    // Clean up preview map
    if (previewMap) {
        previewMap.remove();
        previewMap = null;
    }
}

function editProperty(propertyId) {
    const property = properties.find(p => p.id === propertyId);
    if (!property) {
        alert('Property not found');
        return;
    }
    
    // Populate edit form with property data
    document.getElementById('editPropertyId').value = property.id;
    document.getElementById('editPropertyTitle').value = property.title;
    document.getElementById('editPropertyType').value = property.type;
    document.getElementById('editPropertyRent').value = property.rent;
    document.getElementById('editPropertyBedrooms').value = property.bedrooms;
    document.getElementById('editPropertyBathrooms').value = property.bathrooms;
    document.getElementById('editPropertyArea').value = property.area;
    document.getElementById('editPropertyAddress').value = property.address;
    document.getElementById('editPropertyDescription').value = property.description;
    document.getElementById('editPropertyStatus').value = property.status || 'available';
    document.getElementById('editPropertyLatitude').value = property.latitude || '';
    document.getElementById('editPropertyLongitude').value = property.longitude || '';
    
    // Open modal
    document.getElementById('editPropertyModal').classList.add('active');
    
    // Initialize edit map after modal opens
    setTimeout(() => {
        initializeEditMap(property.latitude, property.longitude);
    }, 300);
}

function initializeEditMap(latitude, longitude) {
    const mapContainer = document.getElementById('editPropertyMap');
    if (!mapContainer) {
        console.error('Edit map container not found');
        return;
    }
    
    // Remove existing map
    if (editMap) {
        editMap.remove();
        editMap = null;
    }
    
    // Use existing coordinates or default to Muntinlupa
    const lat = latitude || 14.3832;
    const lng = longitude || 121.0409;
    editMapCenter = { lat, lng };
    
    try {
        editMap = L.map('editPropertyMap').setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(editMap);
        
        // Add existing marker if coordinates exist
        if (latitude && longitude) {
            editMarker = L.marker([lat, lng], { draggable: true }).addTo(editMap);
            
            editMarker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updateEditCoordinates(pos.lat, pos.lng);
            });
            
            updateEditCoordinates(lat, lng);
            document.getElementById('editPinBtn').classList.add('pinned');
            document.getElementById('editPinBtn').innerHTML = '✅ Location Pinned';
        }
        
        // Add click event
        editMap.on('click', function(e) {
            const { lat, lng } = e.latlng;
            moveEditMarker(lat, lng);
        });
        
        setTimeout(() => {
            editMap.invalidateSize();
        }, 100);
        
        console.log('✅ Edit map initialized');
    } catch (error) {
        console.error('Edit map error:', error);
    }
}

function searchEditLocation() {
    const searchQuery = document.getElementById('editLocationSearch').value.trim();
    
    if (!searchQuery) {
        alert('⚠️ Please enter a location to search');
        return;
    }

    // Show loading
    const searchBtn = document.querySelector('.btn-search-location');
    const originalText = searchBtn.innerHTML;
    searchBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Searching...';
    searchBtn.disabled = true;
    
    // Use our PHP proxy to avoid CORS issues
    const url = `/RENTCONNECT(2)/functions/landlord_f/geocode.php?q=${encodeURIComponent(searchQuery)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data && result.data.length > 0) {
                const location = result.data[0];
                const lat = parseFloat(location.lat);
                const lng = parseFloat(location.lon);
                
                // Move map to location
                propertyMap.setView([lat, lng], 16);
                currentMapCenter = { lat, lng };
                
                // Add/move marker
                moveMarker(lat, lng);
                
                // Update address field with found location
                if (location.display_name) {
                    document.getElementById('propertyAddress').value = location.display_name;
                }
                
                alert('✅ Location found! Click "Pin This Location" to confirm.');
            } else {
                alert('❌ Location not found. Please try a different search term.');
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
            alert('❌ Failed to search location. Please try again.');
        })
        .finally(() => {
            searchBtn.innerHTML = originalText;
            searchBtn.disabled = false;
        });
}

function moveMarker(lat, lng) {
    if (propertyMarker) {
        propertyMarker.setLatLng([lat, lng]);
    } else {
        propertyMarker = L.marker([lat, lng], {
            draggable: true
        }).addTo(propertyMap);
        
        // Add drag event
        propertyMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateCoordinates(pos.lat, pos.lng);
        });
    }
    
    updateCoordinates(lat, lng);
}

function updateCoordinates(lat, lng) {
    currentMapCenter = { lat, lng };
    document.getElementById('coordsDisplay').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('mapCoordinates').classList.add('show');
}

function pinCurrentLocation() {
    if (!propertyMarker) {
        alert('⚠️ Please search for a location first or click on the map to place a marker');
        return;
    }
    
    const { lat, lng } = currentMapCenter;
    
    // Save coordinates to hidden fields
    document.getElementById('propertyLatitude').value = lat;
    document.getElementById('propertyLongitude').value = lng;
    
    // Update button style
    const pinBtn = document.querySelector('.btn-pin-location');
    pinBtn.classList.add('pinned');
    pinBtn.innerHTML = '✅ Location Pinned!';
    
    setTimeout(() => {
        pinBtn.innerHTML = '📍 Pin This Location';
    }, 2000);
    
    alert('✅ Location pinned successfully!');
}

// ==================== PHOTO UPLOAD HANDLING ====================

function handlePhotoUpload(event) {
    const files = Array.from(event.target.files);
    
    // Limit to 8 photos total
    const remainingSlots = 8 - uploadedPhotos.length;
    if (remainingSlots <= 0) {
        alert('⚠️ Maximum 8 photos allowed');
        return;
    }
    
    const filesToAdd = files.slice(0, remainingSlots);
    
    // Validate each file
    for (const file of filesToAdd) {
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(`⚠️ ${file.name} is too large. Maximum file size is 5MB.`);
            continue;
        }
        
        // Check file type
        if (!file.type.startsWith('image/')) {
            alert(`⚠️ ${file.name} is not an image file.`);
            continue;
        }
        
        uploadedPhotos.push(file);
    }
    
    renderPhotoGrid();
    event.target.value = ''; // Reset input
}

function renderPhotoGrid() {
    const photoGrid = document.getElementById('photoGrid');
    photoGrid.innerHTML = '';
    
    uploadedPhotos.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const photoItem = document.createElement('div');
            photoItem.className = 'photo-item';
            photoItem.innerHTML = `
                <img src="${e.target.result}" alt="Property photo ${index + 1}">
                <button type="button" class="photo-remove" onclick="removePhoto(${index})" title="Remove photo">×</button>
            `;
            photoGrid.appendChild(photoItem);
        };
        reader.readAsDataURL(file);
    });
}

function removePhoto(index) {
    uploadedPhotos.splice(index, 1);
    renderPhotoGrid();
}

// ==================== PROPERTY FORM HANDLING ====================

async function handlePropertySubmit(event) {
    event.preventDefault();
    
    // Validate photos
    if (uploadedPhotos.length < 1) {
        alert('⚠️ Please upload at least 1 photo');
        return;
    }
    
    if (uploadedPhotos.length < 6) {
        if (!confirm(`You've only uploaded ${uploadedPhotos.length} photo(s). We recommend 6-8 photos for better visibility. Continue anyway?`)) {
            return;
        }
    }
    
    // Validate location pin
    const latitude = document.getElementById('propertyLatitude').value;
    const longitude = document.getElementById('propertyLongitude').value;
    
    if (!latitude || !longitude) {
        alert('⚠️ Please pin your property location on the map');
        return;
    }
    
    // Get form values
    const formData = new FormData();
    formData.append('title', document.getElementById('propertyTitle').value.trim());
    formData.append('type', document.getElementById('propertyType').value);
    formData.append('rent', document.getElementById('propertyRent').value);
    formData.append('bedrooms', document.getElementById('propertyBedrooms').value);
    formData.append('bathrooms', document.getElementById('propertyBathrooms').value);
    formData.append('area', document.getElementById('propertyArea').value);
    formData.append('address', document.getElementById('propertyAddress').value.trim());
    formData.append('description', document.getElementById('propertyDescription').value.trim());
    formData.append('latitude', latitude);
    formData.append('longitude', longitude);
    
    // Append photos
    uploadedPhotos.forEach((photo, index) => {
        formData.append('photos[]', photo);
    });
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Listing Property...';
    submitBtn.disabled = true;
    
    try {
        // Send to backend API
        const response = await fetch('/RENTCONNECT(2)/functions/landlord_f/save_property.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        if (result.success) {
            alert('✅ Property listed successfully!');
            resetPropertyForm();
            showSection('properties');
            loadProperties(); // Refresh the properties list from database
        } else {
            alert(`❌ Failed to list property: ${result.message}`);
        }
        
    } catch (error) {
        console.error('Property listing error:', error);
        alert('❌ Failed to list property. Please try again.');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

function resetPropertyForm() {
    document.getElementById('propertyForm').reset();
    uploadedPhotos = [];
    renderPhotoGrid();
    
    // Reset map
    if (propertyMarker) {
        propertyMap.removeLayer(propertyMarker);
        propertyMarker = null;
    }
    document.getElementById('propertyLatitude').value = '';
    document.getElementById('propertyLongitude').value = '';
    document.getElementById('mapCoordinates').classList.remove('show');
    document.querySelector('.btn-pin-location').classList.remove('pinned');
    
    // Reset map view to Muntinlupa
    if (propertyMap) {
        propertyMap.setView([14.3832, 121.0409], 13);
    }
}

// ==================== LOAD AND DISPLAY PROPERTIES ====================

async function loadProperties() {
    const propertiesList = document.getElementById('propertiesList');
    const emptyState = document.getElementById('emptyPropertiesState');
    
    try {
        // Fetch properties from database
        const response = await fetch('/RENTCONNECT(2)/functions/landlord_f/get_properties.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        properties = result.properties || [];
        
        if (properties.length === 0) {
            propertiesList.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }
        
        propertiesList.style.display = 'grid';
        emptyState.style.display = 'none';
        propertiesList.innerHTML = '';
        
        properties.forEach(property => {
            const card = createPropertyCard(property);
            propertiesList.appendChild(card);
        });
        
    } catch (error) {
        console.error('Error loading properties:', error);
        propertiesList.style.display = 'none';
        emptyState.style.display = 'block';
    }
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function viewPropertyDetails(propertyId) {
    console.log('Opening preview for property:', propertyId);
    
    const property = properties.find(p => p.id === propertyId);
    if (!property) {
        console.error('Property not found:', propertyId);
        alert('Property not found');
        return;
    }
    
    console.log('Property found:', property);
    
    // Populate preview modal
    document.getElementById('previewTitle').textContent = property.title;
    document.getElementById('previewType').textContent = capitalizeFirst(property.type);
    document.getElementById('previewPrice').textContent = `₱${parseInt(property.rent).toLocaleString()}/mo`;
    document.getElementById('previewBeds').textContent = property.bedrooms;
    document.getElementById('previewBedsPlural').textContent = property.bedrooms > 1 ? 's' : '';
    document.getElementById('previewBaths').textContent = property.bathrooms;
    document.getElementById('previewBathsPlural').textContent = property.bathrooms > 1 ? 's' : '';
    document.getElementById('previewArea').textContent = property.area;
    document.getElementById('previewAddress').textContent = property.address;
    document.getElementById('previewDescription').textContent = property.description;
    
    // Set status badge
    const statusBadge = document.getElementById('previewStatus');
    const status = property.status || 'available';
    statusBadge.textContent = capitalizeFirst(status);
    statusBadge.className = `preview-status ${status}`;
    
    // Load photos
    currentPreviewPhotos = property.photos || [];
    currentPreviewIndex = 0;
    
    console.log('Loading photos:', currentPreviewPhotos.length);
    
    if (currentPreviewPhotos.length > 0) {
        loadPreviewPhoto(0);
        loadPreviewThumbnails();
    } else {
        // Show placeholder
        document.getElementById('previewMainImg').src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22%3E%3Crect fill=%22%23E4EFE7%22 width=%22800%22 height=%22500%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2272%22 text-anchor=%22middle%22 dy=%22.3em%22%3E🏠%3C/text%3E%3C/svg%3E';
        document.getElementById('previewPhotoIndex').textContent = '0';
        document.getElementById('previewPhotoTotal').textContent = '0';
        document.getElementById('previewThumbnails').innerHTML = '';
    }
    
    // Show modal FIRST
    const modal = document.getElementById('propertyPreviewModal');
    if (modal) {
        modal.classList.add('active');
        console.log('Modal opened');
    } else {
        console.error('Modal element not found!');
    }
    
    // Initialize preview map after modal is visible
    setTimeout(() => {
        initializePreviewMap(property.latitude, property.longitude);
    }, 300);
}

function prevPreviewPhoto() {
    const newIndex = currentPreviewIndex - 1;
    if (newIndex >= 0) {
        loadPreviewPhoto(newIndex);
    } else {
        loadPreviewPhoto(currentPreviewPhotos.length - 1); // Loop to last
    }
}

function nextPreviewPhoto() {
    const newIndex = currentPreviewIndex + 1;
    if (newIndex < currentPreviewPhotos.length) {
        loadPreviewPhoto(newIndex);
    } else {
        loadPreviewPhoto(0); // Loop to first
    }
}

function initializePreviewMap(latitude, longitude) {
    const mapContainer = document.getElementById('previewMapContainer');
    
    if (!mapContainer) return;
    
    // Remove existing map
    if (previewMap) {
        previewMap.remove();
        previewMap = null;
    }
    
    // Check if coordinates exist
    if (!latitude || !longitude) {
        mapContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; background: var(--border-color); border-radius: 12px;"><p style="color: var(--text-primary); opacity: 0.5;">📍 Location not specified</p></div>';
        return;
    }
    
    try {
        // Initialize map
        previewMap = L.map('previewMapContainer').setView([latitude, longitude], 15);
        
        // Add tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(previewMap);
        
        // Add marker
        L.marker([latitude, longitude]).addTo(previewMap);
        
        // Fix map display
        setTimeout(() => {
            previewMap.invalidateSize();
        }, 100);
    } catch (error) {
        console.error('Preview map error:', error);
    }
}

function closePreviewModal() {
    document.getElementById('propertyPreviewModal').classList.remove('active');
    
    // Clean up preview map
    if (previewMap) {
        previewMap.remove();
        previewMap = null;
    }
}

function editProperty(propertyId) {
    const property = properties.find(p => p.id === propertyId);
    if (!property) {
        alert('Property not found');
        return;
    }
    
    // Populate edit form with property data
    document.getElementById('editPropertyId').value = property.id;
    document.getElementById('editPropertyTitle').value = property.title;
    document.getElementById('editPropertyType').value = property.type;
    document.getElementById('editPropertyRent').value = property.rent;
    document.getElementById('editPropertyBedrooms').value = property.bedrooms;
    document.getElementById('editPropertyBathrooms').value = property.bathrooms;
    document.getElementById('editPropertyArea').value = property.area;
    document.getElementById('editPropertyAddress').value = property.address;
    document.getElementById('editPropertyDescription').value = property.description;
    document.getElementById('editPropertyStatus').value = property.status || 'available';
    document.getElementById('editPropertyLatitude').value = property.latitude || '';
    document.getElementById('editPropertyLongitude').value = property.longitude || '';
    
    // Open modal
    document.getElementById('editPropertyModal').classList.add('active');
    
    // Initialize edit map after modal opens
    setTimeout(() => {
        initializeEditMap(property.latitude, property.longitude);
    }, 300);
}

function initializeEditMap(latitude, longitude) {
    const mapContainer = document.getElementById('editPropertyMap');
    if (!mapContainer) {
        console.error('Edit map container not found');
        return;
    }
    
    // Remove existing map
    if (editMap) {
        editMap.remove();
        editMap = null;
    }
    
    // Use existing coordinates or default to Muntinlupa
    const lat = latitude || 14.3832;
    const lng = longitude || 121.0409;
    editMapCenter = { lat, lng };
    
    try {
        editMap = L.map('editPropertyMap').setView([lat, lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(editMap);
        
        // Add existing marker if coordinates exist
        if (latitude && longitude) {
            editMarker = L.marker([lat, lng], { draggable: true }).addTo(editMap);
            
            editMarker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updateEditCoordinates(pos.lat, pos.lng);
            });
            
            updateEditCoordinates(lat, lng);
            document.getElementById('editPinBtn').classList.add('pinned');
            document.getElementById('editPinBtn').innerHTML = '✅ Location Pinned';
        }
        
        // Add click event
        editMap.on('click', function(e) {
            const { lat, lng } = e.latlng;
            moveEditMarker(lat, lng);
        });
        
        setTimeout(() => {
            editMap.invalidateSize();
        }, 100);
        
        console.log('✅ Edit map initialized');
    } catch (error) {
        console.error('Edit map error:', error);
    }
}

function searchEditLocation() {
    const searchQuery = document.getElementById('editLocationSearch').value.trim();
    
    if (!searchQuery) {
        alert('⚠️ Please enter a location to search');
        return;
    }
    
    const searchBtn = event.target;
    const originalHTML = searchBtn.innerHTML;
    searchBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Searching...';
    searchBtn.disabled = true;
    
    // Use our PHP proxy to avoid CORS issues
    const url = `/RENTCONNECT(2)/functions/landlord_f/geocode.php?q=${encodeURIComponent(searchQuery)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data && result.data.length > 0) {
                const location = result.data[0];
                const lat = parseFloat(location.lat);
                const lng = parseFloat(location.lon);
                
                editMap.setView([lat, lng], 16);
                editMapCenter = { lat, lng };
                moveEditMarker(lat, lng);
                
                if (location.display_name) {
                    document.getElementById('editPropertyAddress').value = location.display_name;
                }
                
                alert('✅ Location found! Click "Pin This Location" to confirm.');
            } else {
                alert('❌ Location not found. Please try a different search term.');
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
            alert('❌ Failed to search location. Please try again.');
        })
        .finally(() => {
            searchBtn.innerHTML = originalHTML;
            searchBtn.disabled = false;
        });
}

function moveEditMarker(lat, lng) {
    if (editMarker) {
        editMarker.setLatLng([lat, lng]);
    } else {
        editMarker = L.marker([lat, lng], { draggable: true }).addTo(editMap);
        
        editMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateEditCoordinates(pos.lat, pos.lng);
        });
    }
    
    updateEditCoordinates(lat, lng);
}

function updateEditCoordinates(lat, lng) {
    editMapCenter = { lat, lng };
    document.getElementById('editCoordsDisplay').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('editMapCoordinates').style.display = 'block';
}

function pinEditLocation() {
    if (!editMarker) {
        alert('⚠️ Please search for a location first or click on the map to place a marker');
        return;
    }
    
    const { lat, lng } = editMapCenter;
    
    document.getElementById('editPropertyLatitude').value = lat;
    document.getElementById('editPropertyLongitude').value = lng;
    
    const pinBtn = document.getElementById('editPinBtn');
    pinBtn.classList.add('pinned');
    pinBtn.innerHTML = '✅ Location Pinned!';
    
    setTimeout(() => {
        pinBtn.innerHTML = '📍 Pin This Location';
    }, 2000);
    
    alert('✅ Location updated successfully!');
}

function closeEditModal() {
    document.getElementById('editPropertyModal').classList.remove('active');
    document.getElementById('editPropertyForm').reset();
    
    // Clean up edit map
    if (editMap) {
        editMap.remove();
        editMap = null;
    }
    if (editMarker) {
        editMarker = null;
    }
}

async function handlePropertyUpdate(event) {
    event.preventDefault();
    
    const propertyId = document.getElementById('editPropertyId').value;
    const address = document.getElementById('editPropertyAddress').value.trim();
    
    // Debug log
    console.log('=== Property Update Debug ===');
    console.log('Property ID:', propertyId);
    console.log('Address value:', address);
    console.log('Address length:', address.length);
    
    const formData = new FormData();
    
    formData.append('property_id', propertyId);
    formData.append('title', document.getElementById('editPropertyTitle').value.trim());
    formData.append('type', document.getElementById('editPropertyType').value);
    formData.append('rent', document.getElementById('editPropertyRent').value);
    formData.append('bedrooms', document.getElementById('editPropertyBedrooms').value);
    formData.append('bathrooms', document.getElementById('editPropertyBathrooms').value);
    formData.append('area', document.getElementById('editPropertyArea').value);
    formData.append('address', address);
    formData.append('description', document.getElementById('editPropertyDescription').value.trim());
    formData.append('status', document.getElementById('editPropertyStatus').value);
    formData.append('latitude', document.getElementById('editPropertyLatitude').value || '');
    formData.append('longitude', document.getElementById('editPropertyLongitude').value || '');
    
    // Debug: Log all form data
    console.log('FormData entries:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('/RENTCONNECT(2)/functions/landlord_f/update_property.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        const result = JSON.parse(responseText);
        
        if (result.success) {
            alert('✅ Property updated successfully!');
            closeEditModal();
            loadProperties(); // Reload properties
        } else {
            alert(`❌ Failed to update property: ${result.message}`);
        }
        
    } catch (error) {
        console.error('Property update error:', error);
        alert('❌ Failed to update property. Please try again.');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

function deleteProperty(propertyId) {
    if (!confirm('Are you sure you want to delete this property? This action cannot be undone.')) return;
    
    // Show loading
    const deleteBtn = event.target;
    const originalText = deleteBtn.textContent;
    deleteBtn.textContent = 'Deleting...';
    deleteBtn.disabled = true;
    
    fetch('/RENTCONNECT(2)/functions/landlord_f/delete_property.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            property_id: propertyId
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('✅ Property deleted successfully!');
            loadProperties(); // Reload properties from database
        } else {
            alert(`❌ Failed to delete property: ${result.message}`);
            deleteBtn.textContent = originalText;
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('❌ Failed to delete property. Please try again.');
        deleteBtn.textContent = originalText;
        deleteBtn.disabled = false;
    });
}

// ==================== PROFILE UPDATE HANDLING ====================

async function handleProfileUpdate(event) {
    event.preventDefault();
    
    // Get form values
    const profileData = {
        firstName: document.getElementById('profileFirstName').value.trim(),
        lastName: document.getElementById('profileLastName').value.trim(),
        contact: document.getElementById('profileContact').value.trim()
    };
    
    // Validation
    if (!profileData.firstName || !profileData.lastName) {
        alert('⚠️ Please fill in all required fields');
        return;
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    try {
        // TODO: Send to backend API
        // For now, just simulate success
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        alert('✅ Profile updated successfully!');
        
    } catch (error) {
        console.error('Profile update error:', error);
        alert('❌ Failed to update profile. Please try again.');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🏠 Landlord Dashboard loaded');
    
    // Load properties on page load
    loadProperties();
    
    // Handle window resize
    window.addEventListener('resize', () => {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('open', 'closed');
            mainContent.classList.remove('expanded');
        } else {
            sidebar.classList.add('closed');
        }
    });
    
    // Initialize sidebar state based on screen size
    if (window.innerWidth <= 1024) {
        document.getElementById('sidebar').classList.add('closed');
    }
});

// Make functions globally accessible
window.toggleSidebar = toggleSidebar;
window.showSection = showSection;
window.handlePhotoUpload = handlePhotoUpload;
window.removePhoto = removePhoto;
window.handlePropertySubmit = handlePropertySubmit;
window.resetPropertyForm = resetPropertyForm;
window.handleProfileUpdate = handleProfileUpdate;
window.editProperty = editProperty;
window.closeEditModal = closeEditModal;
window.handlePropertyUpdate = handlePropertyUpdate;
window.deleteProperty = deleteProperty;
window.viewPropertyDetails = viewPropertyDetails;
window.closePreviewModal = closePreviewModal;
window.prevPreviewPhoto = prevPreviewPhoto;
window.nextPreviewPhoto = nextPreviewPhoto;
window.searchLocation = searchLocation;
window.pinCurrentLocation = pinCurrentLocation;
window.searchEditLocation = searchEditLocation;
window.pinEditLocation = pinEditLocation;
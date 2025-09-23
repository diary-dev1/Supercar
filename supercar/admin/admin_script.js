// Variables globales
let currentSection = 'dashboard';
const API_BASE = '.';

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    setupNavigation();
    loadDashboardData();
    setupModalEvents();
    setupHomepageConfig();
});

// Navigation
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.getAttribute('data-section');
            switchSection(section);
        });
    });
}

function switchSection(section) {
    // Cacher toutes les sections
    document.querySelectorAll('.page-section').forEach(s => {
        s.classList.remove('active');
    });
    
    // Afficher la section demandée
    document.getElementById(section).classList.add('active');
    
    // Mettre à jour la navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-section') === section) {
            link.classList.add('active');
        }
    });
    
    // Mettre à jour le titre
    const titles = {
        dashboard: 'Dashboard',
        cars: 'Gestion des Voitures',
        bookings: 'Demandes d\'Essai',
        users: 'Utilisateurs',
        messages: 'Messages',
        services: 'Services',
        homepage: 'Configuration du Site'
    };
    document.getElementById('page-title').textContent = titles[section] || section;
    
    currentSection = section;
    
    // Charger les données selon la section
    switch(section) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'cars':
            loadCars();
            break;
        case 'bookings':
            loadBookings();
            break;
        case 'users':
            loadUsers();
            break;
        case 'messages':
            loadMessages();
            break;
        case 'services':
            loadServices();
            break;
        case 'homepage':
            loadHomepageConfig();
            break;
    }
}

// Homepage Configuration
function setupHomepageConfig() {
    const heroImageInput = document.getElementById('hero-image-url');
    const heroImagePreview = document.getElementById('hero-image-preview');
    const homepageForm = document.getElementById('homepage-config-form');
    
    if (heroImageInput && heroImagePreview) {
        // Aperçu de l'image en temps réel
        heroImageInput.addEventListener('input', function() {
            const url = this.value.trim();
            if (url) {
                heroImagePreview.src = url;
                heroImagePreview.style.display = 'block';
                heroImagePreview.onerror = function() {
                    this.style.display = 'none';
                };
            } else {
                heroImagePreview.style.display = 'none';
            }
        });
    }
    
    if (homepageForm) {
        // Soumission du formulaire
        homepageForm.addEventListener('submit', handleHomepageConfigSubmit);
    }
}

async function loadHomepageConfig() {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_homepage.php`);
        const data = await response.json();
        
        if (data.success) {
            const config = data.data;
            
            // Remplir le formulaire
            document.getElementById('hero-title').value = config.hero_title;
            document.getElementById('hero-subtitle').value = config.hero_subtitle;
            document.getElementById('hero-image-url').value = config.hero_image_url;
            document.getElementById('hero-button-text').value = config.hero_button_text;
            document.getElementById('about-title').value = config.about_title;
            document.getElementById('about-description').value = config.about_description;
            document.getElementById('company-name').value = config.company_name;
            document.getElementById('company-phone').value = config.company_phone || '';
            document.getElementById('company-email').value = config.company_email || '';
            document.getElementById('company-address').value = config.company_address || '';
            
            // Afficher l'aperçu de l'image si elle existe
            const imagePreview = document.getElementById('hero-image-preview');
            if (config.hero_image_url && imagePreview) {
                imagePreview.src = config.hero_image_url;
                imagePreview.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Erreur chargement configuration:', error);
        showNotification('Erreur lors du chargement de la configuration', 'error');
    }
}

async function handleHomepageConfigSubmit(e) {
    e.preventDefault();
    
    const formData = {
        hero_title: document.getElementById('hero-title').value,
        hero_subtitle: document.getElementById('hero-subtitle').value,
        hero_image_url: document.getElementById('hero-image-url').value,
        hero_button_text: document.getElementById('hero-button-text').value,
        about_title: document.getElementById('about-title').value,
        about_description: document.getElementById('about-description').value,
        company_name: document.getElementById('company-name').value,
        company_phone: document.getElementById('company-phone').value,
        company_email: document.getElementById('company-email').value,
        company_address: document.getElementById('company-address').value
    };
    
    try {
        const response = await fetch(`${API_BASE}/admin_manage_homepage.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Configuration mise à jour avec succès', 'success');
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur sauvegarde configuration:', error);
        showNotification('Erreur lors de la sauvegarde', 'error');
    }
}

// Dashboard
async function loadDashboardData() {
    try {
        const response = await fetch(`${API_BASE}/admin_get_stats.php`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-cars').textContent = data.data.total_cars;
            document.getElementById('total-users').textContent = data.data.total_users;
            document.getElementById('total-bookings').textContent = data.data.total_bookings;
            document.getElementById('total-messages').textContent = data.data.total_messages;
        }
        
        await loadRecentBookings();
    } catch (error) {
        console.error('Erreur chargement dashboard:', error);
        showNotification('Erreur lors du chargement des statistiques', 'error');
    }
}

async function loadRecentBookings() {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_bookings.php?recent=true&limit=5`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('recent-bookings');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">Aucune demande d\'essai récente</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(booking => `
                <tr>
                    <td>${booking.user.name}</td>
                    <td>${booking.car.name}</td>
                    <td>${formatDate(booking.test_date)}</td>
                    <td><span class="badge badge-${getStatusBadgeClass(booking.status)}">${booking.status_label}</span></td>
                    <td>
                        ${booking.status === 'pending' ? `
                            <button class="btn btn-success btn-sm" onclick="updateBookingStatus(${booking.id}, 'confirmed')">Confirmer</button>
                            <button class="btn btn-danger btn-sm" onclick="updateBookingStatus(${booking.id}, 'cancelled')">Annuler</button>
                        ` : `
                            <button class="btn btn-warning btn-sm" onclick="viewBookingDetails(${booking.id})">Voir</button>
                        `}
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement bookings récents:', error);
    }
}

// Cars
async function loadCars() {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_cars.php`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('cars-table');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem;">Aucune voiture trouvée</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(car => `
                <tr>
                    <td><img src="${car.image_url || 'https://via.placeholder.com/60x40'}" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                    <td>${car.name}</td>
                    <td>${car.brand}</td>
                    <td>${car.price_formatted}</td>
                    <td><span class="badge badge-${car.available ? 'success' : 'danger'}">${car.available ? 'Disponible' : 'Indisponible'}</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="editCar(${car.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteCar(${car.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement voitures:', error);
        showNotification('Erreur lors du chargement des voitures', 'error');
    }
}

// Bookings - CORRECTION APPLIQUÉE ICI
async function loadBookings(statusFilter = '') {
    try {
        const url = statusFilter ? 
            `${API_BASE}/admin_manage_bookings.php?status=${statusFilter}` : 
            `${API_BASE}/admin_manage_bookings.php`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('bookings-table');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">Aucune demande d\'essai trouvée</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(booking => `
                <tr>
                    <td>${booking.user.name}</td>
                    <td>${booking.user.email}</td>
                    <td>${booking.phone}</td>
                    <td>${booking.car.name}</td>
                    <td>${formatDate(booking.test_date)}</td>
                    <td>${booking.test_time}</td>
                    <td><span class="badge badge-${getStatusBadgeClass(booking.status)}">${booking.status_label}</span></td>
                    <td>
                        ${booking.status === 'pending' ? `
                            <button class="btn btn-success btn-sm" onclick="updateBookingStatus(${booking.id}, 'confirmed')">Confirmer</button>
                            <button class="btn btn-danger btn-sm" onclick="updateBookingStatus(${booking.id}, 'cancelled')">Annuler</button>
                        ` : booking.status === 'confirmed' ? `
                            <button class="btn btn-warning btn-sm" onclick="updateBookingStatus(${booking.id}, 'completed')">Terminer</button>
                            <button class="btn btn-danger btn-sm" onclick="updateBookingStatus(${booking.id}, 'cancelled')">Annuler</button>
                        ` : `
                            <button class="btn btn-secondary btn-sm" onclick="viewBookingDetails(${booking.id})">Voir</button>
                        `}
                        <button class="btn btn-danger btn-sm" onclick="deleteBooking(${booking.id})" style="margin-left: 0.25rem;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement bookings:', error);
        showNotification('Erreur lors du chargement des demandes d\'essai', 'error');
    }
}

// Users
async function loadUsers() {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_users.php`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('users-table');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">Aucun utilisateur trouvé</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(user => `
                <tr>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>${user.phone || 'Non renseigné'}</td>
                    <td>${user.formatted_date}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                        <span style="margin-left: 0.5rem; font-size: 0.8em; color: #666;">
                            ${user.bookings_count} demande(s)
                        </span>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement utilisateurs:', error);
        showNotification('Erreur lors du chargement des utilisateurs', 'error');
    }
}

// Messages
async function loadMessages(statusFilter = '') {
    try {
        const url = statusFilter ? 
            `${API_BASE}/admin_manage_messages.php?status=${statusFilter}` : 
            `${API_BASE}/admin_manage_messages.php`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('messages-table');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem;">Aucun message trouvé</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(message => `
                <tr>
                    <td>${message.nom}</td>
                    <td>${message.email}</td>
                    <td>${message.sujet}</td>
                    <td>${message.formatted_date}</td>
                    <td><span class="badge badge-${getMessageStatusBadgeClass(message.status)}">${message.status_label}</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="viewMessage(${message.id})" title="Voir le message">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-success btn-sm" onclick="markAsReplied(${message.id})" title="Marquer comme répondu">
                            <i class="fas fa-reply"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteMessage(${message.id})" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement messages:', error);
        showNotification('Erreur lors du chargement des messages', 'error');
    }
}

// Services
async function loadServices() {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_services.php`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('services-table');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">Aucun service trouvé</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.data.map(service => `
                <tr>
                    <td>${service.title}</td>
                    <td>${service.price}</td>
                    <td>${service.category}</td>
                    <td><span class="badge badge-${service.available ? 'success' : 'danger'}">${service.available ? 'Disponible' : 'Indisponible'}</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="editService(${service.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteService(${service.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Erreur chargement services:', error);
        showNotification('Erreur lors du chargement des services', 'error');
    }
}

// Fonctions d'action pour les bookings
async function updateBookingStatus(bookingId, status) {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_bookings.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: bookingId, status: status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Statut mis à jour avec succès', 'success');
            if (currentSection === 'dashboard') {
                loadRecentBookings();
            } else {
                loadBookings();
            }
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur mise à jour booking:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

// FONCTION CORRIGÉE - Affichage des détails d'une demande d'essai
async function viewBookingDetails(bookingId) {
    try {
        // Récupérer toutes les demandes d'essai pour trouver celle avec l'ID correspondant
        const response = await fetch(`${API_BASE}/admin_manage_bookings.php`);
        const data = await response.json();
        
        if (data.success) {
            const booking = data.data.find(b => b.id === bookingId);
            
            if (booking) {
                // Créer une fenêtre modale pour afficher les détails
                showBookingModal(booking);
            } else {
                showNotification('Demande d\'essai non trouvée', 'error');
            }
        } else {
            showNotification('Erreur lors du chargement des détails', 'error');
        }
    } catch (error) {
        console.error('Erreur chargement détails booking:', error);
        showNotification('Erreur lors du chargement des détails', 'error');
    }
}

function showBookingModal(booking) {
    // Créer le modal s'il n'existe pas
    let modal = document.getElementById('booking-details-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'booking-details-modal';
        modal.className = 'modal';
        modal.style.display = 'block';
        
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Détails de la Demande d'Essai</h3>
                    <span class="close" onclick="closeBookingModal()">&times;</span>
                </div>
                <div style="padding: 2rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #1e293b;">Informations Client</h4>
                            <p><strong>Nom:</strong> <span id="modal-client-name"></span></p>
                            <p><strong>Email:</strong> <span id="modal-client-email"></span></p>
                            <p><strong>Téléphone:</strong> <span id="modal-client-phone"></span></p>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 1rem; color: #1e293b;">Détails de l'Essai</h4>
                            <p><strong>Voiture:</strong> <span id="modal-car-info"></span></p>
                            <p><strong>Date:</strong> <span id="modal-test-date"></span></p>
                            <p><strong>Heure:</strong> <span id="modal-test-time"></span></p>
                            <p><strong>Statut:</strong> <span id="modal-status"></span></p>
                        </div>
                    </div>
                    <div style="margin-top: 2rem;">
                        <h4 style="margin-bottom: 1rem; color: #1e293b;">Message du Client</h4>
                        <div id="modal-message" style="background: #f8fafc; padding: 1rem; border-radius: 6px; border-left: 4px solid #3b82f6;"></div>
                    </div>
                    <div style="margin-top: 2rem;">
                        <p><strong>Demande créée le:</strong> <span id="modal-created-date"></span></p>
                        <p><strong>Dernière mise à jour:</strong> <span id="modal-updated-date"></span></p>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 2rem; padding: 0 2rem 2rem 2rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                    <button class="btn btn-secondary" onclick="closeBookingModal()">Fermer</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } else {
        modal.style.display = 'block';
    }
    
    // Remplir les données
    document.getElementById('modal-client-name').textContent = booking.user.name;
    document.getElementById('modal-client-email').textContent = booking.user.email;
    document.getElementById('modal-client-phone').textContent = booking.phone;
    document.getElementById('modal-car-info').textContent = `${booking.car.brand} ${booking.car.name}`;
    document.getElementById('modal-test-date').textContent = formatDate(booking.test_date);
    document.getElementById('modal-test-time').textContent = booking.test_time;
    document.getElementById('modal-status').innerHTML = `<span class="badge badge-${getStatusBadgeClass(booking.status)}">${booking.status_label}</span>`;
    document.getElementById('modal-message').textContent = booking.message || 'Aucun message spécifique';
    document.getElementById('modal-created-date').textContent = new Date(booking.created_at).toLocaleString('fr-FR');
    document.getElementById('modal-updated-date').textContent = new Date(booking.updated_at).toLocaleString('fr-FR');
    
    // Fermer en cliquant à l'extérieur
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeBookingModal();
        }
    });
}

function closeBookingModal() {
    const modal = document.getElementById('booking-details-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function deleteBooking(bookingId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette demande d\'essai ?')) {
        return;
    }
    
    fetch(`${API_BASE}/admin_manage_bookings.php?id=${bookingId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Demande d\'essai supprimée avec succès', 'success');
            if (currentSection === 'dashboard') {
                loadRecentBookings();
            } else {
                loadBookings();
            }
        } else {
            showNotification(data.message || 'Erreur lors de la suppression', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur suppression booking:', error);
        showNotification('Erreur lors de la suppression', 'error');
    });
}

async function deleteCar(carId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette voiture ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/admin_manage_cars.php?id=${carId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Voiture supprimée avec succès', 'success');
            loadCars();
        } else {
            showNotification(data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur suppression voiture:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/admin_manage_users.php?id=${userId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Utilisateur supprimé avec succès', 'success');
            loadUsers();
        } else {
            showNotification(data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur suppression utilisateur:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
}

async function deleteMessage(messageId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/admin_manage_messages.php?id=${messageId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Message supprimé avec succès', 'success');
            loadMessages();
        } else {
            showNotification(data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur suppression message:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
}

async function deleteService(serviceId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce service ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/admin_manage_services.php?id=${serviceId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Service supprimé avec succès', 'success');
            loadServices();
        } else {
            showNotification(data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur suppression service:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
}

async function markAsReplied(messageId) {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_messages.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: messageId, status: 'replied' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Message marqué comme répondu', 'success');
            loadMessages();
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur mise à jour message:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

async function viewMessage(messageId) {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_messages.php?id=${messageId}`);
        const data = await response.json();
        
        if (data.success) {
            const message = data.data;
            alert(`
Message de: ${message.nom}
Email: ${message.email}
Sujet: ${message.sujet}
Date: ${message.formatted_date}

Message:
${message.message}
            `);
            // Recharger les messages pour refléter le changement de statut
            loadMessages();
        } else {
            showNotification('Erreur lors du chargement du message', 'error');
        }
    } catch (error) {
        console.error('Erreur chargement message:', error);
        showNotification('Erreur lors du chargement du message', 'error');
    }
}

// Gestion des modaux
function setupModalEvents() {
    // Modal voiture
    const carModal = document.getElementById('car-modal');
    const carForm = document.getElementById('car-form');
    
    // Fermer les modaux en cliquant à l'extérieur
    window.addEventListener('click', (event) => {
        if (event.target === carModal) {
            closeCarModal();
        }
    });
    
    // Formulaire voiture
    if (carForm) {
        carForm.addEventListener('submit', handleCarFormSubmit);
    }
    
    // Filtres
    const bookingFilter = document.getElementById('booking-status-filter');
    if (bookingFilter) {
        bookingFilter.addEventListener('change', function() {
            loadBookings(this.value);
        });
    }
    
    const messageFilter = document.getElementById('message-status-filter');
    if (messageFilter) {
        messageFilter.addEventListener('change', function() {
            loadMessages(this.value);
        });
    }
}

async function handleCarFormSubmit(e) {
    e.preventDefault();
    
    const carId = document.getElementById('car-id').value;
    const formData = {
        name: document.getElementById('car-name').value,
        brand: document.getElementById('car-brand').value,
        price: parseFloat(document.getElementById('car-price').value),
        power: document.getElementById('car-power').value,
        max_speed: document.getElementById('car-speed').value,
        acceleration: document.getElementById('car-acceleration').value,
        image_url: document.getElementById('car-image').value,
        description: document.getElementById('car-description').value,
        available: document.getElementById('car-available').checked
    };
    
    // Ajouter l'ID si on modifie
    if (carId) {
        formData.id = parseInt(carId);
    }
    
    try {
        const method = carId ? 'PUT' : 'POST';
        const response = await fetch(`${API_BASE}/admin_manage_cars.php`, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(carId ? 'Voiture modifiée avec succès' : 'Voiture ajoutée avec succès', 'success');
            closeCarModal();
            loadCars();
        } else {
            showNotification(data.message || 'Erreur lors de l\'enregistrement', 'error');
        }
    } catch (error) {
        console.error('Erreur sauvegarde voiture:', error);
        showNotification('Erreur lors de l\'enregistrement', 'error');
    }
}

function openCarModal(carId = null) {
    const modal = document.getElementById('car-modal');
    const form = document.getElementById('car-form');
    const title = document.getElementById('car-modal-title');
    
    if (carId) {
        title.textContent = 'Modifier la Voiture';
        loadCarData(carId);
    } else {
        title.textContent = 'Ajouter une Voiture';
        form.reset();
        document.getElementById('car-id').value = '';
        document.getElementById('car-available').checked = true;
    }
    
    modal.style.display = 'block';
}

function closeCarModal() {
    document.getElementById('car-modal').style.display = 'none';
}

async function loadCarData(carId) {
    try {
        const response = await fetch(`${API_BASE}/admin_manage_cars.php`);
        const data = await response.json();
        
        if (data.success) {
            const car = data.data.find(c => c.id === carId);
            if (car) {
                document.getElementById('car-id').value = car.id;
                document.getElementById('car-name').value = car.name;
                document.getElementById('car-brand').value = car.brand;
                document.getElementById('car-price').value = car.price;
                document.getElementById('car-power').value = car.power || '';
                document.getElementById('car-speed').value = car.max_speed || '';
                document.getElementById('car-acceleration').value = car.acceleration || '';
                document.getElementById('car-image').value = car.image_url || '';
                document.getElementById('car-description').value = car.description || '';
                document.getElementById('car-available').checked = car.available;
            }
        }
    } catch (error) {
        console.error('Erreur chargement données voiture:', error);
        showNotification('Erreur lors du chargement des données', 'error');
    }
}

function editCar(carId) {
    openCarModal(carId);
}

// Fonctions pour les services
function editService(serviceId) {
    openServiceModal(serviceId);
}

function openServiceModal(serviceId = null) {
    if (serviceId) {
        // Mode édition - récupérer les données du service
        fetch(`${API_BASE}/admin_manage_services.php`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const service = data.data.find(s => s.id === serviceId);
                    if (service) {
                        editServicePrompt(service);
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors du chargement du service', 'error');
            });
    } else {
        // Mode création
        createServicePrompt();
    }
}

function createServicePrompt() {
    const title = prompt('Titre du service:');
    if (!title) return;
    
    const description = prompt('Description (optionnelle):') || '';
    const price = prompt('Prix:');
    if (!price) return;
    
    const category = prompt('Catégorie:') || '';
    const available = confirm('Service disponible ?');
    
    const serviceData = {
        title: title,
        description: description,
        price: price,
        category: category,
        available: available
    };
    
    fetch(`${API_BASE}/admin_manage_services.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(serviceData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Service créé avec succès', 'success');
            loadServices();
        } else {
            showNotification(data.message || 'Erreur lors de la création', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur création service:', error);
        showNotification('Erreur lors de la création', 'error');
    });
}

function editServicePrompt(service) {
    const title = prompt('Titre du service:', service.title);
    if (!title) return;
    
    const description = prompt('Description:', service.description) || '';
    const price = prompt('Prix:', service.price);
    if (!price) return;
    
    const category = prompt('Catégorie:', service.category) || '';
    const available = confirm(`Service disponible ? (actuellement: ${service.available ? 'Oui' : 'Non'})`);
    
    const serviceData = {
        id: service.id,
        title: title,
        description: description,
        price: price,
        category: category,
        available: available
    };
    
    fetch(`${API_BASE}/admin_manage_services.php`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(serviceData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Service modifié avec succès', 'success');
            loadServices();
        } else {
            showNotification(data.message || 'Erreur lors de la modification', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur modification service:', error);
        showNotification('Erreur lors de la modification', 'error');
    });
}

// Utilitaires
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'warning',
        'confirmed': 'success',
        'completed': 'info',
        'cancelled': 'danger'
    };
    return classes[status] || 'secondary';
}

function getMessageStatusBadgeClass(status) {
    const classes = {
        'new': 'danger',
        'read': 'warning',
        'replied': 'success'
    };
    return classes[status] || 'secondary';
}

// Notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10B981' : '#DC143C'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 5px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        z-index: 3000;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}
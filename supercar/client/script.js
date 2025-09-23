// Configuration des données (fallback)
const carsData = [
    {
        id: 1,
        name: "Ferrari F8 Tributo",
        price: "280 000 €",
        image: "https://images.pexels.com/photos/544542/pexels-photo-544542.jpeg",
        power: "720 ch",
        speed: "340 km/h",
        acceleration: "2.9s"
    },
    {
        id: 2,
        name: "Lamborghini Huracán",
        price: "220 000 €",
        image: "https://images.pexels.com/photos/1592384/pexels-photo-1592384.jpeg",
        power: "640 ch",
        speed: "325 km/h",
        acceleration: "3.2s"
    },
    {
        id: 3,
        name: "McLaren 570S",
        price: "200 000 €",
        image: "https://images.pexels.com/photos/1009324/pexels-photo-1009324.jpeg",
        power: "570 ch",
        speed: "328 km/h",
        acceleration: "3.2s"
    },
    {
        id: 4,
        name: "Porsche 911 GT3",
        price: "180 000 €",
        image: "https://images.pexels.com/photos/1334924/pexels-photo-1334924.jpeg",
        power: "510 ch",
        speed: "318 km/h",
        acceleration: "3.4s"
    },
    {
        id: 5,
        name: "Aston Martin Vantage",
        price: "160 000 €",
        image: "https://images.pexels.com/photos/1149137/pexels-photo-1149137.jpeg",
        power: "503 ch",
        speed: "314 km/h",
        acceleration: "3.6s"
    },
    {
        id: 6,
        name: "BMW M8 Competition",
        price: "150 000 €",
        image: "https://images.pexels.com/photos/1719648/pexels-photo-1719648.jpeg",
        power: "625 ch",
        speed: "305 km/h",
        acceleration: "3.2s"
    },
    {
        id: 7,
        name: "Mercedes-AMG GT R",
        price: "190 000 €",
        image: "https://images.pexels.com/photos/2920064/pexels-photo-2920064.jpeg",
        power: "585 ch",
        speed: "318 km/h",
        acceleration: "3.6s"
    },
    {
        id: 8,
        name: "Audi R8 V10",
        price: "170 000 €",
        image: "https://images.pexels.com/photos/1534604/pexels-photo-1534604.jpeg",
        power: "570 ch",
        speed: "324 km/h",
        acceleration: "3.4s"
    },
    {
        id: 9,
        name: "Nissan GT-R NISMO",
        price: "200 000 €",
        image: "https://images.pexels.com/photos/1146603/pexels-photo-1146603.jpeg",
        power: "600 ch",
        speed: "315 km/h",
        acceleration: "2.8s"
    }
];

// Services chargés depuis l'API
let servicesData = [
    {
        id: 1,
        title: "Maintenance Premium",
        description: "Service complet de maintenance avec pièces d'origine",
        price: "À partir de 500€",
        icon: "fas fa-tools"
    },
    {
        id: 2,
        title: "Assurance Elite",
        description: "Couverture complète pour votre véhicule de luxe",
        price: "À partir de 200€/mois",
        icon: "fas fa-shield-alt"
    },
    {
        id: 3,
        title: "Livraison VIP",
        description: "Livraison à domicile avec service conciergerie",
        price: "Gratuit",
        icon: "fas fa-truck"
    },
    {
        id: 4,
        title: "Formation Pilotage",
        description: "Cours de pilotage avec instructeurs professionnels",
        price: "À partir de 800€",
        icon: "fas fa-graduation-cap"
    },
    {
        id: 5,
        title: "Stockage Sécurisé",
        description: "Garde de votre véhicule dans nos installations",
        price: "À partir de 300€/mois",
        icon: "fas fa-warehouse"
    },
    {
        id: 6,
        title: "Personnalisation",
        description: "Customisation selon vos préférences",
        price: "Sur devis",
        icon: "fas fa-paint-brush"
    },
    {
        id: 7,
        title: "Financement Premium",
        description: "Solutions de financement sur mesure",
        price: "Taux préférentiels",
        icon: "fas fa-credit-card"
    },
    {
        id: 8,
        title: "Expertise Technique",
        description: "Diagnostic et expertise par nos spécialistes",
        price: "À partir de 150€",
        icon: "fas fa-microscope"
    },
    {
        id: 9,
        title: "Assistance 24/7",
        description: "Support technique disponible en permanence",
        price: "Inclus",
        icon: "fas fa-phone-alt"
    }
];

// Variables globales
let currentUser = null;
let selectedCarForTest = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    loadUserSession();
});

function initializeApp() {
    setupNavigation();
    setupAuthentication();
    generateCars();
    generateServices();
    setupForms();
    setupAnimations();
}

// Navigation
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');
    
    // Gestion des liens de navigation
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetPage = link.getAttribute('data-page');
            
            // Vérifier si l'utilisateur essaie d'accéder à la demande d'essai sans être connecté
            if (targetPage === 'demande-essai' && !currentUser) {
                openAuthModal();
                return;
            }
            
            switchPage(targetPage);
        });
    });

    // CORRECTION : Gestion du bouton "Découvrir nos Voitures" dans le hero
    const heroBtns = document.querySelectorAll('[data-page="voitures"]');
    heroBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            switchPage('voitures');
        });
    });

    // Gestion du bouton "Voir Tout" dans le dashboard des bookings
    const viewAllBtn = document.querySelector('[data-section="bookings"]');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchPage('bookings');
        });
    }
}

function switchPage(targetPage) {
    const pages = document.querySelectorAll('.page');
    const navLinks = document.querySelectorAll('.nav-link');

    // Cacher toutes les pages
    pages.forEach(page => {
        page.classList.remove('active');
    });

    // Afficher la page cible
    const targetPageElement = document.getElementById(targetPage);
    if (targetPageElement) {
        targetPageElement.classList.add('active');
        
        // Animer l'entrée de la page
        setTimeout(() => {
            targetPageElement.style.animation = 'fadeInUp 0.5s ease-out';
        }, 50);
    }

    // Mettre à jour la navigation
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-page') === targetPage) {
            link.classList.add('active');
        }
    });

    // Scroll vers le haut
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Authentification
function setupAuthentication() {
    const loginBtn = document.getElementById('login-btn');
    const logoutBtn = document.getElementById('logout-btn');
    const modal = document.getElementById('auth-modal');
    const closeBtn = document.querySelector('.close');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    loginBtn.addEventListener('click', openAuthModal);
    logoutBtn.addEventListener('click', logout);
    closeBtn.addEventListener('click', closeAuthModal);
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeAuthModal();
        }
    });

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            switchAuthTab(targetTab);
        });
    });

    loginForm.addEventListener('submit', handleLogin);
    registerForm.addEventListener('submit', handleRegister);
}

function openAuthModal() {
    const modal = document.getElementById('auth-modal');
    modal.style.display = 'block';
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    modal.style.display = 'none';
}

function switchAuthTab(tab) {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => btn.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));

    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    document.getElementById(`${tab}-tab`).classList.add('active');
}

// FONCTION CORRIGÉE - handleLogin
function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    console.log('Tentative de connexion avec:', email); // Debug

    fetch('api/auth/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug
        return response.json();
    })
    .then(data => {
        console.log('Login response:', data); // Debug
        if (data.success) {
            login(data.user);
            closeAuthModal();
            showNotification('Connexion réussie !', 'success');
            
            if (selectedCarForTest) {
                switchPage('demande-essai');
            }
        } else {
            showNotification(data.message || 'Erreur de connexion', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

// FONCTION CORRIGÉE - handleRegister
function handleRegister(e) {
    e.preventDefault();
    const name = document.getElementById('register-name').value;
    const email = document.getElementById('register-email').value;
    const phone = document.getElementById('register-phone').value;
    const password = document.getElementById('register-password').value;

    console.log('Tentative d\'inscription avec:', { name, email, phone }); // Debug

    fetch('api/auth/register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ name, email, phone, password })
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug
        return response.json();
    })
    .then(data => {
        console.log('Register response:', data); // Debug
        if (data.success) {
            login(data.user);
            closeAuthModal();
            showNotification('Inscription réussie !', 'success');
        } else {
            showNotification(data.message || 'Erreur d\'inscription', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur d\'inscription', 'error');
    });
}

function login(userData) {
    currentUser = userData;
    localStorage.setItem('supercar_user', JSON.stringify(userData));
    updateAuthUI();
}

// FONCTION CORRIGÉE - logout
function logout() {
    fetch('api/auth/logout.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        currentUser = null;
        localStorage.removeItem('supercar_user');
        updateAuthUI();
        switchPage('accueil');
        showNotification('Déconnexion réussie !', 'success');
    })
    .catch(error => {
        console.error('Erreur logout:', error);
        // Logout local en cas d'erreur
        currentUser = null;
        localStorage.removeItem('supercar_user');
        updateAuthUI();
        switchPage('accueil');
    });
}

// FONCTION CORRIGÉE - loadUserSession
function loadUserSession() {
    // Vérifier d'abord avec le serveur
    checkSession();
}

// NOUVELLE FONCTION - checkSession
function checkSession() {
    fetch('api/auth/check_session.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.authenticated) {
            login(data.user);
        } else {
            // Si pas de session serveur, nettoyer le localStorage
            localStorage.removeItem('supercar_user');
            currentUser = null;
            updateAuthUI();
        }
    })
    .catch(error => {
        console.error('Erreur vérification session:', error);
        // En cas d'erreur, essayer le localStorage comme fallback
        const savedUser = localStorage.getItem('supercar_user');
        if (savedUser) {
            currentUser = JSON.parse(savedUser);
            updateAuthUI();
        }
    });
}

function updateAuthUI() {
    const loginBtn = document.getElementById('login-btn');
    const userInfo = document.getElementById('user-info');
    const username = document.getElementById('username');

    if (currentUser) {
        loginBtn.style.display = 'none';
        userInfo.style.display = 'flex';
        username.textContent = `Bonjour, ${currentUser.name}`;
    } else {
        loginBtn.style.display = 'block';
        userInfo.style.display = 'none';
    }
}

// FONCTION CORRIGÉE - Génération des voitures depuis l'API
async function generateCars() {
    const carsGrid = document.getElementById('cars-grid');
    const carSelect = document.getElementById('car-select');
    
    carsGrid.innerHTML = '';
    
    try {
        const response = await fetch('api/cars/get_cars.php');
        const data = await response.json();
        
        if (data.success && data.data) {
            // Utiliser les données de l'API
            data.data.forEach((car, index) => {
                // Créer la carte de voiture
                const carCard = createCarCard(car);
                carsGrid.appendChild(carCard);

                // Ajouter à la liste de sélection
                const option = document.createElement('option');
                option.value = car.id;
                option.textContent = car.name;
                carSelect.appendChild(option);

                // Animation d'entrée
                setTimeout(() => {
                    carCard.style.animation = 'fadeInUp 0.6s ease-out';
                }, index * 100);
            });
        } else {
            throw new Error('Erreur API ou données vides');
        }
    } catch (error) {
        console.error('Erreur chargement voitures depuis API:', error);
        console.log('Utilisation des données de fallback');
        
        // Fallback sur les données statiques si l'API ne marche pas
        carsData.forEach((car, index) => {
            // Créer la carte de voiture
            const carCard = createCarCard(car);
            carsGrid.appendChild(carCard);

            // Ajouter à la liste de sélection
            const option = document.createElement('option');
            option.value = car.id;
            option.textContent = car.name;
            carSelect.appendChild(option);

            // Animation d'entrée
            setTimeout(() => {
                carCard.style.animation = 'fadeInUp 0.6s ease-out';
            }, index * 100);
        });
    }
}

function createCarCard(car) {
    const carCard = document.createElement('div');
    carCard.className = 'car-card';
    carCard.innerHTML = `
        <img src="${car.image || car.image_url}" alt="${car.name}" class="car-image">
        <div class="car-info">
            <h3 class="car-name">${car.name}</h3>
            <p class="car-price">${car.price}</p>
            <div class="car-specs">
                <div class="spec-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>${car.power}</span>
                </div>
                <div class="spec-item">
                    <i class="fas fa-road"></i>
                    <span>${car.speed || car.max_speed}</span>
                </div>
                <div class="spec-item">
                    <i class="fas fa-rocket"></i>
                    <span>0-100: ${car.acceleration}</span>
                </div>
            </div>
            <button class="btn btn-primary btn-large test-drive-btn" data-car-id="${car.id}">
                Faire un Essai
            </button>
        </div>
    `;

    // Ajouter l'événement pour le bouton d'essai
    const testBtn = carCard.querySelector('.test-drive-btn');
    testBtn.addEventListener('click', () => {
        selectedCarForTest = car.id;
        
        if (!currentUser) {
            openAuthModal();
        } else {
            switchPage('demande-essai');
            // Pré-sélectionner la voiture dans le formulaire
            document.getElementById('car-select').value = car.id;
        }
    });

    return carCard;
}

// FONCTION CORRIGÉE - Génération des services depuis l'API
async function generateServices() {
    const servicesGrid = document.getElementById('services-grid');
    
    servicesGrid.innerHTML = '';
    
    try {
        const response = await fetch('api/services/get_services.php');
        const data = await response.json();
        
        if (data.success && data.data) {
            // Utiliser les données de l'API
            data.data.forEach((service, index) => {
                const serviceCard = createServiceCard(service);
                servicesGrid.appendChild(serviceCard);

                // Animation d'entrée
                setTimeout(() => {
                    serviceCard.style.animation = 'fadeInUp 0.6s ease-out';
                }, index * 100);
            });
        } else {
            throw new Error('Erreur API services');
        }
    } catch (error) {
        console.error('Erreur chargement services depuis API:', error);
        console.log('Utilisation des données de fallback services');
        
        // Fallback sur les données statiques
        servicesData.forEach((service, index) => {
            const serviceCard = createServiceCard(service);
            servicesGrid.appendChild(serviceCard);

            // Animation d'entrée
            setTimeout(() => {
                serviceCard.style.animation = 'fadeInUp 0.6s ease-out';
            }, index * 100);
        });
    }
}

function createServiceCard(service) {
    const serviceCard = document.createElement('div');
    serviceCard.className = 'service-card';
    serviceCard.innerHTML = `
        <i class="${service.icon} service-icon"></i>
        <h3 class="service-title">${service.title}</h3>
        <p class="service-description">${service.description}</p>
        <p class="service-price">${service.price}</p>
        <button class="btn btn-secondary">En Savoir Plus</button>
    `;

    return serviceCard;
}

// Configuration des formulaires
function setupForms() {
    const essaiForm = document.getElementById('essai-form');
    const contactForm = document.querySelector('.contact-form');

    if (essaiForm) {
        essaiForm.addEventListener('submit', handleEssaiSubmission);
    }

    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmission);
    }
}

// FONCTION ENTIÈREMENT CORRIGÉE - handleEssaiSubmission
function handleEssaiSubmission(e) {
    e.preventDefault();
    
    if (!currentUser) {
        showNotification('Vous devez être connecté pour faire une demande d\'essai', 'error');
        openAuthModal();
        return;
    }
    
    const formData = new FormData(e.target);
    const data = {
        car: formData.get('car'),
        date: formData.get('date'),
        time: formData.get('time'),
        phone: formData.get('phone'),
        message: formData.get('message') || ''
    };

    console.log('Données envoyées:', data); // Debug
    console.log('Utilisateur actuel:', currentUser); // Debug

    // Validation côté client
    if (!data.car || data.car === '') {
        showNotification('Veuillez sélectionner une voiture', 'error');
        return;
    }

    if (!data.date) {
        showNotification('Veuillez sélectionner une date', 'error');
        return;
    }

    if (!data.time || data.time === '') {
        showNotification('Veuillez sélectionner une heure', 'error');
        return;
    }

    if (!data.phone) {
        showNotification('Veuillez saisir votre numéro de téléphone', 'error');
        return;
    }

    // Envoyer la demande d'essai au backend
    fetch('api/bookings/create_booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug
        return response.json();
    })
    .then(data => {
        console.log('Booking response:', data); // Debug
        if (data.success) {
            showNotification('Demande d\'essai envoyée avec succès ! Nous vous contacterons bientôt.', 'success');
            e.target.reset();
            selectedCarForTest = null; // Reset la sélection
        } else {
            showNotification(data.message || 'Erreur lors de l\'envoi', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'envoi de la demande', 'error');
    });
}

function handleContactSubmission(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        nom: formData.get('nom'),
        email: formData.get('email'),
        sujet: formData.get('sujet'),
        message: formData.get('message')
    };

    // Envoyer le message de contact au backend
    fetch('api/contact/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Message envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.', 'success');
            e.target.reset();
        } else {
            showNotification(data.message || 'Erreur lors de l\'envoi', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'envoi', 'error');
    });
}

// Notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
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
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}

// Animations et effets
function setupAnimations() {
    // Intersection Observer pour les animations au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Observer tous les éléments avec la classe fade-target
    document.querySelectorAll('.car-card, .service-card, .feature-card').forEach(el => {
        observer.observe(el);
    });
}

// Gestion responsive
function setupMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const nav = document.querySelector('.nav');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            nav.classList.toggle('mobile-open');
        });
    }
}

// Utilitaires
function formatPrice(price) {
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[+]?[0-9\s\-\(\)]{8,}$/;
    return re.test(phone);
}

// Fonction pour définir la date minimum (aujourd'hui)
function setMinDate() {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1); // Minimum demain
    
    const dateInput = document.getElementById('date');
    if (dateInput) {
        dateInput.min = tomorrow.toISOString().split('T')[0];
    }
}

// Appeler setMinDate au chargement
document.addEventListener('DOMContentLoaded', function() {
    setMinDate();
});

// Styles CSS additionnels pour les animations
const additionalStyles = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes fadeInUp {
        from { 
            transform: translateY(30px); 
            opacity: 0; 
        }
        to { 
            transform: translateY(0); 
            opacity: 1; 
        }
    }
    
    .mobile-open {
        display: block !important;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.95);
        padding: 1rem;
    }
    
    .mobile-open .nav-list {
        flex-direction: column;
        gap: 1rem;
    }
`;

// Ajouter les styles additionnels
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// Initialisation des fonctionnalités mobiles
setupMobileMenu();
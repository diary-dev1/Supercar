-- Base de données pour Supercar Elite
CREATE DATABASE IF NOT EXISTS supercar_elite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE supercar_elite;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des voitures
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    power VARCHAR(50),
    max_speed VARCHAR(50),
    acceleration VARCHAR(50),
    description TEXT,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des services
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price VARCHAR(100),
    icon VARCHAR(100),
    category VARCHAR(50),
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des demandes d'essai
CREATE TABLE test_drives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    test_date DATE NOT NULL,
    test_time TIME NOT NULL,
    phone VARCHAR(20),
    message TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Table des messages de contact
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des voitures
INSERT INTO cars (name, brand, price, image_url, power, max_speed, acceleration, description) VALUES
('Ferrari F8 Tributo', 'Ferrari', 280000.00, 'https://images.pexels.com/photos/544542/pexels-photo-544542.jpeg', '720 ch', '340 km/h', '2.9s', 'La Ferrari F8 Tributo est l\'aboutissement de l\'excellence italienne en matière de supercars.'),
('Lamborghini Huracán', 'Lamborghini', 220000.00, 'https://images.pexels.com/photos/1592384/pexels-photo-1592384.jpeg', '640 ch', '325 km/h', '3.2s', 'La Lamborghini Huracán incarne la puissance et l\'élégance du taureau italien.'),
('McLaren 570S', 'McLaren', 200000.00, 'https://images.pexels.com/photos/1009324/pexels-photo-1009324.jpeg', '570 ch', '328 km/h', '3.2s', 'La McLaren 570S offre une expérience de conduite pure et intense.'),
('Porsche 911 GT3', 'Porsche', 180000.00, 'https://images.pexels.com/photos/1334924/pexels-photo-1334924.jpeg', '510 ch', '318 km/h', '3.4s', 'La Porsche 911 GT3 est l\'essence même de la performance allemande.'),
('Aston Martin Vantage', 'Aston Martin', 160000.00, 'https://images.pexels.com/photos/1149137/pexels-photo-1149137.jpeg', '503 ch', '314 km/h', '3.6s', 'L\'Aston Martin Vantage allie élégance britannique et performances exceptionnelles.'),
('BMW M8 Competition', 'BMW', 150000.00, 'https://images.pexels.com/photos/1719648/pexels-photo-1719648.jpeg', '625 ch', '305 km/h', '3.2s', 'La BMW M8 Competition redéfinit le luxe sportif allemand.'),
('Mercedes-AMG GT R', 'Mercedes-AMG', 190000.00, 'https://images.pexels.com/photos/2920064/pexels-photo-2920064.jpeg', '585 ch', '318 km/h', '3.6s', 'La Mercedes-AMG GT R est la bête verte de Stuttgart.'),
('Audi R8 V10', 'Audi', 170000.00, 'https://images.pexels.com/photos/1534604/pexels-photo-1534604.jpeg', '570 ch', '324 km/h', '3.4s', 'L\'Audi R8 V10 combine technologie de pointe et design futuriste.'),
('Nissan GT-R NISMO', 'Nissan', 200000.00, 'https://images.pexels.com/photos/1146603/pexels-photo-1146603.jpeg', '600 ch', '315 km/h', '2.8s', 'La Nissan GT-R NISMO est la supercar japonaise ultime.');

-- Insertion des services
INSERT INTO services (title, description, price, icon, category) VALUES
('Maintenance Premium', 'Service complet de maintenance avec pièces d\'origine et techniciens certifiés', 'À partir de 500€', 'fas fa-tools', 'maintenance'),
('Assurance Elite', 'Couverture complète pour votre véhicule de luxe avec assistance 24h/24', 'À partir de 200€/mois', 'fas fa-shield-alt', 'assurance'),
('Livraison VIP', 'Livraison à domicile avec service conciergerie et présentation personnalisée', 'Gratuit', 'fas fa-truck', 'livraison'),
('Formation Pilotage', 'Cours de pilotage avec instructeurs professionnels sur circuit privé', 'À partir de 800€', 'fas fa-graduation-cap', 'formation'),
('Stockage Sécurisé', 'Garde de votre véhicule dans nos installations climatisées et sécurisées', 'À partir de 300€/mois', 'fas fa-warehouse', 'stockage'),
('Personnalisation', 'Customisation selon vos préférences avec matériaux premium', 'Sur devis', 'fas fa-paint-brush', 'personnalisation'),
('Financement Premium', 'Solutions de financement sur mesure avec taux préférentiels', 'Taux préférentiels', 'fas fa-credit-card', 'financement'),
('Expertise Technique', 'Diagnostic et expertise par nos spécialistes certifiés', 'À partir de 150€', 'fas fa-microscope', 'expertise'),
('Assistance 24/7', 'Support technique et dépannage disponible en permanence', 'Inclus', 'fas fa-phone-alt', 'assistance');
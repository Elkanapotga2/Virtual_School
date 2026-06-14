CREATE DATABASE IF NOT EXISTS lms_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lms_db;

-- 1. Table des utilisateurs (Étudiants, Enseignants, Promoteurs)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('etudiant', 'enseignant', 'promoteur') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Table des modules de cours (gérés par le Promoteur)
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Table des leçons (gérées par l'Enseignant)
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    enseignant_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    type_support ENUM('pdf', 'video') NOT NULL,
    support_url VARCHAR(255) NOT NULL,
    quiz_question TEXT NOT NULL,
    quiz_reponse VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (enseignant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Table des notes et de progression (gérée par l'Étudiant lors du quiz)
CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    lesson_id INT NOT NULL,
    note_obtenue INT NOT NULL, -- Note sur 100 pour donner le pourcentage directement
    validated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_eval (etudiant_id, lesson_id)
) ENGINE=InnoDB;

-- Insertion du compte Promoteur par défaut (pour pouvoir configurer les modules)
-- Le mot de passe par défaut est 'admin123' (haché en bcrypt)
INSERT INTO users (username, password, role) 
VALUES ('promoteur', '$2y$10$wE9fLg9X20H6iRkHbyB/Iu1mRreOUnfS2eS2b3vK2f.wX38/92YvG', 'promoteur')
ON DUPLICATE KEY UPDATE id=id;
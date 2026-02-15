# ⏳ TimeCapsule

![Symfony](https://img.shields.io/badge/Symfony-000000?style=for-the-badge&logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)

> **"Send a message to the future."**
> TimeCapsule est une application web permettant d'envoyer des messages, des photos ou des documents qui ne s'ouvriront qu'à une date précise dans le futur.

## 🌟 Concept & Fonctionnalités

L'objectif est de créer une "capsule temporelle numérique".
* 🔒 **Verrouillage Temporel :** Les capsules restent cryptées/inaccessibles jusqu'à la date d'ouverture définie.
* 📩 **Notification :** Envoi d'email automatique le jour J pour prévenir le destinataire.
* 👥 **Espace Membre :** Inscription, gestion des capsules envoyées et reçues.
* 🛡️ **Sécurité :** Authentification robuste et protection des données.

## 🛠️ Stack Technique

* **Backend :** Symfony 6/7 (PHP 8.2)
* **ORM :** Doctrine
* **Frontend :** Twig, Bootstrap 5
* **Base de données :** MySQL

## 🚀 Installation & Configuration

Avant de commencer, assurez-vous d'avoir **PHP**, **Composer** et **Node.js** installés.

### 1. Cloner le projet
```bash
git clone [https://github.com/barakatyoussef/TimeCapsule.git](https://github.com/barakatyoussef/TimeCapsule.git)
cd TimeCapsule
```
### 2. Installer les dépendances
Installez les librairies Backend (Symfony) et Frontend (Assets) :
```bash
composer install
npm install
```
### 3. Configuration (.env)
Dupliquer .env en .env.local et configurer la base de données :
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/timecapsule_db"
MAILER_DSN=smtp://user:pass@smtp.example.com
```
### 4. Base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```
### 5. Lancer le serveur
```bash
symfony server:start
```
## 👥 Auteurs & Contribution

| Membre | Rôle Principal | Liens |
| :--- | :--- | :--- |
| **Youssef Barakat** | **Lead Developer** (Backend Logic, API, Database) | [GitHub](https://github.com/barakatyoussef) |
| **Imad Rachid** | **Developer** (Conception UML, Frontend UI & Tests, Documentation) | [GitHub](https://github.com/Rachid-Imad) |

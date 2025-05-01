
# TirFly✈️

Agence de voyages moderne et innovante intégrant des fonctionnalités d'IA et des services de qualité de vie 🌐

## Table de matieres

- [Description](#description)
- [Screenshots](#screenshots)
- [Tech Stack](#tech-stack)
- [Prerequis](#prerequis)
- [Installation](#installation)
- [Contributions](#contributions)
- [License](#license)

## Description

Développé dans le cadre d'un projet PIdev 2024-25 Esprit (Esprit School of Engineering) sous la supervision de la professeure Emna Rbii durant une semestre. L'objectif était de créer une application web full-stack permettant aux employés de l'agence d'effectuer des opérations CRUD avec les offres proposées par l'agence elle-même, et aux clients de l'application de réserver des offres de voyages, hebergements, packs et evennements. L'application est développée avec Symfony, Bootstrap et Tailwind CSS, et communique avec plusieurs APIs externes .


## Screenshots

## Tech Stack

### Frontend🖥

- Symfony
- Twig 
- Tailwind CSS (Front office)
- Bootstrap 5 (Back office)

### backend🗄️
- Python
- PHP
- OAuth 2.0
- JWT
- Llama LLM
- TensorFlow
- WebSocket

## Prerequis

Avant de procéder à l'installation, assurez-vous d'avoir les éléments suivants installés sur votre machine :

* PHP ^8.1 avec Composer
* Symfony CLI
* Python 3.8+
* Pip (Python package installer)
* Git
* OpenSSL (pour la génération des clés JWT)

## Installation

#### 1. Clonage du projet principal

```bash
git clone https://github.com/Koussay-Akchi/tirfly-web
cd tirfly
```

#### 2. Installation des dépendances PHP

```bash
composer install
```

#### 3. Génération des mots de passe hachés pour les utilisateurs

```bash
php bin/console app:hash-user-passwords
```

#### 4. Génération des clés pour l’authentification JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

#### 5. Démarrage du serveur WebSocket

```bash
php bin/console app:websocket:server
```

---

#### 6. Clonage du modele AI

```bash
git clone https://github.com/Koussay-Akchi/llama-destinations
cd llm-project
```

#### 7. Activation de l’environnement et Lancement

```bash
venv\Scripts\activate
python app3.py
```

#### 8. Lancement du serveur Symfony

```bash
symfony serve
```


## Contributions

Les contributions sont toujours les bienvenues !

Vous pouvez dupliquer (Fork) ce projet et y contribuer. Nous examinerons rapidement votre demande.


## License

Ce projet utilise la licence [MIT](https://choosealicense.com/licenses/mit/)


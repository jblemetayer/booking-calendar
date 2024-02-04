# Calendrier de réservation

Logiciel web libre permettant de réserver des dates dans un calendrier.

## License

Logiciel libre sous license AGPL V3

## Installation

Dépendances :

- php >= 5.6
- php-pdo
- php-sqlite3

Sur debian :

```
sudo aptitude install php php-pdo php-sqlite3
```

Récupération des sources :

```
git clone https://github.com/jblemetayer/booking-calendar.git
```

Pour le lancer :

```
php -S localhost:8000 -t public
```

## Configuration

Création du dossier tmp utilisé par fatfree pour stocker les fichiers compilés, etc :

```
mkdir tmp
sudo chown www-data:username tmp/
```

Il faut créer le fichier de configuration config/app.conf à partir du modèle existant (app.conf.example) et l'adapter selon vos usages :

```
cp config/app.conf.example config/app.conf
```

## Base de données

La base de données SQLite est embarquée dans le projet. Il faut la créer à partir du modèle qui contient son schéma relationnel :

```
cp database/datas.sqlite.example database/datas.sqlite
```

## Déployer avec apache

```
DocumentRoot /path/to/booking-calendar/public
DirectoryIndex index.php

<Directory /path/to/booking-calendar/public>
  AllowOverride All
  Require all granted
</Directory>

<Location "/admin/">
  AuthType Basic
  AuthName "Restricted Access"
  AuthUserFile /path/to/file/.htpasswd
  Require valid-user
</Location>
```

Pour sécuriser l'accès à l'administration il faut créer le fichier .htpasswd contenant les identifiants d'accès à l'administration :

```
sudo htpasswd -c /path/to/file/.htpasswd username
```

Activer le mode rewrite pour la prise en charge du routage :

```
sudo a2enmod rewrite
```

## Librairies utilisées

- **Fat-Free** micro framework PHP : https://github.com/bcosca/fatfree (GPLv3)
- **Bootstrap** framework html, css et javascript : https://getbootstrap.com/ (MIT)
- **FullCalendar** librairie javascript de calendrier d'événements : https://github.com/fullcalendar/fullcalendar (MIT)

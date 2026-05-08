# cm2e-php

## MQTT vers base de donnees

Le fichier [mqtt/bridge.php](mqtt/bridge.php) fait le lien entre MQTT et la base:

- Ecoute `pointage/scan` (payload: `UID`)
- Ecoute `pointage/button` (payload: `UID|absent` ou `UID|present`)
- Lit les horaires depuis la base (priorite: `pointages.assigned_time`, puis `working_time_assignments.start_time`, puis `user_settings.arrival_time`)
- Ecrit ou met a jour la table `pointages`
- Republie l'horaire assigne vers `pointage/assigned_time` (payload: `UID|HH:MM`)

### Installation

1. Installer les dependances PHP:

```bash
composer install
```

### Lancement

1. Definir les variables si necessaire:

- `MQTT_HOST` (defaut `127.0.0.1`)
- `MQTT_PORT` (defaut `1883`)
- `MQTT_USERNAME` (optionnel)
- `MQTT_PASSWORD` (optionnel)

2. Lancer le bridge:

```bash
php mqtt/bridge.php
```

Le process doit rester actif pour que MQTT et la base restent synchronises.
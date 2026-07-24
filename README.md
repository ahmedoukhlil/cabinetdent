<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Gestion Multi-Instances de Cabinet Savwa

Ce système permet de gérer plusieurs instances de l'application de cabinet médical à partir d'un template central.

## Structure

```
.
├── template/           # Dépôt template principal
├── instances/         # Dossier contenant toutes les instances
│   ├── cabinet1/     # Instance du cabinet 1
│   ├── cabinet2/     # Instance du cabinet 2
│   └── ...
└── scripts/          # Scripts utilitaires
    └── sync-instances.sh
```

## Installation

1. **Configuration du Template**
```bash
# Cloner le template
git clone <URL_DU_REPO_TEMPLATE> template
cd template

# Créer la branche principale
git checkout -b main
git push -u origin main
```

2. **Création d'une Nouvelle Instance**
```bash
# Cloner le template
git clone <URL_DU_REPO_TEMPLATE> instances/nom-du-cabinet
cd instances/nom-du-cabinet

# Créer une branche spécifique
git checkout -b instance/nom-du-cabinet

# Configurer l'environnement
cp .env.example .env
# Modifier les configurations dans .env

# Commit initial
git add .
git commit -m "Initial commit - Instance nom-du-cabinet"
git push -u origin instance/nom-du-cabinet
```

## Synchronisation

Pour synchroniser toutes les instances avec le template :

```bash
# Rendre le script exécutable
chmod +x scripts/sync-instances.sh

# Exécuter la synchronisation
./scripts/sync-instances.sh
```

Le script va :
1. Mettre à jour le template
2. Parcourir toutes les instances
3. Fusionner les modifications du template dans chaque instance
4. Gérer les conflits si nécessaire

## Gestion des Conflits

Si des conflits surviennent lors de la synchronisation :

1. Le script marquera l'instance comme ayant des conflits
2. Vous devrez résoudre manuellement les conflits dans cette instance
3. Une fois les conflits résolus :
```bash
git add .
git commit -m "Résolution des conflits"
git push origin instance/nom-du-cabinet
```

## Bonnes Pratiques

1. **Modifications Spécifiques**
   - Faire les modifications spécifiques à une instance dans sa branche
   - Ne jamais modifier directement la branche main

2. **Mises à Jour du Template**
   - Faire les modifications communes dans le template
   - Utiliser le script de synchronisation pour propager les changements

3. **Configuration**
   - Garder les configurations spécifiques dans le fichier .env
   - Ne jamais commiter le fichier .env

4. **Sauvegarde**
   - Faire des sauvegardes régulières de chaque instance
   - Vérifier les logs de synchronisation

## Support

Pour toute question ou problème, veuillez contacter l'administrateur système.

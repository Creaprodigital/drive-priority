# DRIVER PRIORITY

Site PHP 8.2+ sans framework, créé à partir de la maquette fournie. Le dépôt initial ne contenait aucun commit ni site à conserver. Identité noir, blanc et or, gabarits partagés, responsive, FAQ native accessible au clavier, formulaire PHP avec SMTP TLS.

## Développement

PHP 8.2+ avec `session`, `openssl`, `mbstring` ; Python 3 et OpenSSL pour les tests. Aucun service de base de données ni installation npm n’est nécessaire à l’application. PHPMailer 6.10.0 est inclus dans `vendor/phpmailer` avec sa licence LGPL 2.1.

Depuis la racine du dépôt :

```sh
php -S 0.0.0.0:8080 router.php
```

Dans l’environnement cloud préparé, PHP est accessible via `/workspace/.tools/bin/php`. Les bibliothèques système nécessaires sont extraites localement, sans modification du système.

```sh
PHP_BIN=/workspace/.tools/bin/php python3 tests/smoke.py
```

Les tests démarrent deux serveurs PHP temporaires et un SMTP TLS local avec certificat de test. Ils n’envoient aucun email extérieur. Ports de test : 8090, 8091 et 25252. Tests : 12 routes et métadonnées, 404 et fichiers privés, CSRF, antispam, échappement et validation, succès SMTP, absence de configuration et limitation d’envoi. Les certificats de test ne sont jamais utilisés par l’application en production.

## Configuration et Hostinger

Les coordonnées de l’entreprise sont centralisées dans `config/site.php`. Injecter les variables documentées dans `.env.example` au processus PHP. Ce fichier n’est pas chargé automatiquement. Sur un hébergement mutualisé, copier `config/local.example.php` vers `config/local.php` et compléter ce fichier privé directement sur le serveur ; il est ignoré par Git. Les variables du processus ont priorité.

- `SITE_URL` : URL HTTPS définitive pour canonical, Open Graph et sitemap. Par défaut, URL locale de développement.
- `SMTP_HOST`, `SMTP_PORT` (587 par défaut), `SMTP_SECURITY` (`tls` ou `ssl`), `SMTP_USER`, `SMTP_PASSWORD` : paramètres du fournisseur de messagerie réel.
- `SMTP_FROM` : adresse expéditrice autorisée ; `CONTACT_TO` : destinataire des demandes. Aucune adresse n’est inventée ou préconfigurée.
- `CONTACT_RATE_DIR` : répertoire privé, accessible en écriture, hors de la racine web. À défaut : répertoire système temporaire. Chaque adresse réseau est limitée à 5 tentatives valides par heure, y compris en cas d’échec SMTP ; ce compteur résiste aux changements de session. Ne pas activer un proxy de confiance sans adapter l’identification réseau à la configuration réelle.

Le formulaire n’enregistre pas les messages dans une base et ne journalise pas les identifiants SMTP. Une confirmation n’est affichée qu’après acceptation par le serveur SMTP ; cela ne garantit pas la délivrance finale au destinataire. TLS et la vérification du certificat restent activés.

Sur Hostinger, utiliser PHP 8.2+ et Apache/LiteSpeed avec réécriture et `.htaccess` activées. Transférer le site (y compris `.htaccess`, assets et vendor) dans le dossier web. Les dossiers internes sont interdits par `.htaccess`. Ne pas transférer `.git`, `tests`, `.env`, ni les fichiers de développement inutiles. Avec un serveur autre qu’Apache/LiteSpeed, configurer les mêmes interdictions et le contrôleur frontal avant de publier.

Activer HTTPS, cookies sécurisés, `display_errors=Off`, sauvegardes et permissions minimales. Prévoir une tâche planifiée supprimant uniquement les fichiers `.json` de `CONTACT_RATE_DIR` inactifs depuis plus d’une heure (ex. `find /chemin/prive/compteurs -type f -name '*.json' -mmin +65 -delete`). Ne pas utiliser cette commande sur un répertoire partagé contenant d’autres données.

Vérifier après déploiement les URLs propres, les pages privées inaccessibles, la canonical, le sitemap et un véritable envoi sur la messagerie configurée. L’hébergement Hostinger et l’envoi extérieur ne sont pas testés par les tests locaux.

## Pages et visuels

`/`, `/services`, les trois pages de services, `/vehicules`, `/destinations`, `/faq`, `/contact`, `/mentions-legales`, `/politique-confidentialite`, `/cgv`, `/sitemap.xml`, `/robots.txt`.

Les images WebP sont des illustrations générées ou retouchées pour ce projet, inspirées de la maquette. La Classe V reprend le modèle de la photo fournie par l’utilisateur, aussi bien dans les cartes véhicules que dans la scène du hero partagée par les bannières et la page Contact. Ces visuels ne prouvent ni la possession, ni la disponibilité, ni le millésime des véhicules. Les capacités sont présentées à titre indicatif et soumises à confirmation. Aucun faux avis ou engagement commercial non confirmé n’est ajouté. Aucun traceur non essentiel, police distante ou carte embarquée n’est chargé. Le lien Itinéraire n’ouvre Google Maps que sur action de l’utilisateur.

## À compléter avant publication

Les pages juridiques sont des projets. Vérifier forme juridique, RCS, responsable de publication, inscription VTC, assurances, email, horaires, médiateur effectivement désigné, clauses de paiement/annulation/attente, droit de rétractation selon la qualification de chaque prestation, conservation des demandes et localisation/sous-traitants. Les coordonnées de l’entité Hostinger contractante n’ont pas été vérifiées : les compléter depuis le contrat et les sources officielles. Ne pas considérer ces textes comme une validation juridique.

Le site n’est ni publié sur Hostinger ni poussé sur GitHub par cette livraison.

Contrôles navigateur facultatifs (serveur 8080 actif) :

```sh
NODE_PATH=/workspace/.tools/browser/node_modules node tests/browser.cjs
```

Ce contrôle utilise Playwright et Chromium (`/usr/bin/chromium`), déjà installés dans l’environnement préparé. Il vérifie les liens internes, les H1, les débordements mobiles, le menu et l’accordéon au clavier, puis enregistre les captures dans `/tmp/driver-*.png`.

## Cache et actualisation des visuels

Les liens des ressources utilisent une empreinte SHA-256 de leur contenu (`?v=…`), et non la date du fichier. La feuille `/styles.css` est générée en PHP à partir de `assets/style.css` et versionne chaque image de fond. Sa propre empreinte change aussi lorsqu’une image change : le hero, les cartes et les bannières suivent donc les mises à jour sans renommer manuellement les fichiers. Les pages HTML demandent une revalidation ; Contact reste en `no-store`. Les styles versionnés peuvent rester en cache longtemps.

Après le premier déploiement de ce correctif, purger une fois les caches Hostinger/LiteSpeed/CDN déjà constitués : du code PHP ne peut pas retirer une ancienne page HTML servie en amont sans exécution de PHP. Conserver les paramètres de requête dans les clés du cache CDN. Les déploiements suivants utilisent automatiquement de nouvelles URL lorsque le contenu change. Ces réglages doivent être contrôlés sur l’hébergement réel après déploiement.

Tests du renouvellement des URL, y compris avec dates et tailles de fichiers conservées : `PHP_BIN=/workspace/.tools/bin/php python3 tests/cache.py`. Les tests HTTP de `tests/smoke.py` vérifient aussi les en-têtes et les images CSS versionnées.

La carte Mercedes Classe V possède désormais son propre visuel parisien (`assets/classe-v-paris-card.webp`), avec le van seul et entier. Ce visuel est indépendant du hero et partagé entre l’accueil et la page Véhicules. Son URL est versionnée automatiquement comme les autres images de fond.

Les Classes C et E disposent également de leurs propres illustrations parisiennes (`classe-c-paris-card.webp` et `classe-e-paris-card.webp`), avec chaque berline entière et un cadrage identique à la Classe V. Ces images n’affectent pas le hero.

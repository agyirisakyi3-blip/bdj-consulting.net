# BDJ Consulting — Site officiel (V6.1.5)

Site vitrine statique du centre de relation client **BDJ Consulting**, basé à Abidjan (Côte d'Ivoire).
11 pages HTML + 2 handlers PHP (`send_contact.php`, `send_recruitment.php`), sans framework.

Visuel : https://bdj-consulting.net/

---

## Fonctionnalités

- **7 axes de contenu** : Accueil, Nos solutions, Nos secteurs, À propos, Notre savoir-faire, Carrières, Contact + mentions légales et politique de confidentialité
- **Widget WhatsApp** : bouton flottant, badge, horaires, assistant qui répond aux questions courantes avant tout transfert vers un conseiller (`wa.me/2250102440707`)
- **Réseaux sociaux** en pied de page : Facebook, Instagram, X, TikTok, LinkedIn, YouTube (`bdjconsulting`)
- **Formulaires opérationnels** :
  - Contact → 2 destinataires + accusé de réception automatique au visiteur
  - Recrutement → 2 destinataires + CV (PDF/DOC/DOCX, max 5 Mo) + accusé de réception automatique au candidat
- **SEO complet** : titles/descriptions/canonicals uniques, Open Graph + Twitter Card, JSON-LD (`ProfessionalService`, `WebSite`, `ContactPage`, `BreadcrumbList` ×6), sitemap.xml, robots.txt
- **SEO local** : NAP unifié (`+225 01 02 44 07 07`), géolocalisation, zone de service Abidjan / Côte d'Ivoire
- **Bandeau cookies** + politique de confidentialité

## Structure

```
├── *.html                  # 11 pages du site
├── assets/
│   ├── css/style.css
│   ├── js/main.js          # nav, carrousel, widgets (WhatsApp, cookies, anti-spam ts)
│   ├── images/             # logos
│   └── favicon/
├── config/                 # PROTÉGÉ (.htaccess = Require all denied)
│   ├── config.php          # config centrale (non versionnée — secrets)
│   ├── mail_config.php     # destinataires + SMTP (non versionnée — secrets)
│   └── mail_helper.php     # envoi : PHPMailer/IONOS sinon mail()
├── send_contact.php        # handler formulaire contact
├── send_recruitment.php    # handler formulaire recrutement
├── .htaccess               # headers de sécurité, CSP, blocage /config/
├── sitemap.xml
└── robots.txt
```

## Configuration e-mail (à faire sur le serveur)

`config/config.php` et `config/mail_config.php` **ne sont pas versionnés** (secrets). Ils doivent être
recréés sur l'hébergement. Le dossier `config/` référence le modèle dans `CONFIGURATION_EMAIL.md`.

Envoi via le transport mail local IONOS avec `config/mail_helper.php` et PHPMailer. Aucun mot de passe SMTP n'est stocké dans le site.

## Sécurité

- `.htaccess` : `Options -Indexes`, CSP (`script-src 'self'`), `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, blocage `/config/`
- Dossier `config/` verrouillé (accès direct 403)
- Formulaires : honeypot + champ horodaté `ts` (anti-bot), suppression CRLF (anti header injection), `FILTER_VALIDATE_EMAIL`
- Upload CV : extension + MIME (finfo) + taille max, jamais écrit sur disque (envoyé en pièce jointe uniquement)
- Aucun secret dans le dépôt (configs dans `.gitignore`)

## Mise en ligne (checklist)

1. Hébergement → docroot du domaine `bdj-consulting.net` (HTTPS)
2. Recréer `config/config.php` + `config/mail_config.php` (modèles dans `CONFIGURATION_*.md`)
3. Adresse définitive renseignée (Faya Palace, Cocody, Abidjan) dans `config.php` + JSON-LD (`address`, `openingHoursSpecification`) sur toutes les pages
4. Brancher IONOS SMTP + PHPMailer (voir ci-dessus)
5. Vérifier : réception des 2 destinataires pour chaque formulaire + réception de l'accusé automatique sur l'adresse saisie dans le formulaire
6. Valider HTTPS + headers (avec CSP) + Google Business Profile (NAP identique)
7. Soumettre sitemap.xml dans Google Search Console

## CI/CD — déploiement automatique (GitHub Actions)

Le workflow `.github/workflows/deploy.yml` se déclenche sur chaque **push sur `main`** (ou manuellement via
*l'onglet Actions → Run workflow*). Deux jobs :

1. **Vérifications** (`check`) : lint PHP (`php -l`), lint JS (`node --check`), validation des blocs
   JSON-LD et du sitemap (`.github/scripts/validate.mjs`).
2. **Déploiement FTP/FTPS** vers l'hébergement IONOS (`deploy`) — ne démarre que si les vérifications passent.

### Secrets à créer (GitHub → Settings → Secrets and variables → Actions)

| Secret | Valeur |
| --- | --- |
| `FTP_HOST` | Hôte FTP IONOS (ex. `ftp://...` ou IP fournie par IONOS) |
| `FTP_USER` | Identifiant FTP |
| `FTP_PASSWORD` | Mot de passe FTP |
| `FTP_SERVER_DIR` | Dossier racine du domaine sur le serveur (ex. `/`, `/htdocs` ou `/bdj-consulting.net`) |

### Fichiers exclus du déploiement (jamais écrasés ni supprimés)

- `config/config.php`, `config/mail_config.php` → **à déposer une fois à la main** sur le serveur (SMTP, destinataires, secrets)
- `bdj-deploy.zip`, `.git`, `.github`

### Notes

- Premier déploiement : l'outil supprime les fichiers présents sur le serveur qui n'existent pas dans le dépôt,
  sauf les exclus ci-dessus — penser à activer HTTPS et PHP 8.1+ dans le panneau IONOS avant.
- Si IONOS refuse `ftps`, passer le champ `protocol: ftp` (ou `sftp` si votre offre IONOS fournit SSH/SFTP) dans le workflow.

## Commandes de vérification

```bash
php -l config/mail_helper.php send_contact.php send_recruitment.php
node .github/scripts/validate.mjs
```

---
© 2026 BDJ Consulting. Tous droits réservés.
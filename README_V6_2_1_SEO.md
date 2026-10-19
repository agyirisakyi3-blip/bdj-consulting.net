# BDJ Consulting — V6.2.1 SEO

Base : V6.1.24 production stable.

## Objectif
Optimiser le référencement naturel sans modifier le fonctionnement validé des formulaires Contact et Recrutement, ni le transport mail local IONOS, ni les protections de production.

## Modifications
- Titles et meta descriptions optimisés pour les requêtes centre d’appel, BPO, relation client, externalisation, Abidjan et Côte d’Ivoire.
- Open Graph et Twitter alignés sur les nouveaux titres/descriptions.
- Enrichissement éditorial de Savoir-faire, Solutions, Secteurs et À propos.
- Renforcement du maillage interne vers Solutions, Savoir-faire, Secteurs et Contact.
- FAQ enrichie d’une question sur les opérations externalisables.
- Contenu local renforcé sur Abidjan et la Côte d’Ivoire.
- Sitemap mis à jour avec les dates de modification des pages SEO.

## Non modifié volontairement
- send_contact.php
- send_recruitment.php
- config/mail_helper.php
- config/mail_config.php
- config/email_templates.php
- vendor/ et PHPMailer
- .htaccess de sécurité

## Validation
- Syntaxe PHP validée avec `php -l`.
- Chaque page HTML commerciale conserve un seul H1.
- Title et meta description présents sur les pages.
- Aucun changement au parcours des formulaires.

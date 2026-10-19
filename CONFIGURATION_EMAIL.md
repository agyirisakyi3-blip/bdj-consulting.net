# Configuration e-mail — BDJ Consulting V6.1.24

## Production

Le site utilise le transport mail local de l'hébergement IONOS via PHPMailer. Aucun mot de passe SMTP n'est stocké dans le projet.

Expéditeur : `no-reply@bdj-consulting.net`

Destinataires Contact : `contact@bdj-consulting.net`, `commercial@bdj-consulting.net`

Destinataires Recrutement : `recrutement@bdj-consulting.net`, `rh@bdj-consulting.net`

Le formulaire de recrutement transmet le CV en pièce jointe et envoie une confirmation automatique au candidat.

## Sécurité

Les anciens outils de diagnostic SMTP et l'ancien fichier de secret SMTP ont été retirés de la version de production.

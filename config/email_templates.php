<?php
declare(strict_types=1);

/*
 * BDJ CONSULTING — MODÈLES D'E-MAILS AUTOMATIQUES
 *
 * Les modèles ci-dessous sont dynamiques. Les variables entre {{ }} sont
 * remplacées automatiquement par les données du formulaire.
 *
 * Variables disponibles :
 * {{nom}}, {{prenom}}, {{full_name}}, {{email}}, {{telephone}},
 * {{entreprise}}, {{service}}, {{objet}}, {{poste}}, {{site_name}},
 * {{contact_email}}, {{phone}}
 *
 * Vous pouvez modifier les sujets et les textes ici sans toucher aux
 * fichiers send_contact.php / send_recruitment.php.
 */

return [
    'branding' => [
        'logo_path' => __DIR__ . '/../assets/images/logo-bdj-final.png',
        'logo_cid'  => 'bdj-logo',
        'slogan'    => 'Glad to be one of them.',
    ],

    'contact_auto_reply' => [
        'subject' => 'Nous avons bien reçu votre demande — {{service}} | BDJ Consulting',
        'text' => <<<'TEXT'
Bonjour {{full_name}},

Nous vous remercions d’avoir contacté BDJ Consulting.

Nous confirmons la bonne réception de votre demande concernant : {{service}}.
Notre équipe a bien enregistré votre message et reviendra vers vous dans les meilleurs délais.

Service / objet : {{service}}

Cordialement,
BDJ Consulting
{{contact_email}}
{{phone}}
TEXT,
        'html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;line-height:1.7;">
  <p>Bonjour <strong>{{full_name}}</strong>,</p>
  <p>Nous vous remercions d’avoir contacté <strong>{{site_name}}</strong>.</p>
  <p>Nous confirmons la bonne réception de votre demande concernant le service suivant :</p>
  <div style="margin:22px 0;padding:16px 18px;background:#f8fafc;border-left:4px solid #7f1d1d;border-radius:4px;">
    <strong>{{service}}</strong>
  </div>
  <p>Notre équipe a bien enregistré votre message et reviendra vers vous dans les meilleurs délais.</p>
  <p style="margin-top:28px;">Cordialement,<br>
    <strong>{{site_name}}</strong><br>
    {{contact_email}}<br>
    {{phone}}
  </p>
</div>
HTML,
    ],

    'recruitment_auto_reply' => [
        'subject' => 'Candidature bien reçue — {{poste}} | BDJ Consulting',
        'text' => <<<'TEXT'
Bonjour {{full_name}},

Nous vous remercions pour votre candidature auprès de BDJ Consulting.

Nous confirmons la bonne réception de votre candidature et de votre CV pour le poste de : {{poste}}.

Notre équipe RH examinera votre dossier. Si votre profil correspond à nos besoins, nous vous contacterons pour la suite du processus.

Poste concerné : {{poste}}

Cordialement,
L’équipe RH — BDJ Consulting
recrutement@bdj-consulting.net
{{phone}}
TEXT,
        'html' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;line-height:1.7;">
  <p>Bonjour <strong>{{full_name}}</strong>,</p>
  <p>Nous vous remercions pour votre candidature auprès de <strong>{{site_name}}</strong>.</p>
  <p>Nous confirmons la bonne réception de votre candidature et de votre CV pour le poste de :</p>
  <div style="margin:22px 0;padding:16px 18px;background:#f8fafc;border-left:4px solid #7f1d1d;border-radius:4px;">
    <strong>{{poste}}</strong>
  </div>
  <p>Notre équipe RH examinera votre dossier. Si votre profil correspond à nos besoins, nous vous contacterons pour la suite du processus.</p>
  <p style="margin-top:28px;">Cordialement,<br>
    <strong>L’équipe RH — {{site_name}}</strong><br>
    recrutement@bdj-consulting.net<br>
    {{phone}}
  </p>
</div>
HTML,
    ],
];

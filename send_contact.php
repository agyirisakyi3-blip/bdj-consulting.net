<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/mail_config.php';
require __DIR__ . '/config/mail_helper.php';
$recipients = $config['contact_recipients'];

function clean_contact(string $v): string {
    return trim(preg_replace('/[\r\n\x00]+/', ' ', strip_tags($v)));
}

function is_bot_request(): bool {
    $website = trim((string)($_POST['website'] ?? ''));
    $ts = (int)($_POST['ts'] ?? 0);
    if ($website !== '') return true;
    if (!$ts || $ts > time() || time() - $ts > 2 * 24 * 3600) return true;
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

$nom = clean_contact($_POST['nom'] ?? '');
$entreprise = clean_contact($_POST['entreprise'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$telephone = clean_contact($_POST['telephone'] ?? '');
$objet = clean_contact($_POST['objet'] ?? '');
$message = clean_contact($_POST['message'] ?? '');

$errors = [];
if (is_bot_request()) $errors[] = 'Requête invalide.';
if (!bdj_rate_limit('contact:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'))) $errors[] = 'Trop de demandes envoyées. Merci de réessayer dans quelques minutes.';
if (!$nom) $errors[] = 'Nom obligatoire.';
if (!$email) $errors[] = 'Adresse e-mail invalide.';
if (!$message) $errors[] = 'Message obligatoire.';

if ($errors) {
    http_response_code(400);
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css">';
    echo '<div class="container" style="padding:70px 0"><div class="notice"><h2>Votre demande n’a pas pu être envoyée.</h2><ul>';
    foreach ($errors as $e) echo '<li>'.htmlspecialchars($e, ENT_QUOTES, 'UTF-8').'</li>';
    echo '</ul><a class="btn btn-primary" href="contact.html">Retour au formulaire</a></div></div>';
    exit;
}

$text  = "Nouvelle demande de contact depuis le site BDJ Consulting\n\n";
$text .= "Nom : $nom\n";
$text .= "Entreprise : $entreprise\n";
$text .= "Email : $email\n";
$text .= "Téléphone : $telephone\n";
$text .= "Objet : $objet\n\n";
$text .= "Message :\n$message\n\n";
$text .= "IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue') . "\n";
$text .= "Date : " . date('Y-m-d H:i:s') . "\n";

$subject = 'Nouvelle demande de contact BDJ Consulting — ' . ($objet ?: 'Site web');
$internalBranding = bdj_email_branding();
$internalHtml = '<!doctype html><html lang="fr"><head><meta charset="utf-8"></head><body style="margin:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;"><div style="max-width:680px;margin:30px auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;"><div style="background:#111827;padding:24px 30px;color:#fff;font-size:22px;font-weight:700;">Nouvelle demande de contact</div><div style="padding:30px;line-height:1.7;"><p><strong>Nom :</strong> '.htmlspecialchars($nom, ENT_QUOTES, 'UTF-8').'</p><p><strong>Entreprise :</strong> '.htmlspecialchars($entreprise, ENT_QUOTES, 'UTF-8').'</p><p><strong>Email :</strong> '.htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8').'</p><p><strong>Téléphone :</strong> '.htmlspecialchars($telephone, ENT_QUOTES, 'UTF-8').'</p><p><strong>Service / objet :</strong> '.htmlspecialchars($objet, ENT_QUOTES, 'UTF-8').'</p><div style="margin:20px 0;padding:16px;background:#f8fafc;border-left:4px solid #7f1d1d;white-space:pre-wrap;"><strong>Message</strong><br>'.nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')).'</div><p style="font-size:12px;color:#6b7280;">IP : '.htmlspecialchars((string)($_SERVER['REMOTE_ADDR'] ?? 'inconnue'), ENT_QUOTES, 'UTF-8').' · '.date('Y-m-d H:i:s').'</p>' . bdj_email_footer_html('BDJ Consulting', 'contact@bdj-consulting.net', '+225 01 02 44 07 07', $internalBranding['logo_cid'], $internalBranding['slogan']) . '</div></div></body></html>';
$internalImages = [];
if ($internalBranding['logo_path'] !== '' && is_file($internalBranding['logo_path'])) {
    $internalImages[] = ['path' => $internalBranding['logo_path'], 'cid' => $internalBranding['logo_cid'], 'name' => 'logo-bdj-consulting.png', 'mime' => 'image/png'];
}
$result = bdj_send_mail($config, $recipients, $subject, $text, $email, $nom, [], $internalHtml, $internalImages);

if (!$result['ok']) {
    error_log('BDJ send_contact: ' . $result['error']);
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css">';
    echo '<div class="container" style="padding:70px 0"><div class="notice"><h2>Envoi momentanément indisponible.</h2><p>La demande n’a pas pu être transmise. Vérifiez la configuration e-mail du serveur.</p><a class="btn btn-primary" href="contact.html">Retour</a></div></div>';
    exit;
}

// Accusé de réception automatique dynamique envoyé à l'adresse renseignée par le visiteur.
$templates = bdj_email_templates();
$template = $templates['contact_auto_reply'] ?? [];
$branding = bdj_email_branding();
$service = $objet !== '' ? $objet : 'Demande de contact';
$vars = [
    'nom' => $nom,
    'prenom' => '',
    'full_name' => $nom,
    'email' => (string)$email,
    'telephone' => $telephone,
    'entreprise' => $entreprise,
    'service' => $service,
    'objet' => $service,
    'poste' => '',
    'site_name' => (string)($config['from_name'] ?? 'BDJ Consulting'),
    'contact_email' => 'contact@bdj-consulting.net',
    'phone' => '+225 01 02 44 07 07',
];

$autoSubject = bdj_render_email_template((string)($template['subject'] ?? 'Nous avons bien reçu votre demande — BDJ Consulting'), $vars);
$autoText = bdj_render_email_template((string)($template['text'] ?? ''), $vars);
$autoHtmlBody = bdj_render_email_template((string)($template['html'] ?? ''), $vars, true);
$autoHtml = '<!doctype html><html lang="fr"><head><meta charset="utf-8"></head><body style="margin:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;"><div style="max-width:640px;margin:30px auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;"><div style="background:#111827;padding:24px 30px;color:#fff;"><div style="font-size:24px;font-weight:700;">BDJ Consulting</div><div style="margin-top:5px;font-size:13px;opacity:.85;">Glad to be one of them.</div></div><div style="padding:30px;">' . $autoHtmlBody . bdj_email_footer_html('BDJ Consulting', 'contact@bdj-consulting.net', '+225 01 02 44 07 07', $branding['logo_cid'], $branding['slogan']) . '</div></div></body></html>';

$embeddedImages = [];
if ($branding['logo_path'] !== '' && is_file($branding['logo_path'])) {
    $embeddedImages[] = [
        'path' => $branding['logo_path'],
        'cid' => $branding['logo_cid'],
        'name' => 'logo-bdj-consulting.png',
        'mime' => 'image/png',
    ];
}

$autoResult = bdj_send_mail($config, [$email], $autoSubject, $autoText, 'no-reply@bdj-consulting.net', 'BDJ Consulting', [], $autoHtml, $embeddedImages);
if (!$autoResult['ok']) {
    error_log('BDJ contact auto-reply: ' . $autoResult['error']);
}

echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css">';
echo '<div class="container" style="padding:70px 0"><div class="recruitment-banner">';
echo '<h2>Merci pour votre message !</h2>';
echo '<p>Votre demande a bien été transmise à BDJ Consulting.</p><br>';
echo '<a class="btn btn-light" href="index.html">Retour à l’accueil</a>';
echo '</div></div>';

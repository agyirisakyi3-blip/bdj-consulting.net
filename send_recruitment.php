<?php
declare(strict_types=1);

$mailConfig = require __DIR__ . '/config/mail_config.php';
require __DIR__ . '/config/mail_helper.php';
$recruitmentRecipients = $mailConfig['recruitment_recipients'];

function clean(string $v): string { return trim(preg_replace('/[\r\n\x00]+/', ' ', strip_tags($v))); }

function is_bot_request(): bool {
    $website = trim((string)($_POST['website'] ?? ''));
    $ts = (int)($_POST['ts'] ?? 0);
    if ($website !== '') return true;
    if (!$ts || $ts > time() || time() - $ts > 2 * 24 * 3600) return true;
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrieres.html');
    exit;
}

$nom=clean($_POST['nom']??'');
$prenom=clean($_POST['prenom']??'');
$email=filter_var(trim($_POST['email']??''), FILTER_VALIDATE_EMAIL);
$telephone=clean($_POST['telephone']??'');
$poste=clean($_POST['poste']??'');
$ville=clean($_POST['ville']??'');
$experience=clean($_POST['experience']??'');
$message=clean($_POST['message']??'');
$consentement=isset($_POST['consentement']) && $_POST['consentement']==='1';

$errors=[];
if(is_bot_request()) $errors[]='Requête invalide.';
if(!bdj_rate_limit('recrutement:'.($_SERVER['REMOTE_ADDR']??'0.0.0.0'))) $errors[]='Trop de candidatures envoyées. Merci de réessayer dans quelques minutes.';
$maxFileSize = (int)$mailConfig['max_cv_size'];
$allowedExtensions = ['pdf','doc','docx'];
$allowedMime = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
if(!$nom||!$prenom) $errors[]='Nom et prénom obligatoires.';
if(!$email) $errors[]='Adresse e-mail invalide.';
if(!$telephone) $errors[]='Téléphone obligatoire.';
if(!$poste) $errors[]='Poste recherché obligatoire.';
if(!$consentement) $errors[]='Consentement obligatoire.';
if(!isset($_FILES['cv']) || $_FILES['cv']['error']!==UPLOAD_ERR_OK) $errors[]='CV obligatoire.';

if(isset($_FILES['cv']) && $_FILES['cv']['error']===UPLOAD_ERR_OK){
    $cv=$_FILES['cv'];
    if($cv['size'] > $maxFileSize) $errors[]='Le CV dépasse 5 Mo.';
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($cv['tmp_name']);
    $extension = strtolower(pathinfo($cv['name'], PATHINFO_EXTENSION));
    if(!in_array($mime,$allowedMime,true) || !in_array($extension,$allowedExtensions,true)) {
        $errors[]='Format non autorisé. PDF, DOC ou DOCX uniquement.';
    }
}

if($errors){
    http_response_code(400);
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css">';
    echo '<div class="container" style="padding:70px 0"><div class="notice"><h2>Votre candidature n’a pas pu être envoyée.</h2><ul>';
    foreach($errors as $e) echo '<li>'.htmlspecialchars($e,ENT_QUOTES,'UTF-8').'</li>';
    echo '</ul><a class="btn btn-primary" href="carrieres.html#recrutement">Retour au formulaire</a></div></div>';
    exit;
}

$cv=$_FILES['cv'];
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($cv['tmp_name']);
$filename=preg_replace('/[^A-Za-z0-9._-]/','_',basename($cv['name']));

$text="Nouvelle candidature reçue depuis le site BDJ Consulting\n\n";
$text.="Nom : $nom\nPrénom : $prenom\nEmail : $email\nTéléphone : $telephone\n";
$text.="Poste : $poste\nVille : $ville\nExpérience : $experience\n\n";
$text.="Message :\n$message\n\n";
$text.="IP : ".($_SERVER['REMOTE_ADDR']??'inconnue')."\n";
$text.="Date : ".date('Y-m-d H:i:s')."\n";

$subject='Nouvelle candidature BDJ Consulting — '.$poste;
$internalBranding = bdj_email_branding();
$internalHtml = '<!doctype html><html lang="fr"><head><meta charset="utf-8"></head><body style="margin:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;"><div style="max-width:680px;margin:30px auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;"><div style="background:#111827;padding:24px 30px;color:#fff;font-size:22px;font-weight:700;">Nouvelle candidature</div><div style="padding:30px;line-height:1.7;"><p><strong>Nom :</strong> '.htmlspecialchars($nom, ENT_QUOTES, 'UTF-8').'</p><p><strong>Prénom :</strong> '.htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8').'</p><p><strong>Email :</strong> '.htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8').'</p><p><strong>Téléphone :</strong> '.htmlspecialchars($telephone, ENT_QUOTES, 'UTF-8').'</p><p><strong>Poste :</strong> '.htmlspecialchars($poste, ENT_QUOTES, 'UTF-8').'</p><p><strong>Ville :</strong> '.htmlspecialchars($ville, ENT_QUOTES, 'UTF-8').'</p><p><strong>Expérience :</strong> '.htmlspecialchars($experience, ENT_QUOTES, 'UTF-8').'</p><div style="margin:20px 0;padding:16px;background:#f8fafc;border-left:4px solid #7f1d1d;white-space:pre-wrap;"><strong>Message</strong><br>'.nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')).'</div><p style="font-size:12px;color:#6b7280;">IP : '.htmlspecialchars((string)($_SERVER['REMOTE_ADDR'] ?? 'inconnue'), ENT_QUOTES, 'UTF-8').' · '.date('Y-m-d H:i:s').'</p>' . bdj_email_footer_html('BDJ Consulting', 'recrutement@bdj-consulting.net', '+225 01 02 44 07 07', $internalBranding['logo_cid'], $internalBranding['slogan']) . '</div></div></body></html>';
$internalImages = [];
if ($internalBranding['logo_path'] !== '' && is_file($internalBranding['logo_path'])) {
    $internalImages[] = ['path' => $internalBranding['logo_path'], 'cid' => $internalBranding['logo_cid'], 'name' => 'logo-bdj-consulting.png', 'mime' => 'image/png'];
}
$result=bdj_send_mail($mailConfig,$recruitmentRecipients,$subject,$text,$email,$prenom.' '.$nom,[
    ['path'=>$cv['tmp_name'], 'name'=>$filename, 'mime'=>$mime],
], $internalHtml, $internalImages);

if(!$result['ok']){
    error_log('BDJ send_recruitment: '.$result['error']);
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css">';
    echo '<div class="container" style="padding:70px 0"><div class="notice"><h2>Envoi momentanément indisponible.</h2><p>Votre candidature n’a pas pu être transmise. Vérifiez la configuration e-mail du serveur.</p><a class="btn btn-primary" href="carrieres.html#recrutement">Retour</a></div></div>';
    exit;
}

// Accusé de réception automatique dynamique envoyé au candidat.
$templates = bdj_email_templates();
$template = $templates['recruitment_auto_reply'] ?? [];
$branding = bdj_email_branding();
$fullName = trim($prenom . ' ' . $nom);
$vars = [
    'nom' => $nom,
    'prenom' => $prenom,
    'full_name' => $fullName,
    'email' => (string)$email,
    'telephone' => $telephone,
    'entreprise' => '',
    'service' => 'Recrutement',
    'objet' => 'Candidature',
    'poste' => $poste,
    'site_name' => (string)($mailConfig['from_name'] ?? 'BDJ Consulting'),
    'contact_email' => 'recrutement@bdj-consulting.net',
    'phone' => '+225 01 02 44 07 07',
];

$autoSubject = bdj_render_email_template((string)($template['subject'] ?? 'Candidature bien reçue — BDJ Consulting'), $vars);
$autoText = bdj_render_email_template((string)($template['text'] ?? ''), $vars);
$autoHtmlBody = bdj_render_email_template((string)($template['html'] ?? ''), $vars, true);
$autoHtml = '<!doctype html><html lang="fr"><head><meta charset="utf-8"></head><body style="margin:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;"><div style="max-width:640px;margin:30px auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;"><div style="background:#111827;padding:24px 30px;color:#fff;"><div style="font-size:24px;font-weight:700;">BDJ Consulting</div><div style="margin-top:5px;font-size:13px;opacity:.85;">Glad to be one of them.</div></div><div style="padding:30px;">' . $autoHtmlBody . bdj_email_footer_html('BDJ Consulting', 'recrutement@bdj-consulting.net', '+225 01 02 44 07 07', $branding['logo_cid'], $branding['slogan']) . '</div></div></body></html>';

$embeddedImages = [];
if ($branding['logo_path'] !== '' && is_file($branding['logo_path'])) {
    $embeddedImages[] = [
        'path' => $branding['logo_path'],
        'cid' => $branding['logo_cid'],
        'name' => 'logo-bdj-consulting.png',
        'mime' => 'image/png',
    ];
}

$autoResult = bdj_send_mail($mailConfig, [$email], $autoSubject, $autoText, 'recrutement@bdj-consulting.net', 'BDJ Consulting — Recrutement', [], $autoHtml, $embeddedImages);
if (!$autoResult['ok']) {
    error_log('BDJ recruitment auto-reply: ' . $autoResult['error']);
}

echo '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="assets/css/style.css">';
echo '<div class="container" style="padding:70px 0"><div class="recruitment-banner">';
echo '<h2>Merci pour votre candidature !</h2>';
echo '<p>Votre CV et vos informations ont bien été transmis à BDJ Consulting.</p><br>';
echo '<a class="btn btn-light" href="index.html">Retour à l’accueil</a>';
echo '</div></div>';
?>
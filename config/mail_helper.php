<?php
declare(strict_types=1);

/*
 * BDJ Consulting — Envoi d'e-mails
 *
 * Envoie via PHPMailer + le transport mail local de l'hébergement IONOS.
 * Aucun identifiant ou mot de passe SMTP n'est stocké dans le site.
 *
 * Installation de PHPMailer (au choix) :
 *   - Composer :  composer require phpmailer/phpmailer   (utilise vendor/autoload.php)
 *   - Manuel    :  déposer le dossier "phpmailer/src" dans assets/lib/phpmailer/
 */

$bdj_phpmailer_loaded = false;
foreach ([
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../assets/lib/phpmailer/src/PHPMailer.php',
] as $bdj_file) {
    if (!is_file($bdj_file)) continue;
    if (basename($bdj_file) === 'autoload.php') {
        require_once $bdj_file;
    } else {
        $bdj_src = dirname($bdj_file);
        require_once $bdj_src . '/Exception.php';
        require_once $bdj_src . '/PHPMailer.php';
        if (is_file($bdj_src . '/SMTP.php')) require_once $bdj_src . '/SMTP.php';
    }
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $bdj_phpmailer_loaded = true;
        break;
    }
}
unset($bdj_file);

/**
 * Résultat : ['ok' => bool, 'error' => string].
 *
 * @param array $config      Contenu de mail_config.php
 * @param string[] $to       Destinataires
 * @param string $subject    Sujet (déjà assaini)
 * @param string $text       Corps texte brut
 * @param string $replyEmail Email du visiteur (Reply-To)
 * @param string $replyName  Nom du visiteur
 * @param array[] $attachments [['path'=>..., 'name'=>..., 'mime'=>...], ...]
 */
function bdj_send_mail(
    array $config,
    array $to,
    string $subject,
    string $text,
    string $replyEmail = '',
    string $replyName = '',
    array $attachments = [],
    string $html = '',
    array $embeddedImages = []
): array {
    global $bdj_phpmailer_loaded;

    if (!$bdj_phpmailer_loaded) {
        return [
            'ok' => false,
            'error' => 'PHPMailer est indisponible sur le serveur.',
        ];
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // IONOS Web Hosting fournit un transport mail local pour les scripts
        // PHP. L'adresse From appartient au domaine du site.
        $mail->isMail();

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom((string)$config['from'], (string)($config['from_name'] ?? 'BDJ Consulting'));

        if ($replyEmail !== '') {
            $mail->addReplyTo($replyEmail, $replyName !== '' ? $replyName : $replyEmail);
        }

        foreach ($to as $dest) {
            if (filter_var($dest, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($dest);
            }
        }

        if (count($mail->getToAddresses()) === 0) {
            return ['ok' => false, 'error' => 'Aucun destinataire valide configuré.'];
        }

        $mail->Subject = $subject;
        if ($html !== '') {
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $text;

            foreach ($embeddedImages as $image) {
                if (!isset($image['path'], $image['cid']) || !is_file((string)$image['path'])) {
                    continue;
                }
                $mail->addEmbeddedImage(
                    (string)$image['path'],
                    (string)$image['cid'],
                    (string)($image['name'] ?? basename((string)$image['path'])),
                    'base64',
                    (string)($image['mime'] ?? 'image/png')
                );
            }
        } else {
            $mail->isHTML(false);
            $mail->Body = $text;
        }

        foreach ($attachments as $a) {
            if (!isset($a['path']) || !is_file((string)$a['path'])) {
                return ['ok' => false, 'error' => 'Pièce jointe introuvable.'];
            }
            $content = file_get_contents((string)$a['path']);
            if ($content === false) {
                return ['ok' => false, 'error' => 'Impossible de lire la pièce jointe.'];
            }
            $mail->addStringAttachment(
                $content,
                (string)$a['name'],
                'base64',
                (string)($a['mime'] ?? 'application/octet-stream')
            );
        }

        $mail->send();
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        error_log('BDJ Mail error: ' . $e->getMessage());
        return [
            'ok' => false,
            'error' => 'Le serveur e-mail IONOS n’a pas accepté l’envoi.',
        ];
    }
}

/**
 * Charge les modèles d'e-mails dynamiques configurables.
 */
function bdj_email_templates(): array
{
    static $templates = null;
    if ($templates === null) {
        $file = __DIR__ . '/email_templates.php';
        $templates = is_file($file) ? require $file : [];
        if (!is_array($templates)) $templates = [];
    }
    return $templates;
}

/**
 * Remplace les variables {{nom}} dans un modèle texte ou HTML.
 * Les valeurs HTML sont échappées pour éviter l'injection dans les e-mails.
 */
function bdj_render_email_template(string $template, array $vars, bool $html = false): string
{
    foreach ($vars as $key => $value) {
        $value = (string)$value;
        $replacement = $html
            ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : $value;
        $template = str_replace('{{' . $key . '}}', $replacement, $template);
    }
    return $template;
}

/**
 * Construit les paramètres de marque pour l'image logo embarquée.
 */
function bdj_email_branding(): array
{
    $templates = bdj_email_templates();
    $branding = is_array($templates['branding'] ?? null) ? $templates['branding'] : [];
    $logoPath = (string)($branding['logo_path'] ?? '');
    $logoCid = (string)($branding['logo_cid'] ?? 'bdj-logo');

    return [
        'logo_path' => $logoPath,
        'logo_cid' => $logoCid,
        'slogan' => (string)($branding['slogan'] ?? 'Glad to be one of them.'),
    ];
}

/**
 * Ajoute la signature visuelle BDJ Consulting avec le logo en bas de l'e-mail.
 */
function bdj_email_footer_html(string $siteName, string $contactEmail, string $phone, string $cid, string $slogan): string
{
    return '<div style="margin-top:34px;padding-top:22px;border-top:1px solid #e5e7eb;text-align:center;">'
        . '<img src="cid:' . htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" width="250" style="display:block;width:250px;max-width:80%;height:auto;margin:0 auto 12px;border:0;">'
        . '<div style="font-size:12px;color:#6b7280;">' . htmlspecialchars($slogan, ENT_QUOTES, 'UTF-8') . '</div>'
        . '<div style="font-size:12px;color:#6b7280;margin-top:5px;">' . htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') . ' · ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '</div>'
        . '</div>';
}

/**
 * Limitation simple par IP (fichier temporaire) : autorise au plus $max
 * envois par $window secondes. Retourne true si la requête est autorisée.
 * Si le stockage temporaire n'est pas accessible, autorise (true) pour ne
 * jamais bloquer le site.
 */
function bdj_rate_limit(string $key, int $max = 5, int $window = 900): bool
{
    $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'bdj_rl';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    $now = time();
    $hits = [];

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            foreach ($decoded as $t) {
                if (is_int($t) && $t > $now - $window) $hits[] = $t;
            }
        }
    }

    if (count($hits) >= $max) {
        return false;
    }

    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}
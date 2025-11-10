<?php
// Minimal SMTP mail sender using AUTH LOGIN and optional STARTTLS.
// Not a full-featured library; intended for simple transactional emails to Mailtrap for testing.

function smtp_send_mail($toEmail, $toName, $subject, $htmlBody) {
    // Use globals from config
    $host = defined('MAIL_HOST') ? MAIL_HOST : null;
    $port = defined('MAIL_PORT') ? MAIL_PORT : 2525;
    $user = defined('MAIL_USERNAME') ? MAIL_USERNAME : null;
    $pass = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : null;
    $from = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@example.com';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Website';
    $useTls = defined('MAIL_USE_TLS') ? MAIL_USE_TLS : false;

    if (!$host || !$user || !$pass) {
        error_log('smtp_send_mail: SMTP not configured (MAIL_HOST/MAIL_USERNAME/MAIL_PASSWORD)');
        return false;
    }

    $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
    if (!$socket) {
        error_log("smtp_send_mail: connection failed: {$errno} {$errstr}");
        return false;
    }

    $res = fgets($socket, 515);

    $send = function($cmd) use ($socket) {
        fwrite($socket, $cmd . "\r\n");
        return fgets($socket, 515);
    };

    // EHLO
    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $resp = $send("EHLO {$hostname}");

    // STARTTLS if requested
    if ($useTls) {
        $resp = $send('STARTTLS');
        // enable encryption
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('smtp_send_mail: STARTTLS negotiation failed');
            fclose($socket);
            return false;
        }
        // EHLO again
        $resp = $send("EHLO {$hostname}");
    }

    // AUTH LOGIN
    $resp = $send('AUTH LOGIN');
    $resp = $send(base64_encode($user));
    $resp = $send(base64_encode($pass));

    // MAIL FROM
    $resp = $send('MAIL FROM: <' . $from . '>');
    // RCPT TO
    $resp = $send('RCPT TO: <' . $toEmail . '>');
    // DATA
    $resp = $send('DATA');

    // build headers
    $boundary = md5(uniqid(time()));
    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'To: ' . $toName . ' <' . $toEmail . '>';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $message = implode("\r\n", $headers) . "\r\n\r\n";
    // plain text fallback
    $plain = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $htmlBody));

    $message .= '--' . $boundary . "\r\n";
    $message .= 'Content-Type: text/plain; charset="utf-8"' . "\r\n\r\n";
    $message .= $plain . "\r\n\r\n";

    $message .= '--' . $boundary . "\r\n";
    $message .= 'Content-Type: text/html; charset="utf-8"' . "\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";

    $message .= '--' . $boundary . '--' . "\r\n.";

    // send message
    fwrite($socket, $message . "\r\n");
    $resp = fgets($socket, 515);

    // QUIT
    $resp = $send('QUIT');
    fclose($socket);

    return true;
}
?>

<?php
// Minimal SMTP mail sender using AUTH LOGIN and optional STARTTLS.
// Not a full-featured library; intended for simple transactional emails to Mailtrap for testing.

function smtp_send_mail($toEmail, $toName, $subject, $htmlBody) {
    // Better SMTP sender with clearer error reporting and support for
    // SSL (port 465) and STARTTLS (port 587). Works with Mailtrap SMTP inbox.
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

    $debug = [];

    // Choose socket transport: ssl:// for implicit TLS (465), otherwise tcp://
    $transport = ($port == 465) ? 'ssl' : 'tcp';
    $target = "{$transport}://{$host}:{$port}";

    $socket = @stream_socket_client($target, $errno, $errstr, 15);
    if (!$socket) {
        error_log("smtp_send_mail: connection to {$target} failed: {$errno} {$errstr}");
        return false;
    }

    stream_set_timeout($socket, 15);

    $read = function() use ($socket, &$debug) {
        $line = fgets($socket, 515);
        $debug[] = "S: " . trim($line);
        return $line;
    };
    $write = function($cmd) use ($socket, &$debug) {
        fwrite($socket, $cmd . "\r\n");
        $debug[] = "C: {$cmd}";
    };

    // expect 220
    $greeting = $read();
    if (!$greeting || substr($greeting,0,3) !== '220') {
        error_log('smtp_send_mail: Invalid greeting: ' . trim($greeting));
        fclose($socket);
        return false;
    }

    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $write("EHLO {$hostname}");
    // read EHLO multi-line
    while ($line = $read()) {
        if (isset($line[3]) && $line[3] == ' ') break; // last line
    }

    // STARTTLS if requested and not already using ssl://
    if ($useTls && $transport !== 'ssl') {
        $write('STARTTLS');
        $resp = $read();
        if (substr($resp,0,3) !== '220') {
            error_log('smtp_send_mail: STARTTLS not accepted: ' . trim($resp));
            fclose($socket);
            return false;
        }
        // enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('smtp_send_mail: STARTTLS negotiation failed');
            fclose($socket);
            return false;
        }
        // EHLO again
        $write("EHLO {$hostname}");
        while ($line = $read()) {
            if (isset($line[3]) && $line[3] == ' ') break;
        }
    }

    // AUTH LOGIN
    $write('AUTH LOGIN');
    $resp = $read();
    if (substr($resp,0,3) !== '334') {
        error_log('smtp_send_mail: AUTH LOGIN not accepted: ' . trim($resp));
        fclose($socket);
        return false;
    }
    $write(base64_encode($user));
    $resp = $read();
    if (substr($resp,0,3) !== '334') {
        error_log('smtp_send_mail: username not accepted: ' . trim($resp));
        fclose($socket);
        return false;
    }
    $write(base64_encode($pass));
    $resp = $read();
    if (substr($resp,0,3) !== '235') {
        error_log('smtp_send_mail: authentication failed: ' . trim($resp));
        fclose($socket);
        return false;
    }

    // MAIL FROM / RCPT TO / DATA
    $write('MAIL FROM: <' . $from . '>');
    $resp = $read();
    if (substr($resp,0,3) !== '250') {
        error_log('smtp_send_mail: MAIL FROM rejected: ' . trim($resp));
        fclose($socket);
        return false;
    }

    $write('RCPT TO: <' . $toEmail . '>');
    $resp = $read();
    if (substr($resp,0,3) !== '250' && substr($resp,0,3) !== '251') {
        error_log('smtp_send_mail: RCPT TO rejected: ' . trim($resp));
        fclose($socket);
        return false;
    }

    $write('DATA');
    $resp = $read();
    if (substr($resp,0,3) !== '354') {
        error_log('smtp_send_mail: DATA command not accepted: ' . trim($resp));
        fclose($socket);
        return false;
    }

    // build headers and body
    $boundary = md5(uniqid(time()));
    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'To: ' . $toName . ' <' . $toEmail . '>';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $plain = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $htmlBody));

    $message = implode("\r\n", $headers) . "\r\n\r\n";
    $message .= '--' . $boundary . "\r\n";
    $message .= 'Content-Type: text/plain; charset="utf-8"' . "\r\n\r\n";
    $message .= $plain . "\r\n\r\n";
    $message .= '--' . $boundary . "\r\n";
    $message .= 'Content-Type: text/html; charset="utf-8"' . "\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";
    $message .= '--' . $boundary . '--' . "\r\n.";

    // send data lines and end with CRLF.CRLF
    $write($message);
    $resp = $read();
    if (substr($resp,0,3) !== '250') {
        error_log('smtp_send_mail: message not accepted: ' . trim($resp));
        // include debug trace in log for help
        foreach ($debug as $d) error_log($d);
        fclose($socket);
        return false;
    }

    $write('QUIT');
    $read();
    fclose($socket);
    return true;
}
?>

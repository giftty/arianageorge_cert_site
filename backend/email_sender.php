<?php
/**
 * SMTP Email Sender using PHP's built-in stream functions
 * Supports SMTP on port 465 with TLS/SSL
 */

class SMTPEmailSender {
    private $host = 'mail.arianageorgegroups.com';
    private $port = 465;
    private $username = 'ariana_admin@arianageorgegroups.com';
    private $password = '###arianaG';
    private $encryption = 'ssl';
    
    public function __construct() {
        // Use environment or hardcoded credentials
        $this->host = getenv('MAIL_HOST') ?: $this->host;
        $this->port = (int)(getenv('MAIL_PORT') ?: $this->port);
        $this->username = getenv('MAIL_USERNAME') ?: $this->username;
        $this->password = getenv('MAIL_PASSWORD') ?: $this->password;
        $this->encryption = getenv('MAIL_ENCRYPTION') ?: $this->encryption;
    }

    public function send($to, $subject, $body, $from_name = 'Ariana Groups') {
        try {
            // Build SMTP stream context for SSL/TLS
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            // Connect to SMTP server
            $transport = "ssl://{$this->host}:{$this->port}";
            $fp = stream_socket_client($transport, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
            
            if (!$fp) {
                throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
            }

            // Wait for the server greeting
            $response = fgets($fp, 1024);
            if (strpos($response, '220') === false) {
                throw new Exception("SMTP server greeting failed: $response");
            }

            // Send EHLO command
            fwrite($fp, "EHLO example.com\r\n");
            $response = fgets($fp, 1024);

            // Authenticate
            fwrite($fp, "AUTH LOGIN\r\n");
            $response = fgets($fp, 1024);
            if (strpos($response, '334') === false) {
                throw new Exception("AUTH LOGIN failed");
            }

            fwrite($fp, base64_encode($this->username) . "\r\n");
            $response = fgets($fp, 1024);
            if (strpos($response, '334') === false) {
                throw new Exception("Username auth failed");
            }

            fwrite($fp, base64_encode($this->password) . "\r\n");
            $response = fgets($fp, 1024);
            if (strpos($response, '235') === false) {
                throw new Exception("Password auth failed: $response");
            }

            // Set sender
            fwrite($fp, "MAIL FROM:<{$this->username}>\r\n");
            $response = fgets($fp, 1024);
            if (strpos($response, '250') === false) {
                throw new Exception("MAIL FROM failed: $response");
            }

            // Set recipient
            fwrite($fp, "RCPT TO:<$to>\r\n");
            $response = fgets($fp, 1024);
            if (strpos($response, '250') === false) {
                throw new Exception("RCPT TO failed: $response");
            }

            // Prepare message
            fwrite($fp, "DATA\r\n");
            $response = fgets($fp, 1024);
            if (strpos($response, '354') === false) {
                throw new Exception("DATA command failed: $response");
            }

            $email_content = "From: {$from_name} <{$this->username}>\r\n";
            $email_content .= "To: $to\r\n";
            $email_content .= "Subject: $subject\r\n";
            $email_content .= "MIME-Version: 1.0\r\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $email_content .= $body . "\r\n";
            $email_content .= ".\r\n";

            fwrite($fp, $email_content);
            $response = fgets($fp, 1024);
            if (strpos($response, '250') === false) {
                throw new Exception("Message send failed: $response");
            }

            // Quit
            fwrite($fp, "QUIT\r\n");
            fclose($fp);

            return ['success' => true];
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Fallback: use mail() if SMTP fails
function send_email_fallback($to, $subject, $body, $from_name = 'Ariana Groups') {
    $headers = "From: " . $from_name . " <ariana_admin@arianageorgegroups.com>\r\n";
    $headers .= "Reply-To: ariana_admin@arianageorgegroups.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

// Public function to send email
function send_email($to, $subject, $body, $from_name = 'Ariana Groups') {
    $sender = new SMTPEmailSender();
    $result = $sender->send($to, $subject, $body, $from_name);
    
    if (!$result['success']) {
        // Fallback to mail()
        error_log("Falling back to mail() function");
        return send_email_fallback($to, $subject, $body, $from_name);
    }
    return true;
}
?>

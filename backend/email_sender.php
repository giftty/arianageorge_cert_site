<?php

    require 'PHPMailer-master/src/Exception.php';
    require 'PHPMailer-master/src/PHPMailer.php';
    require 'PHPMailer-master/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
class SMTPEmailSender {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    
    public function __construct() {
        // Load configuration from environment variables or config file
        $this->loadConfig();
        
        // Validate required settings
        if (empty($this->username) || empty($this->password)) {
            throw new Exception('SMTP credentials not configured. Please check environment variables or config.php');
        }
    }
    
    private function loadConfig() {
        // Try environment variables first (highest priority)
        $this->host = getenv('MAIL_HOST');
        $this->port = getenv('MAIL_PORT');
        $this->username = getenv('MAIL_USERNAME');
        $this->password = getenv('MAIL_PASSWORD');
        $this->encryption = getenv('MAIL_ENCRYPTION');
        
        // Fall back to config file if environment variables not set
        if (empty($this->host) || empty($this->username)) {
            $configPath = __DIR__ . '/config.php';
            if (file_exists($configPath)) {
                $config = require $configPath;
                $mailConfig = $config['mail'] ?? [];
                
                $this->host = $this->host ?: ($mailConfig['host'] ?? '');
                $this->port = $this->port ?: ($mailConfig['port'] ?? 465);
                $this->username = $this->username ?: ($mailConfig['username'] ?? '');
                $this->password = $this->password ?: ($mailConfig['password'] ?? '');
                $this->encryption = $this->encryption ?: ($mailConfig['encryption'] ?? 'ssl');
            }
        }
        
        // Set defaults if still not set
        $this->port = $this->port ?: 465;
        $this->encryption = $this->encryption ?: 'ssl';
    }

    public function send($to, $subject, $body, $from_name = 'Ariana Groups') {

                $mail = new PHPMailer(true);
                try {
                    // SMTP Settings
                    $mail->isSMTP();
                    $mail->Host       = $this->host;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $this->username;
                    $mail->Password   = $this->password; // put real password
                    $mail->SMTPSecure = $this->encryption; // Port 465 uses SSL
                    $mail->Port       = $this->port;

                    // Sender
                    $mail->setFrom($this->username, 'Ariana Admin');

                    // Recipient
                    $mail->addAddress($to, '');

                    // Email Content
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->AltBody = '';

                    $mail->send();
                   return  true;

                } catch (Exception $e) {
                    echo "Mailer Error: " . $mail->ErrorInfo;
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
    if (!$result) {
        // Fallback to mail()
        error_log("An error occured while trying to send mail.");
        return false;
        // return send_email_fallback($to, $subject, $body, $from_name);
    }
    return true;
}


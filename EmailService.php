<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // If using Composer
// OR
// require 'PHPMailer/Exception.php';
// require 'PHPMailer/PHPMailer.php';
// require 'PHPMailer/SMTP.php';

class EmailService
{
    private $from_email = "palmerong7@gmail.com"; // Change this to your Gmail
    private $from_name = "Beauty Salon System";
    private $smtp_username = "palmerong7@gmail.com"; // Same as from_email
    private $smtp_password = "kljb jomy shwq qveu"; // Your 16-character App Password
    
    // Send verification email
    public function sendVerificationEmail($to_email, $to_name, $verification_token)
    {
        // Automatically detect the correct domain
		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
		$domain = $_SERVER['HTTP_HOST'];
		$verification_link = $protocol . $domain . "/verifyEmail.php?token=" . $verification_token;
        
        $subject = "Verify Your Email - Beauty Salon";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Beauty Salon!</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($to_name) . "!</h2>
                    <p>Thank you for registering with us. Please verify your email address to activate your account.</p>
                    <p>Click the button below to verify your email:</p>
                    <a href='" . $verification_link . "' class='button'>Verify Email Address</a>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #667eea;'>" . $verification_link . "</p>
                    <p><strong>Note:</strong> This link will expire in 24 hours.</p>
                    <p>If you didn't create an account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Beauty Salon. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }
    
    // Send order confirmation email
    public function sendOrderConfirmation($to_email, $to_name, $order_id, $order_details)
    {
        $subject = "Order Confirmation #" . $order_id . " - Beauty Salon";
        
        $items_html = "";
        foreach ($order_details['items'] as $item) {
            $items_html .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($item['service_name']) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>₱" . number_format($item['price'], 2) . "</td>
            </tr>";
        }
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .total { background: #667eea; color: white; font-weight: bold; padding: 15px; text-align: right; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Confirmation</h1>
                    <p>Order #" . $order_id . "</p>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($to_name) . "!</h2>
                    <p>Thank you for your order! We've received your booking request.</p>
                    
                    <h3>Order Details:</h3>
                    <p><strong>Date:</strong> " . date('F d, Y', strtotime($order_details['order_date'])) . "</p>
                    <p><strong>Time:</strong> " . date('h:i A', strtotime($order_details['order_time'])) . "</p>
                    
                    <h3>Services:</h3>
                    <table>
                        <thead>
                            <tr style='background: #f0f0f0;'>
                                <th style='padding: 10px; text-align: left;'>Service</th>
                                <th style='padding: 10px; text-align: left;'>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            " . $items_html . "
                        </tbody>
                    </table>
                    <div class='total'>Total: ₱" . number_format($order_details['total_amount'], 2) . "</div>
                    
                    <p><strong>Status:</strong> Pending Admin Approval</p>
                    <p>We'll notify you once your order has been approved and staff have been assigned.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }
    
    // Send order status update email
    public function sendOrderStatusUpdate($to_email, $to_name, $order_id, $status)
    {
        $status_messages = [
            'approved' => 'Your order has been approved! Our staff will be in touch soon.',
            'in_progress' => 'Your services are now in progress.',
            'completed' => 'Your order has been completed. Thank you for choosing us!',
            'cancelled' => 'Your order has been cancelled.'
        ];
        
        $subject = "Order #" . $order_id . " - Status Update";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Status Update</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($to_name) . "!</h2>
                    <p>Your order #" . $order_id . " has been updated.</p>
                    <p><strong>New Status:</strong> " . ucfirst(str_replace('_', ' ', $status)) . "</p>
                    <p>" . $status_messages[$status] . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($to_email, $to_name, $subject, $message);
    }
    
    // Base email sending function using PHPMailer
    private function sendEmail($to, $to_name, $subject, $html_message)
    {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to, $to_name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            $mail->AltBody = strip_tags($html_message);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
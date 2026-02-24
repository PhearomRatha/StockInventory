<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .email-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .email-body h2 {
            color: #333;
            font-size: 20px;
            margin-top: 0;
        }
        .email-body p {
            color: #666;
            margin-bottom: 20px;
        }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .verify-button:hover {
            opacity: 0.9;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verification-link {
            display: block;
            word-break: break-all;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            margin: 20px 0;
            border: 1px dashed #ddd;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        .email-footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-card">
            <div class="email-header">
                <h1>📧 Verify Your Email Address</h1>
            </div>
            <div class="email-body">
                <h2>Hello {{ $name }},</h2>
                
                <p>Thank you for registering with <strong>Stock Inventory System</strong>! We're excited to have you on board.</p>
                
                <p>To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                
                <div class="button-container">
                    <a href="{{ $verificationUrl }}" class="verify-button">Verify Email Address</a>
                </div>
                
                <p><strong>Important:</strong> This verification link will expire in {{ $expiresInMinutes }} minutes for security purposes.</p>
                
                <p>If the button above doesn't work, you can copy and paste the following link into your browser:</p>
                
                <div class="verification-link">
                    {{ $verificationUrl }}
                </div>
                
                <p>If you didn't create an account with us, please ignore this email or contact our support team.</p>
                
                <p>Best regards,<br>The Stock Inventory Team</p>
            </div>
            <div class="email-footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; {{ date('Y') }} Stock Inventory System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

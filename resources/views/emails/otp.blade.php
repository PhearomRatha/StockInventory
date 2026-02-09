<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        /* Reset and base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }

        .container {
            max-width: 500px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .header {
            background: linear-gradient(135deg, #4a90d9, #6fb1fc);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .content {
            padding: 30px 25px;
            line-height: 1.6;
        }

        .content p {
            margin: 15px 0;
        }

        .otp-code {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #eef6ff;
            border: 1px solid #4a90d9;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(74,144,217,0.2);
        }

        .otp-code span {
            font-size: 36px;
            font-weight: 700;
            color: #4a90d9;
            letter-spacing: 10px;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #888;
        }

        .warning {
            color: #e74c3c;
            font-size: 14px;
            font-weight: 500;
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            background-color: #4a90d9;
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: background 0.3s ease;
        }

        .button:hover {
            background-color: #357ab8;
        }

        @media(max-width: 600px){
            .otp-code span {
                font-size: 28px;
                letter-spacing: 6px;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Stock Inventory System ❤️</h1>
        </div>
        <div class="content">
            <p>Hello <strong>{{ $name }}</strong>,</p>
            <p>Your One-Time Password (OTP) for account verification is:</p>
            <div class="otp-code">
                <span>{{ $otp }}</span>
            </div>
            <p>This OTP will expire in <strong>10 minutes</strong>.</p>
            <p class="warning">⚠️ Please do not share this OTP with anyone. Our support team will never ask for your OTP.</p>
            
        </div>
        <div class="footer">
            <p>If you didn't request this OTP, please ignore this email.</p>
            <p>&copy; {{ date('Y') }} Stock Inventory System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

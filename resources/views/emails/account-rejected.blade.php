<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Rejected</title>
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
        }

        .header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .content {
            padding: 30px 25px;
            line-height: 1.6;
        }

        .content p {
            margin: 15px 0;
        }

        .reason-box {
            background-color: #fef5f5;
            border: 1px solid #e74c3c;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .reason-box p {
            margin: 0;
            color: #c0392b;
            font-weight: 500;
        }

        .reason-box .reason-text {
            margin-top: 10px;
            font-weight: 400;
            color: #333;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #888;
        }

        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media(max-width: 600px) {
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
            <p>We regret to inform you that your registration request for the Stock Inventory System has been <strong>rejected</strong>.</p>
            
            <div class="reason-box">
                <p>Reason for rejection:</p>
                <p class="reason-text">{{ $reason }}</p>
            </div>

            <div class="contact-info">
                <p>If you believe this is a mistake or would like to appeal this decision, please contact the system administrator.</p>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Stock Inventory System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 18px;
            line-height: 1.7;
        }
        .content p:last-child {
            margin-bottom: 0;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        strong {
            color: #007bff;
        }
        .course-list {
            background-color: #ffffff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>RAJSHAHI UNIVERSITY OF ENGINEERING & TECHNOLOGY</h2>
        <h3>Department of Computer Science & Engineering</h3>
    </div>

    <div class="content">
        {!! $content !!}
    </div>

    <div class="footer">
        <p>This is an automated email from the Backlog Exam Scheduler System.</p>
        <p>Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} RUET CSE Department. All rights reserved.</p>
    </div>
</body>
</html>

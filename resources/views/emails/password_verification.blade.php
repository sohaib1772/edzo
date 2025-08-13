<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>استعادة كلمة المرور</title>
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eeeeee;
        }

        .header h1 {
            color: #3498db;
            margin: 0;
        }

        .content {
            padding-top: 20px;
            text-align: right;
            color: #333333;
            line-height: 1.6;
        }

        .code-box {
            margin: 30px auto;
            padding: 15px 25px;
            background-color: #f0f0f0;
            border-radius: 6px;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            text-align: center;
            width: fit-content;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #888888;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="email-container">
    <div class="header">
        <h1>Edzo</h1>
    </div>
    <div class="content">
        <p>مرحباً بك!</p>
        <p>رمز التحقق لأستعادة كلمة المرور</p>

        <div class="code-box">
            {{ $code }}
        </div>

        <p>الرمز صالح لمدة 30 دقيقة فقط.</p>

        <p>إذا لم تطلب هذا التأكيد، يرجى تجاهل هذه الرسالة.</p>
    </div>

    <div class="footer">
        جميع الحقوق محفوظة &copy; {{ date('Y') }} - Edzo
    </div>
</div>

</body>
</html>
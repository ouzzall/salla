<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        /* Add your custom styles here */
        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #333333;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
            background-color: #ffffff;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 100%;
            height: auto;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .content {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .cta {
            text-align: left;
            margin-bottom: 20px;
        }

        .cta a {
            display: inline-block;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            text-decoration: none;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .footer {
            font-size: 14px;
            line-height: 1.5;
            text-align: left;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="title">Hello {{ $name }}</div>
        <div class="title">Thank you for your purchase from Serial Store</div>
        <div class="content">You will find in this e-mail your code for your order( {{ $order }})</div>
        <div class="cta">
            <p>Code : {{ $data }}</p>
        </div><br>
        <div class="footer">
            <p> Thank you.</P>
            <p>Serial Store</p>
            <span> <a href="www.serialst.net"> www.serialst.net</a></span>
            <p> This email was sent automatically, please do not reply to it.</p>
        </div>
    </div>
</body>

</html>

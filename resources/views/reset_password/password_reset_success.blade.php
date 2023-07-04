<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f2f2f2;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        p {
            margin: 10px 0;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            h1 {
                font-size: 20px;
            }

            .button {
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Reset Success</h1>
        <p>Your password has been reset successfully.</p>
        <a href="{{ route('home') }}" class="button">OK</a>
        
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: #007bff;
            color: #fff;
            padding: 15px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
            text-align: center;
            color: #333;
        }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #007bff;
            color: #fff !important;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            {{ $header }}
        </div>

        <div class="content">
            <p>{!! $emailMessage !!}</p>

            @if($btnUrl)
            <a href="{{ $btnUrl }}" class="btn">{{ $btnValue }}</a>
             @endif
        </div>

        <div class="footer">
            {{ $footer }}
        </div>
    </div>

</body>
</html>

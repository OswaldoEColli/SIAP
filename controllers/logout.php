<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando sesión</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .logout-animation {
            text-align: center;
        }
        .logout-animation h1 {
            font-size: 24px;
            color: #333;
        }
        .spinner {
            margin: 20px auto;
            width: 50px;
            height: 50px;
            border: 5px solid #ccc;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-animation">
        <h1>Cerrando sesión...</h1>
        <div class="spinner"></div>
    </div>
    <script>
        // Redirigir a login.php después de 3 segundos
        setTimeout(() => {
            window.location.href = '../views/login.php';
        }, 3000);
    </script>
</body>
</html>
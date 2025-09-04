<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, ip_address) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $password, $ip_address])) {
        header('Location: login.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10">
        <h2 class="text-2xl">Register</h2>
        <form method="POST" class="bg-white p-6 rounded shadow-md">
            <input type="text" name="username" placeholder="Username" required class="border p-2 mb-4 w-full">
            <input type="password" name="password" placeholder="Password" required class="border p-2 mb-4 w-full">
            <button type="submit" class="bg-blue-500 text-white p-2">Register</button>
        </form>
    </div>
</body>
</html>
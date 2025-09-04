<?php
session_start();
require 'db.php';

API_URL = "https://api.dnsexit.com/dns/";
API_KEY = "E628lcE84zv57PTU6m64u2Iuf7KpT4";

// Mock database (replace with actual database logic)
$users_db = [];
$subdomains_db = [];
$nginx_records_db = [];

function create_nginx_config($domain, $subdomain, $ip_address) {
    $config = "
    server {
        listen 80;
        server_name $subdomain.$domain;

        location / {
            proxy_pass http://$ip_address;
            proxy_set_header Host \$host;
            proxy_set_header X-Real-IP \$remote_addr;
            proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        }
    }
    ";
    $config_path = "/etc/nginx/sites-available/$subdomain.$domain.conf";
    file_put_contents($config_path, $config);
    symlink($config_path, "/etc/nginx/sites-enabled/$subdomain.$domain.conf");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Registration logic
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        if (!isset($users_db[$username])) {
            $users_db[$username] = $password;
            $_SESSION['username'] = $username;
            $subdomains_db[$username] = [];
            $nginx_records_db[$username] = [];
            header('Location: index.php');
        } else {
            echo "User already exists.";
        }
    }

    if (isset($_POST['login'])) {
        // Login logic
        $username = $_POST['username'];
        $password = $_POST['password'];
        if (isset($users_db[$username]) && password_verify($password, $users_db[$username])) {
            $_SESSION['username'] = $username;
            header('Location: index.php');
        } else {
            echo "Invalid credentials.";
        }
    }

    if (isset($_POST['create_subdomain'])) {
        // Subdomain creation logic
        $username = $_SESSION['username'];
        if (count($subdomains_db[$username]) >= 5) {
            echo json_encode(["error" => "Limit of 5 subdomains reached."]);
            exit();
        }

        $subdomain = $_POST['subdomain'];
        $domain = "kyle.work.gd"; // Change this to your main domain

        // Prepare DNS record data
        $data = [
            "domain" => $domain,
            "add" => [
                "type" => "A",
                "name" => $subdomain,
                "content" => "144.24.63.250",  // Replace with actual IP
                "ttl" => 480
            ]
        ];

        // Call DNS update
        $response = json_decode(file_get_contents(API_URL . '?' . http_build_query($data)), true);
        if ($response['code'] === 0) {
            $subdomains_db[$username][] = $subdomain;
            create_nginx_config($domain, $subdomain, "144.24.63.250"); // Replace with actual IP
            echo json_encode($response);
        } else {
            echo json_encode(["error" => "Failed to create subdomain."]);
        }
    }

    if (isset($_POST['update_dns'])) {
        // Update DNS logic
        $username = $_SESSION['username'];
        if (count($nginx_records_db[$username]) >= 5) {
            echo json_encode(["error" => "Limit of 5 Nginx records reached."]);
            exit();
        }

        $data = $_POST['update_dns'];
        $response = json_decode(file_get_contents(API_URL . '?' . http_build_query($data)), true);
        if ($response['code'] === 0) {
            $nginx_records_db[$username][] = $data['add']['name'];
            create_nginx_config($data['domain'], $data['add']['name'], $data['add']['content']); // Assuming content is an IP
            echo json_encode($response);
        } else {
            echo json_encode(["error" => "Failed to update DNS."]);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNS Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">DNS Management</h1>

        <form id="authForm" class="bg-white shadow-md rounded-lg p-6 mb-6" method="POST">
            <h2 class="text-2xl font-semibold mb-4">Register / Login</h2>
            <input type="text" name="username" placeholder="Username" class="border border-gray-300 p-2 w-full mb-4" required>
            <input type="password" name="password" placeholder="Password" class="border border-gray-300 p-2 w-full mb-4" required>
            <button type="submit" name="register" class="bg-blue-500 text-white p-2 rounded">Register</button>
            <button type="submit" name="login" class="bg-green-500 text-white p-2 rounded">Login</button>
        </form>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">Create Subdomain</h2>
            <form id="createSubdomainForm" method="POST">
                <input type="text" name="subdomain" placeholder="Subdomain" class="border border-gray-300 p-2 w-full" required>
                <button type="submit" name="create_subdomain" class="bg-blue-500 text-white p-2 rounded">Create Subdomain</button>
            </form>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">Add A Record</h2>
            <form id="addARecord" method="POST">
                <input type="text" name="domain" placeholder="Domain" class="border border-gray-300 p-2 w-full" required>
                <input type="text" name="name" placeholder="Host/Subdomain" class="border border-gray-300 p-2 w-full" required>
                <input type="text" name="content" placeholder="IP Address" class="border border-gray-300 p-2 w-full" required>
                <button type="submit" name="update_dns" class="bg-blue-500 text-white p-2 rounded">Add A Record</button>
            </form>
        </div>
    </div>
</body>
</html>
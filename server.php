<?php
// ============================================
// Simple PHP Backend (No Framework)
// Local Data Source (data.json)
// Supports GET, POST, PUT, DELETE requests
// ============================================

header('Content-Type: application/json');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight (OPTIONS) requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Path to local data file
$dataFile = 'data.json';

// Load existing data or create default
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([
        ["id" => 1, "name" => "Houssam"],
        ["id" => 2, "name" => "Ali"]
    ], JSON_PRETTY_PRINT));
}
$users = json_decode(file_get_contents($dataFile), true);

$method = $_SERVER['REQUEST_METHOD'];
$request = strtok($_SERVER['REQUEST_URI'], '?');

// --------------------------------------------------
// 🟢 1. GET /users → Read all users
// --------------------------------------------------
if ($method === 'GET' && $request === '/users') {
    echo json_encode($users);
}

// --------------------------------------------------
// 🟡 2. POST /users → Add a new user
// --------------------------------------------------
else if ($method === 'POST' && $request === '/users') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['name'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing 'name' field"]);
        exit;
    }

    $newUser = [
        "id" => end($users)['id'] + 1,
        "name" => $input['name']
    ];

    $users[] = $newUser;
    file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT));

    echo json_encode(["message" => "User created", "user" => $newUser]);
}

// --------------------------------------------------
// 🟠 3. PUT /users/{id} → Update user
// --------------------------------------------------
else if ($method === 'PUT' && preg_match('#^/users/(\d+)$#', $request, $matches)) {
    $id = (int)$matches[1];
    $input = json_decode(file_get_contents('php://input'), true);

    foreach ($users as &$user) {
        if ($user['id'] === $id) {
            $user['name'] = $input['name'] ?? $user['name'];
            file_put_contents($dataFile, json_encode($users, JSON_PRETTY_PRINT));
            echo json_encode(["message" => "User updated", "user" => $user]);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
}

// --------------------------------------------------
// 🔴 4. DELETE /users/{id} → Remove user
// --------------------------------------------------
else if ($method === 'DELETE' && preg_match('#^/users/(\d+)$#', $request, $matches)) {
    $id = (int)$matches[1];
    $newUsers = array_filter($users, fn($u) => $u['id'] !== $id);

    if (count($newUsers) === count($users)) {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        exit;
    }

    file_put_contents($dataFile, json_encode(array_values($newUsers), JSON_PRETTY_PRINT));
    echo json_encode(["message" => "User deleted", "id" => $id]);
}

// --------------------------------------------------
// ⚪ Default: Endpoint not found
// --------------------------------------------------
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found"]);
}
?>

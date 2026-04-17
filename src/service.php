<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define ('DB_HOST', 'localhost');
define ('DB_USER', 'root');
define ('DB_PASS', '');
define ('DB_NAME', 'artistryx_db');

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "code" => 500,
            "message" => "Connection failed."
        ]);
        $conn->close();
        exit();
    }

$inputData = file_get_contents("php://input");
$request = json_decode($inputData, true);

if (!is_array($request)) {
    $request = $_POST;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim($request['action'] ?? '');

// REGISTER

if ($action === 'register') {
    $username = trim($request['username'] ?? '');
    $email = trim($request['email'] ?? '');
    $password = trim($request['password'] ?? '');

    if ($username === '') {
        http_response_code(400);
        echo json_encode([
           "status" => "error",
            "code" => 400,
            "message" => "Missing username. Username is required." 
        ]);
        exit();
    }

    if ($email === '') {
        http_response_code(400);
        echo json_encode([
           "status" => "error",
            "code" => 400,
            "message" => "Missing email. Email is required." 
        ]);
        exit();
    }

    $email = strtolower($email);
    $allowedDomains = ["@gmail.com", "@bicol-u.edu.ph", "@outlook.com"];

    if (!(str_ends_with($email, "@gmail.com") ||
        str_ends_with($email, "@bicol-u.edu.ph") ||
        str_ends_with($email, "@outlook.com"))) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Email must end with @gmail.com, @bicol-u.edu.ph, or @outlook.com"
        ]);
        exit();
    }

    $has_letter = false;
    $has_number = false;
    $has_symbol = false;

    for ($i = 0; $i < strlen($password); $i++) {
        $c = $password[$i];

        if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z')) {
            $has_letter = true;
        }
        else if ($c >= '0' && $c <= '9') {
            $has_number = true;
        }
        else {
            $has_symbol = true;
        }
    }

    if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 8 characters."
    ]);
    exit();
    }

    if ($has_letter && !$has_number && !$has_symbol) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Password cannot be only letters."
        ]);
        exit();
    }

    if ($has_number && !$has_letter && !$has_symbol) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Password cannot be only numbers."
        ]);
        exit();
    }

    if ($has_symbol && !$has_letter && !$has_number) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Password cannot be only special characters."
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "code" => 500,
            "message" => "Failed: " . $conn->error
        ]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        http_response_code(409);
        echo json_encode([
            "status" => "error",
            "code" => 409,
            "message" => "Username is already taken."
        ]);
        exit();
    }

    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "code" => 500,
            "message" => "Failed: " . $conn->error
        ]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        http_response_code(409);
        echo json_encode([
            "status" => "error",
            "code" => 409,
            "message" => "Email is already taken."
        ]);
        exit();
    }

    $stmt->close();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "code" => 500,
            "message" => "Failed: " . $conn->error
        ]);
        $conn->close();
        exit();
  
    }

    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "code" => 201,
            "message" => "Registration successful.",
            "user" => [
                "id" => $conn->insert_id,
                "username" => $username,
                "email" => $email
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "code" => 500,
            "message" => "Registration failed: " . $stmt->error
        ]);
    }
    exit();
}
//LOGIN
if ($action === 'login') {
    $email = trim($request['email'] ?? '');
    $password = trim($request['password'] ?? '');

    if ($email === '') {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "code" => 400,
            "message" => "Missing email. Email is required."
        ]);
        exit();
    }

    if ($password === '') {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "code" => 400,
            "message" => "Missing password. Password is required."
        ]);
        exit();
    }    

    $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ?");    

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "code" => 500,
            "message" => "Failed: " . $conn->error
        ]);
        $conn->close();
        exit();
  
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "code" => 404,
            "message" => "Email not found."
        ]);
        exit();
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "code" => 401,
            "message" => "Invalid password."
        ]);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "code" => 200,
        "message" => "Login successful.",
        "user" => [
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email']
        ]
    ]);

    exit();
}

switch ($method) {

    // ─────────────────────────────────────────
    // POST → CREATE SELLER
    // Required params: userId, shopName
    // ─────────────────────────────────────────
    case 'POST':
        $userId   = $request['userId'] ?? '';
        $shopName = trim($request['shopName'] ?? '');

        // Validate required parameters
        if (empty($userId) || empty($shopName)) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "code"    => 400,
                "message" => "Missing parameters. userId and shopName are required."
            ]);
            exit();
        }

        // Check if User exists
        $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $userCheck->bind_param("i", $userId);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            http_response_code(400);
            echo json_encode(["status" => "failed", "code" => 404, "message" => "User ID does not exist."]);
            exit();
        }

        // Check if the User already has a shop (UNIQUE userId in sellers)
        $sellerCheck = $conn->prepare("SELECT id FROM sellers WHERE userId = ?");
        $sellerCheck->bind_param("i", $userId);
        $sellerCheck->execute();
        if ($sellerCheck->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["status" => "conflict", "code" => 409, "message" => "This user already has a registered shop."]);
            exit();
        }

        // Check if Shop Name is already taken
        $shopCheck = $conn->prepare("SELECT id FROM sellers WHERE shopName = ?");
        $shopCheck->bind_param("s", $shopName);
        $shopCheck->execute();
        if ($shopCheck->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["status" => "conflict", "code" => 409, "message" => "Shop name already exists."]);
            exit();
        }

        // Insert new seller
        $stmt = $conn->prepare("INSERT INTO sellers (userId, shopName, shopStatus) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("is", $userId, $shopName);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "status"  => "success",
                "code"    => 201,
                "message" => "Seller shop '$shopName' created successfully.",
                "details" => [
                    "sellerId"   => $stmt->insert_id,
                    "userId"     => (int)$userId,
                    "shopName"   => $shopName,
                    "shopStatus" => "Pending"
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "code" => 500, "message" => "Failed to create seller."]);
        }
        break;

    // ─────────────────────────────────────────
    // GET → READ SELLER
    // Required param: userId (query string)
    // Example: service.php?userId=1
    // ─────────────────────────────────────────
    case 'GET':
        $userId = $_GET['userId'] ?? '';

        // Validate required parameter
        if (empty($userId)) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "code"    => 400,
                "message" => "Missing parameter. userId is required."
            ]);
            exit();
        }

        // Check if User exists
        $userCheck = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
        $userCheck->bind_param("i", $userId);
        $userCheck->execute();
        $userResult = $userCheck->get_result();
        if ($userResult->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["status" => "failed", "code" => 404, "message" => "User ID does not exist."]);
            $conn->close();
            exit();
        }
        $user = $userResult->fetch_assoc();

        // Fetch seller info linked to this user
        $stmt = $conn->prepare("SELECT id, shopName, shopStatus FROM sellers WHERE userId = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            http_response_code(200);
            echo json_encode([
                "status"  => "no_shop",
                "code"    => 200,
                "message" => "No shop found. You can create one.",
                "hasShop" => false
            ]);

        } else {

            $seller = $result->fetch_assoc();

            http_response_code(200);
            echo json_encode([
                "status"  => "has_shop",
                "code"    => 200,
                "message" => "You already have a registered shop.",
                "hasShop" => true,
                "details" => [
                    "sellerId"   => (int)$seller['id'],
                    "userId"     => (int)$user['id'],
                    "username"   => $user['username'],
                    "shopName"   => $seller['shopName'],
                    "shopStatus" => $seller['shopStatus']
                ]
            ]);
        }
        break;

    // ─────────────────────────────────────────
    // PUT → UPDATE SELLER SHOP NAME
    // Required params: userId, newShopName
    // ─────────────────────────────────────────
    case 'PUT':
        $userId      = $request['userId'] ?? '';
        $newShopName = trim($request['newShopName'] ?? '');
        // Validate required parameters
        if (empty($userId) || empty($newShopName)) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "code"    => 400,
                "message" => "Missing parameters. userId and newShopName are required."
            ]);
            exit();
        }

        // Check if User exists
        $userCheck = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $userCheck->bind_param("i", $userId);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["status" => "failed", "code" => 404, "message" => "User ID does not exist."]);
            $conn->close();
            exit();
        }

        // Check if the seller shop exists for this user
        $sellerCheck = $conn->prepare("SELECT id, shopName FROM sellers WHERE userId = ?");
        $sellerCheck->bind_param("i", $userId);
        $sellerCheck->execute();
        $sellerResult = $sellerCheck->get_result();
        if ($sellerResult->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["status" => "not_found", "code" => 404, "message" => "No seller shop found for this user."]);
            $conn->close();
            exit();
        }
        $existingSeller = $sellerResult->fetch_assoc();

        // Check if new shop name is the same as current
        if (strtolower($existingSeller['shopName']) === strtolower($newShopName)) {
            http_response_code(200);
            echo json_encode([
                "status"  => "no_change",
                "code"    => 200,
                "message" => "New shop name is the same as the current one. No changes made.",
                "details" => [
                    "sellerId"       => (int)$existingSeller['id'],
                    "currentShopName" => $existingSeller['shopName']
                ]
            ]);
            exit();
        }

        // Check if the new shop name is already taken by someone else
        $nameCheck = $conn->prepare("SELECT id FROM sellers WHERE shopName = ? AND userId != ?");
        $nameCheck->bind_param("si", $newShopName, $userId);
        $nameCheck->execute();
        if ($nameCheck->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["status" => "conflict", "code" => 409, "message" => "Shop name '$newShopName' is already taken."]);
            exit();
        }

        // Perform the update
        $stmt = $conn->prepare("UPDATE sellers SET shopName = ? WHERE userId = ?");
        $stmt->bind_param("si", $newShopName, $userId);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                "status"  => "success",
                "code"    => 200,
                "message" => "Shop name updated successfully.",
                "details" => [
                    "sellerId"    => (int)$existingSeller['id'],
                    "userId"      => (int)$userId,
                    "oldShopName" => $existingSeller['shopName'],
                    "newShopName" => $newShopName
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "code" => 500, "message" => "Failed to update shop name."]);
        }
        break;

    // ─────────────────────────────────────────
    // Unsupported HTTP method
    // ─────────────────────────────────────────
    default:
        http_response_code(405);
        echo json_encode([
            "status"  => "error",
            "code"    => 405,
            "message" => "Method not allowed. Use POST (create), GET (read), or PUT (update)."
        ]);
        break;
}

$conn->close();
?>

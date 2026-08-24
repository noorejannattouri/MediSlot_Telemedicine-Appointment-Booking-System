<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// 1. Check if Google returned an error or no code
if (isset($_GET['error'])) {
    $_SESSION['error_msg'] = 'Google login was cancelled or failed.';
    header('Location: login.php');
    exit;
}

if (!isset($_GET['code'])) {
    header('Location: login.php');
    exit;
}

$code = $_GET['code'];

// 2. Exchange authorization code for access token
$token_url = 'https://oauth2.googleapis.com/token';

$token_data = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$token_response = curl_exec($ch);
curl_close($ch);

$token_json = json_decode($token_response, true);

if (!isset($token_json['access_token'])) {
    $_SESSION['error_msg'] = 'Failed to get access token from Google.';
    header('Location: login.php');
    exit;
}

$access_token = $token_json['access_token'];

// 3. Get user info from Google
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);

$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userinfo_response = curl_exec($ch);
curl_close($ch);

$userinfo = json_decode($userinfo_response, true);

if (!isset($userinfo['email'])) {
    $_SESSION['error_msg'] = 'Failed to get user information from Google.';
    header('Location: login.php');
    exit;
}

$email      = $userinfo['email'];
$name       = $userinfo['name'] ?? 'Google User';
$google_id  = $userinfo['id'] ?? null;
$picture    = $userinfo['picture'] ?? null;

// 4. Check if user already exists in database
$stmt = $conn->prepare("SELECT user_id, name, email, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // Existing user → just log them in
    $user = $result->fetch_assoc();
} else {
    // New user → create as Patient
    $role = 'patient';
    $password_hash = ''; // no password for Google users

    $insert = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssss", $name, $email, $password_hash, $role);
    $insert->execute();

    $user_id = $insert->insert_id;
    $insert->close();

    $user = [
        'user_id' => $user_id,
        'name'    => $name,
        'email'   => $email,
        'role'    => $role
    ];
}
$stmt->close();

// 5. Set session and redirect
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

if ($user['role'] === 'admin') {
    header('Location: admin/dashboard.php');
} elseif ($user['role'] === 'doctor') {
    header('Location: doctor/dashboard.php');
} else {
    header('Location: patients/dashboard.php');
}
exit;
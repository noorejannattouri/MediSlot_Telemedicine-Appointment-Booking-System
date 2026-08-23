<?php
echo "<h3>Manual Unit Testing - MediSlot</h3>";

// Test 1: Password Hash
$password = "123456";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo password_verify("123456", $hash) ? "✓ Password Hash Test Passed<br>" : "✗ Failed<br>";

// Test 2: Email Validation
$email = "test@medislot.com";
echo filter_var($email, FILTER_VALIDATE_EMAIL) ? "✓ Email Validation Passed<br>" : "✗ Failed<br>";

// Test 3: Role Check
$role = "patient";
echo in_array($role, ['patient','doctor','admin']) ? "✓ Role Validation Passed<br>" : "✗ Failed<br>";
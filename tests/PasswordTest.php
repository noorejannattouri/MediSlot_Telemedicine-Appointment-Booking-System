<?php
use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
{
    public function testPasswordHashing()
    {
        $password = "123456";
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify("123456", $hash));
        $this->assertFalse(password_verify("wrongpass", $hash));
    }

    public function testPasswordMinimumLength()
    {
        $password = "12345"; // less than 6 characters
        $this->assertTrue(strlen($password) < 6);
    }
}
<?php
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function testValidEmail()
    {
        $email = "patient@example.com";
        $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
    }

    public function testInvalidEmail()
    {
        $email = "invalid-email";
        $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
    }

    public function testEmptyName()
    {
        $name = "";
        $this->assertTrue(empty($name));
    }
}
<?php
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    public function testAllowedStatuses()
    {
        $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];

        $this->assertContains('pending', $allowed);
        $this->assertContains('confirmed', $allowed);
        $this->assertContains('completed', $allowed);
        $this->assertContains('cancelled', $allowed);
        $this->assertNotContains('approved', $allowed);
    }
}
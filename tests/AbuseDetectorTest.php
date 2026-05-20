<?php

declare(strict_types=1);

namespace Tests;

use App\Services\AbuseDetector;
use PHPUnit\Framework\TestCase;

final class AbuseDetectorTest extends TestCase
{
    public function testDetectsEmail(): void
    {
        $findings = AbuseDetector::scan('Здравей, пиши ми на [email protected]');
        $this->assertNotEmpty($findings);
        $this->assertSame('email', $findings[0]['pattern']);
        $this->assertTrue(AbuseDetector::shouldBlock($findings));
    }

    public function testDetectsPhone(): void
    {
        $findings = AbuseDetector::scan('обади се на +359 88 123 4567');
        $patterns = array_column($findings, 'pattern');
        $this->assertContains('phone_bg', $patterns);
    }

    public function testDetectsEgn(): void
    {
        $findings = AbuseDetector::scan('моят егн е 8001012345');
        $patterns = array_column($findings, 'pattern');
        $this->assertContains('ssn_egn', $patterns);
        $this->assertTrue(AbuseDetector::shouldBlock($findings));
    }

    public function testDetectsNameSelfDisclosure(): void
    {
        $findings = AbuseDetector::scan('Здрасти, казвам се Иван и аз съм автора.');
        $patterns = array_column($findings, 'pattern');
        $this->assertContains('name_self_disclosure', $patterns);
    }

    public function testDetectsWhitespaceObfuscation(): void
    {
        $findings = AbuseDetector::scan('казвам се и в а н от софия');
        $patterns = array_column($findings, 'pattern');
        $this->assertContains('whitespace_obfuscation', $patterns);
    }

    public function testCleanMessagePassesThrough(): void
    {
        $findings = AbuseDetector::scan('Темата на писмото е добра, ще обмисля предложението.');
        $this->assertSame([], $findings);
        $this->assertFalse(AbuseDetector::shouldBlock($findings));
    }

    public function testUrlIsLowSeverity(): void
    {
        $findings = AbuseDetector::scan('виж https://example.com/докум');
        $this->assertNotEmpty($findings);
        $this->assertFalse(AbuseDetector::shouldBlock($findings));
        $this->assertSame(1, AbuseDetector::maxSeverity($findings));
    }
}

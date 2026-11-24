<?php

namespace Tests\Feature;

use App\Services\AuditPayloadSanitizer;
use Tests\TestCase;

class AuditPayloadSanitizerTest extends TestCase
{
    public function test_sensitive_aliases_and_nested_audit_values_are_redacted(): void
    {
        $sanitizer = app(AuditPayloadSanitizer::class);
        $sanitized = $sanitizer->sanitize([
            'NIP' => '198001012006041001',
            'nested' => [
                'employee_identifier' => '198001012006041001',
                'student_id' => 'student-uuid',
                'grader_id' => 'grader-uuid',
                'device_fingerprint' => 'device-fingerprint',
                'answer_payload' => ['answer' => 'jawaban privat'],
                'evaluated_answer_snapshot' => ['value' => 'jawaban penilaian'],
                'feedback' => 'Catatan privat',
                'reason' => 'Alasan yang mungkin memuat PII',
                'original_name' => 'dokumen-identitas.pdf',
                'exception_message' => 'exception yang mungkin memuat rahasia',
                'ip_address' => '192.0.2.123',
                'user_agent' => 'Browser version lengkap',
            ],
            'status' => 'ACTIVE',
            'finished_at' => '2026-11-24T10:00:00+00:00',
            'analysis_count' => 3,
            'scoring_skipped_code' => 'MISSING_OR_INVALID_QUESTION_ORDER',
        ]);

        $this->assertSame('[REDACTED]', $sanitized['NIP']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['employee_identifier']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['student_id']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['grader_id']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['device_fingerprint']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['answer_payload']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['evaluated_answer_snapshot']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['feedback']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['reason']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['original_name']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['exception_message']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['ip_address']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['user_agent']);
        $this->assertSame('ACTIVE', $sanitized['status']);
        $this->assertSame('2026-11-24T10:00:00+00:00', $sanitized['finished_at']);
        $this->assertSame(3, $sanitized['analysis_count']);
        $this->assertSame('MISSING_OR_INVALID_QUESTION_ORDER', $sanitized['scoring_skipped_code']);
    }

    public function test_ip_address_is_reduced_to_network_prefix_and_user_agent_is_hmac_fingerprinted(): void
    {
        $sanitizer = app(AuditPayloadSanitizer::class);

        $this->assertSame('192.0.2.0', $sanitizer->anonymizeIpAddress('192.0.2.123'));
        $this->assertSame('2001:db8:abcd::', $sanitizer->anonymizeIpAddress('2001:db8:abcd:1234::1'));

        $fingerprint = $sanitizer->fingerprintUserAgent('Mozilla/5.0 Test Browser');
        $this->assertNotNull($fingerprint);
        $this->assertStringStartsWith('ua:', $fingerprint);
        $this->assertNotSame('Mozilla/5.0 Test Browser', $fingerprint);
        $this->assertSame($fingerprint, $sanitizer->fingerprintUserAgent('Mozilla/5.0 Test Browser'));
    }
}

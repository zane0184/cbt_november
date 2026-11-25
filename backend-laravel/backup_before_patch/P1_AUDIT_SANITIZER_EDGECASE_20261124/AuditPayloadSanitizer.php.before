<?php

namespace App\Services;

class AuditPayloadSanitizer
{
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password', 'passwd', 'secret', 'token', 'authorization', 'cookie',
        'credential', 'apikey', 'privatekey', 'publickey',
        'email', 'phone', 'mobile', 'telephone', 'contactnumber',
        'nip', 'nik', 'nisn', 'nis', 'nuptk', 'employeeidentifier', 'identifier',
        'address', 'birthdate', 'birthplace', 'fullname', 'studentname',
        'teachername', 'parentname', 'guardianname',
        'studentid', 'graderid', 'devicefingerprint', 'devicebindingid',
        'installationuuid', 'installationid',
        'answerpayload', 'answersnapshot', 'evaluatedanswersnapshot', 'feedback',
        'reason', 'originalname', 'filename', 'ipaddress', 'useragent',
        'exception', 'error',
    ];

    public function sanitize(?array $values): ?array
    {
        return $values === null ? null : $this->sanitizeArray($values);
    }

    public function anonymizeIpAddress(?string $ipAddress): ?string
    {
        if ($ipAddress === null || trim($ipAddress) === '') {
            return null;
        }

        $packed = @inet_pton(trim($ipAddress));
        if ($packed === false) {
            return null;
        }

        if (strlen($packed) === 4) {
            $packed[3] = "\0";
        } elseif (strlen($packed) === 16) {
            for ($index = 6; $index < 16; $index++) {
                $packed[$index] = "\0";
            }
        } else {
            return null;
        }

        $anonymized = inet_ntop($packed);
        return $anonymized === false ? null : $anonymized;
    }

    public function fingerprintUserAgent(?string $userAgent): ?string
    {
        $userAgent = trim((string) $userAgent);
        $applicationKey = (string) config('app.key');

        if ($userAgent === '' || $applicationKey === '') {
            return null;
        }

        return 'ua:' . substr(hash_hmac('sha256', $userAgent, $applicationKey), 0, 20);
    }

    private function sanitizeArray(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalizedKey = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $key));

                foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
                    if (str_contains($normalizedKey, $fragment)) {
                        $sanitized[$key] = '[REDACTED]';
                        continue 2;
                    }
                }
            }

            $sanitized[$key] = is_array($value)
                ? $this->sanitizeArray($value)
                : $value;
        }

        return $sanitized;
    }
}

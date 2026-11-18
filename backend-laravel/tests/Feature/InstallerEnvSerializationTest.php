<?php

namespace Tests\Feature;

use App\Services\InstallerService;
use Dotenv\Parser\Parser;
use Tests\TestCase;

class InstallerEnvSerializationTest extends TestCase
{
    public function test_environment_writer_round_trips_special_password_characters_without_replacement_interpolation(): void
    {
        $value = 'secret $1 # "quoted" \\ slash';
        $service = app(InstallerService::class);
        $method = new \ReflectionMethod($service, 'formatEnvValue');
        $method->setAccessible(true);
        $encoded = $method->invoke($service, $value);

        $entries = (new Parser())->parse('DB_PASSWORD=' . $encoded);
        $actual = $entries[0]->getValue()->get()->getChars();

        $this->assertSame($value, $actual);
    }
}

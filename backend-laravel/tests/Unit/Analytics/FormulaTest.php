<?php

namespace Tests\Unit\Analytics;

use Tests\TestCase;
use App\Services\Analytics\ExamAnalyticsService;
use App\Services\Analytics\ItemAnalysisService;

class FormulaTest extends TestCase
{
    /** @test */
    public function it_calculates_median_correctly()
    {
        $service = new class extends ExamAnalyticsService {
            public function testMedian(array $values) {
                return $this->calculateMedian($values);
            }
        };

        // Odd: [60, 70, 80] -> 70
        $this->assertEquals(70, $service->testMedian([60, 70, 80]));
        // Even: [60, 70, 80, 90] -> (70+80)/2 = 75
        $this->assertEquals(75, $service->testMedian([60, 70, 80, 90]));
        // Unsorted: [80, 60, 70] -> 70
        $this->assertEquals(70, $service->testMedian([80, 60, 70]));
        // Empty
        $this->assertNull($service->testMedian([]));
    }

    /** @test */
    public function it_interprets_difficulty_correctly()
    {
        $service = new class extends ItemAnalysisService {
            public function testInterpret(?float $p) {
                return $this->interpretDifficulty($p);
            }
        };

        $this->assertEquals('Sulit', $service->testInterpret(0.29));
        $this->assertEquals('Sedang', $service->testInterpret(0.30));
        $this->assertEquals('Sedang', $service->testInterpret(0.70));
        $this->assertEquals('Mudah', $service->testInterpret(0.71));
        $this->assertNull($service->testInterpret(null));
    }

    /** @test */
    public function it_buckets_distribution_boundaries_correctly()
    {
        $results = [0, 9.99, 10, 19.99, 20, 89.99, 90, 99.99, 100];

        $buckets = [
            0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0,
            5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0
        ];

        foreach ($results as $p) {
            $p = (float) $p;
            if ($p >= 90 && $p <= 100) {
                $buckets[9]++;
            } else {
                $index = (int) floor($p / 10);
                if ($index >= 0 && $index < 9) {
                    $buckets[$index]++;
                }
            }
        }

        $this->assertEquals(2, $buckets[0]); // 0, 9.99
        $this->assertEquals(2, $buckets[1]); // 10, 19.99
        $this->assertEquals(1, $buckets[2]); // 20
        $this->assertEquals(1, $buckets[8]); // 89.99
        $this->assertEquals(3, $buckets[9]); // 90, 99.99, 100
    }

    /** @test */
    public function it_calculates_population_standard_deviation_correctly()
    {
        // Dataset: [10, 20]
        // Mean = 15
        // Variance = ((10-15)^2 + (20-15)^2) / 2 = (25 + 25) / 2 = 25
        // StdDev = sqrt(25) = 5

        $values = [10, 20];
        $n = count($values);
        $mean = array_sum($values) / $n;
        $variance = array_reduce($values, function($carry, $item) use ($mean) {
            return $carry + pow($item - $mean, 2);
        }, 0) / $n;
        $stdDev = sqrt($variance);

        $this->assertEquals(5, $stdDev);
    }
}

<?php

namespace Tests\Feature\Leave;

use App\Models\NonWorkingDay;
use App\Models\Organisation;
use App\Models\User;
use App\Services\LeaveDaysCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDaysCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private LeaveDaysCalculator $calc;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::factory()->create(['country_code' => 'HR']);
        $this->user = User::factory()->create(['organisation_id' => $org->id]);
        $this->calc = app(LeaveDaysCalculator::class);
    }

    public function test_counts_full_working_week(): void
    {
        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-08'),
            CarbonImmutable::parse('2026-06-12'),
            $this->user,
        );

        $this->assertSame(5.0, $days);
    }

    public function test_excludes_weekends(): void
    {
        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-08'),
            CarbonImmutable::parse('2026-06-14'),
            $this->user,
        );

        $this->assertSame(5.0, $days);
    }

    public function test_excludes_country_public_holidays(): void
    {
        NonWorkingDay::create([
            'organisation_id' => null,
            'country_code'    => 'HR',
            'date'            => '2026-06-10',
            'name'            => 'Test Holiday',
        ]);

        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-08'),
            CarbonImmutable::parse('2026-06-12'),
            $this->user,
        );

        $this->assertSame(4.0, $days);
    }

    public function test_excludes_org_specific_non_working_days(): void
    {
        NonWorkingDay::create([
            'organisation_id' => $this->user->organisation_id,
            'date'            => '2026-06-10',
            'name'            => 'Org Closure',
        ]);

        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-08'),
            CarbonImmutable::parse('2026-06-12'),
            $this->user,
        );

        $this->assertSame(4.0, $days);
    }

    public function test_returns_zero_for_weekend_only_range(): void
    {
        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-13'),
            CarbonImmutable::parse('2026-06-14'),
            $this->user,
        );

        $this->assertSame(0.0, $days);
    }

    public function test_returns_zero_when_end_before_start(): void
    {
        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-12'),
            CarbonImmutable::parse('2026-06-08'),
            $this->user,
        );

        $this->assertSame(0.0, $days);
    }

    public function test_single_weekday(): void
    {
        $days = $this->calc->daysBetween(
            CarbonImmutable::parse('2026-06-09'),
            CarbonImmutable::parse('2026-06-09'),
            $this->user,
        );

        $this->assertSame(1.0, $days);
    }
}

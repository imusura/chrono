<?php

namespace App\Console\Commands;

use App\Models\NonWorkingDay;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:sync-public-holidays {year? : Year to sync (defaults to current year)} {country=HR : ISO 3166-1 alpha-2 country code}')]
#[Description('Sync public holidays from Nager.Date API')]
class SyncPublicHolidays extends Command
{
    public function handle(): int
    {
        $year = $this->argument('year') ?? now()->year;
        $country = strtoupper($this->argument('country'));

        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}");

        if ($response->failed()) {
            $this->error("Failed to fetch holidays for {$year}/{$country}: HTTP {$response->status()}");
            return self::FAILURE;
        }

        $holidays = $response->json();
        $synced = 0;

        foreach ($holidays as $holiday) {
            NonWorkingDay::updateOrCreate(
                ['organisation_id' => null, 'country_code' => $country, 'date' => $holiday['date']],
                ['name' => $holiday['localName']],
            );
            $synced++;
        }

        $this->info("Synced {$synced} public holidays for {$year}/{$country}.");

        return self::SUCCESS;
    }
}

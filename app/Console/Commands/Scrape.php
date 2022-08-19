<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Scrapers\Immobiliare\Scraper;
use App\Models\City;

class Scrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hack:scrape {city?} {contract?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape on immo';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $city = $this->argument('city');
        $contract = $this->argument('contract');
        if (is_null($city)) {
            $cities = City::all()->pluck('name');
        } else {
            $cities = [$city];
        }
        foreach ($cities as $city) {
            if (is_null($contract)) {
                $scraperInstance = new Scraper($city, 'vendita');
                $scraperInstance->scrape();
                $scraperInstance = new Scraper($city, 'affitto');
                $scraperInstance->scrape();
            } else {
                $scraperInstance = new Scraper($city, $contract);
                $scraperInstance->scrape();
            }
        }
    }
}

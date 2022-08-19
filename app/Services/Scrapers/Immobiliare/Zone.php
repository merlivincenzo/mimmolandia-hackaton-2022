<?php

namespace App\Services\Scrapers\Immobiliare;

use Illuminate\Support\Facades\Http;

class Zone
{
    public $city;

    public function __construct($city)
    {
        $this->city = $city;
        $this->url = 'https://www.immobiliare.it/search/autocomplete?macrozones=1&microzones=1&min_level=9&query=' . $city;
    }

    public function getZones()
    {
        $response = Http::get($this->url);
        $responseRow = collect($response->json())->filter(function($row) {
            return array_key_exists('macrozones', $row);
        })->first();

        return $responseRow;
    }
}
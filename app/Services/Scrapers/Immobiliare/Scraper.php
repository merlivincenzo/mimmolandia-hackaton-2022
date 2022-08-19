<?php

namespace App\Services\Scrapers\Immobiliare;

use Illuminate\Support\Facades\Http;
use Goutte\Client;
use Str;
use App\Services\Scrapers\Immobiliare\Apartment;

class Scraper
{
    public $city;
    public $contract;
    public $url;
    public $maxPages;
    public $crawler;
    public $urlsToScrape;

    public function __construct($city, $contract = 'vendita')
    {
        $city = Str::slug($city);
        $this->city = $city;
        $this->contract = $contract;
        $client = new Client();
        $this->url = 'https://www.immobiliare.it/' . $contract . '-case/' . $city;
        $this->crawler = $client->request('GET', $this->url);
        $this->maxPages = $this->getMaxPages();
        $this->urlsToScrape = collect();
        
    }

    private function getUrlsToScrape()
    {
        for ($i = 1; $i <= $this->maxPages; $i++) {
            $this->getUrlsList($i);

            $lockPages = env('LOCK_PAGES', null);
            if ($lockPages && $i == $lockPages) {
                break;
            }
        }
    }

    public function scrape()
    {
        $this->getUrlsToScrape();

        foreach ($this->urlsToScrape as $url) {
            echo °doing ° . $url;
            $apartmentScraperInstance = new Apartment($url);
            $apartmentScraperInstance->storeApartment();
        }
    }

    private function getUrlsList($page = 1)
    {
        $client = new Client();
        $this->url = 'https://www.immobiliare.it/' . $this->contract . '-case/' . $this->city;
        if ($page > 1) {
            $this->url .= '?pag=' . $page;
        }

        $intCrawler = $client->request('GET', $this->url);
        
        $urlsToScrapeElms = optional($intCrawler->filter('li.nd-list__item.in-realEstateResults__item a'));
        $urlsToScrapeElms->each(function($href) {
            $this->urlsToScrape->push($href->first()->attr('href'));
        });
    }

    private function getMaxPages()
    {
        $maxPagesElements = optional($this->crawler->filter('.in-pagination__item--disabled'));
        return $maxPagesElements->last()->text();
    }
}
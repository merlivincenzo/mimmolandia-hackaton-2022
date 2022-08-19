<?php

namespace App\Services\Scrapers\Immobiliare;

use Goutte\Client;
use App\Models\Apartment as ModelApartment;
use App\Models\City as ModelCity;
use App\Models\Area as ModelArea;
use App\Models\Typology as ModelTypology;
use App\Models\User;
use Illuminate\Support\Str;
use Geocoder\Laravel\Facades\Geocoder;
use App\Notifications\ApartmentChanged;

class Apartment
{
    private $crawler;
    private $url;
    private $mainFeatures;
    private $features;
    private $otherFeatures;
    private $jsonObject;

    public function __construct($url)
    {
        $this->url = $url;
        $client = new Client();
        $this->crawler = $client->request('GET', $this->url);
        $this->fetchMainFeatures();
        $this->fetchFeatures();
        $this->fetchOtherFeatures();
        $this->fetchJsonObject();
    }

    private function fetchJsonObject()
    {
        $jsonObject = optional($this->crawler->filter('#js-hydration'));
        $this->jsonObject = json_decode($jsonObject->first()->text(), true);
    }

    private function fetchMainFeatures()
    {
        $mainFeaturesElements = optional($this->crawler->filter('.im-mainFeatures .nd-list__item'));
        $this->mainFeatures = collect();
        $mainFeaturesElements->each(function($featuredElement) {
            $this->mainFeatures->push($featuredElement->first()->text());
        });
    }

    private function fetchFeatures()
    {
        $featuresElementsKeys = optional($this->crawler->filter('dl.im-features__list dt'));
        $featuresElementsValues = optional($this->crawler->filter('dl.im-features__list dd'));
        $keys = collect();
        $values = collect();
        $featuresElementsKeys->each(function($node) use(&$keys) {
            $keys->push(Str::snake($node->first()->text()));
        });
        $featuresElementsValues->each(function($node) use(&$values) {
            $values->push($node->first()->text());
        });

        $this->features = $keys->combine($values);
    }

    private function fetchOtherFeatures()
    {
        $otherFeatures = optional($this->crawler->filter('dd.im-features__value.im-features__tagContainer .im-features__tag'));
        $this->otherFeatures = collect();
        $otherFeatures->each(function($otherFeature) {
            $this->otherFeatures->push($otherFeature->first()->text());
        });
    }

    public function getAdHeadline()
    {
        return optional($this->crawler->filter('h1 span.im-titleBlock__title')->first())->text();
    }

    public function getPrice()
    {
        $strPrice = $this->mainFeatures->first();
        if (isset($this->jsonObject['listing']['price']) && isset($this->jsonObject['listing']['price']['price'])) {
            $intPrice = $this->jsonObject['listing']['price']['price'];
        } else {
            $intPrice = (int) filter_var($strPrice, FILTER_SANITIZE_NUMBER_INT);
        }
        return $intPrice;
    }

    public function getLocations()
    {
        $locationElements = optional($this->crawler->filter('.im-titleBlock__link .im-location'));
        $city = null;
        $area = null;
        $address = null;
        $lat = $this->jsonObject['listing']['properties'][0]['location']['latitude'];
        $lng = $this->jsonObject['listing']['properties'][0]['location']['longitude'];
        $index = 0;
        $locationsCount = $locationElements->count();
        $locationElements->each(function($locationElement) use(&$city, &$area, &$address, &$index, $locationsCount) {
            if ($locationsCount > 2) {
                switch($index++) {
                    case 0:
                        $city = $locationElement->first()->text();    
                        break;
                    case 1:
                        $area = $locationElement->first()->text();
                        break;
                    case 2:
                        $address = $locationElement->first()->text();
                        break;
                    default:
                        break;
                }
            } else {
                switch($index++) {
                    case 0:
                        $city = $locationElement->first()->text();    
                        break;
                    case 2:
                        $address = $locationElement->first()->text();
                        break;                        
                    default:
                        break;
                }
            }
        });

        $provinceElements = optional($this->crawler->filter('.nd-list.nd-list--pipe.im-relatedLink__list li'));
        $province = null;
        $provinceElements->each(function($node) use(&$province) {
            $string = $node->first()->text();
            if (Str::contains(strtolower($string), ['provincia'])) {
                $province = trim(explode('provincia', $string)[1]);
            }
        });

        $addressElements = optional($this->crawler->filter('nd-map.im-map__mapInpage .im-location'));
        $addressWithNumber = $addressElements->last()->text();
        $address = !empty($addressWithNumber) ? $addressWithNumber : $address;

        if (!isset($area)) {
            $area = $this->jsonObject['listing']['properties'][0]['location']['macrozone']['name'];   
        }
        
        return [
            'city' => $city, 
            'area' => $area, 
            'province' => $province, 
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng
        ];
    }

    public function getSquareMt()
    {
        if (isset($this->jsonObject['listing']['properties'][0]) && isset($this->jsonObject['listing']['properties'][0]['surfaceValue'])) {
            $priceFiltered = explode(' ', $this->jsonObject['listing']['properties'][0]['surfaceValue'])[0];
            $intPrice = (int) filter_var($priceFiltered, FILTER_SANITIZE_NUMBER_INT);
        } else {
            $strSquareMt = $this->mainFeatures[2];
            $intPrice = (int) filter_var($strSquareMt, FILTER_SANITIZE_NUMBER_INT);
        }
        return $intPrice;
    }

    public function getTypologies()
    {
        $typologies = collect(explode('|', $this->features['tipologia']))->map(function($typology) {
            return trim($typology);
        });
        return $typologies;
    }

    public function getFloor()
    {
        $strFloor = isset($this->mainFeatures[4]) ? $this->mainFeatures[4] : null;
        return (int) filter_var($strFloor, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getCondoFees()
    {
        $strCondoFees = isset($this->features['spese_condominio']) ? $this->features['spese_condominio'] : 0;
        return (int) filter_var($strCondoFees, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getHasGarden()
    {
        $hasGarden = $this->otherFeatures->reduce(function ($carry, $value) {
            return $carry || Str::contains(strtolower($value), ['giardino']);
        }, false);

        return $hasGarden;
    }

    public function getHasGarage()
    {
        if (!array_key_exists('posti_auto', $this->features->toArray())) {
            return false;
        }

        return Str::contains(strtolower($this->features['posti_auto']), ['garage', 'box']);
    }

    public function getContract()
    {
        $strContract = $this->jsonObject['listing']['contract']['name'] ?? $this->features['contratto'];
        return strtolower($strContract);
    }

    public function getBlob()
    {
        $blobElement = optional($this->crawler->filter('.im-description__text.js-readAllText'));
        return $blobElement->first()->text();
    }

    public function getExternalId()
    {
        return (int) abs(filter_var($this->url, FILTER_SANITIZE_NUMBER_INT)); // why not bro?!
    }

    public function getGallery()
    {
        $pictures = [];
        $this->crawlerDefault->filter('.images .item')->each(function ($node) use (&$pictures) {
            $pictures[] = $node->attr('data-gallery');
        });

        return $pictures;
    }

    public function storeApartment()
    {
        if (is_null($this->crawler->filter('.is-not-salable'))) {
            return;
        }
        $apartmentParams = [];

        $locations = $this->getLocations();

        $city = ModelCity::firstOrCreate(['name' => $locations['city']], [
            'name' => $locations['city'],
            'province' => $locations['province']
        ]);
        $area = ModelArea::firstOrCreate(['name' => $locations['area'], 'city_id' => $city->id], [
            'name' => $locations['area'],
            'city_id' => $city->id
        ]);
        $typologies = $this->getTypologies();
        $apartmentTypologiesIds = [];
        $typologies->each(function($typology) use(&$apartmentTypologiesIds) {
            $typology = ModelTypology::firstOrCreate(['name' => $typology], [
                'name' => $typology
            ]);
            $apartmentTypologiesIds[] = $typology->id;
        });

        $apartmentParams['ad_headline'] = $this->getAdHeadline();
        $apartmentParams['ad_blob'] = $this->getBlob();
        $apartmentParams['url'] = $this->url;
        $apartmentParams['external_id'] = $this->getExternalId();
        $apartmentParams['address'] = $locations['address'];
        $apartmentParams['lat'] = $locations['lat'];
        $apartmentParams['lng'] = $locations['lng'];
        $apartmentParams['price'] = $this->getPrice();
        $apartmentParams['condo_fees'] = $this->getCondoFees();
        $apartmentParams['city_id'] = $city->id;
        $apartmentParams['area_id'] = $area->id;
        $apartmentParams['square_mt'] = $this->getSquareMt();
        $apartmentParams['floor'] = $this->getFloor();
        $apartmentParams['garden'] = $this->getHasGarden();
        $apartmentParams['garage'] = $this->getHasGarage();
        $apartmentParams['contract'] = $this->getContract();

        $apartment = ModelApartment::updateOrCreate(
            ['url' => $this->url],
            $apartmentParams,
        );
        $apartment->typologies()->sync($apartmentTypologiesIds);

        if ($apartment->wasChanged(['price'])) {
            $userToNotify = User::all();
            $userToNotify->each->notify(new ApartmentChanged($apartment));
            $apartment->apartmentHistories()->create([
                'changes' => $apartment->getOriginal()
            ]);
        }

        return $apartment;
    }
}
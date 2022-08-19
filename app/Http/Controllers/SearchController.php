<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Apartment;
use Geocoder\Laravel\Facades\Geocoder;

class SearchController extends Controller
{
    private function getQuery($contract = 'vendita')
    {
        $qb = Apartment::query();
        if (request()->has('city')) {
            $geocodedAddress = Geocoder::geocode(request()->city)->get()->first();
            if ($geocodedAddress) {
                $qb->selectRaw("*, ST_Distance_Sphere(point(lng, lat), point(" . $geocodedAddress->getCoordinates()->getLongitude() . ", " . $geocodedAddress->getCoordinates()->getLatitude() . ")) as distance")
                    ->having("distance", "<=", request()->distance * 1000);
            }
        }

        return $qb->where('contract', $contract);
    }

    public function index(Request $request)
    {
        $qb = $this->getQuery();
        $avgPrice = $qb->get()->avg('price');

        if (request()->ajax()) {
            $qb = $this->getQuery();

            return DataTables::eloquent($qb)
                ->addColumn('city_name', function ($row) {
                    return optional($row->city)->name;
                })
                ->editColumn('updated_at', function ($row) {
                    return $row->updated_at->format('d/m/Y H:i');
                })
                ->editColumn('price', function ($row) {
                    return "€ " . number_format($row->price, 2, ',', '.');
                })
                ->editColumn('ad_headline', function ($row) {
                    return "<a target='_blank' href='" . $row->url . "'>" . $row->ad_headline . "</a>";
                })
                ->addColumn('distance', function ($row) {
                    if (request()->has('city')) {
                        return number_format(($row->distance ?? 0) / 1000, 2, ',', '.') . " Km";
                    }
                })
                ->escapeColumns([])
                //->rawColumns(['ad_headline'])
                ->make(true);
        }

        return view('search', ['avgPrice' => $avgPrice]);
    }

    public function indexAvg(Request $request)
    {
        $qb = $this->getQuery();
        $avgPrice = $qb->where('price', '>', 0)->get()->avg('price');

        if (request()->ajax()) {
            $qb = Apartment::query()
                ->where('contract', 'vendita')
                ->where(function($query) use($avgPrice) {
                    $avgMinLimit = $avgPrice - $avgPrice * .4;
                    $avgMaxLimit = $avgPrice + $avgPrice * .4;
                    $query->where('price', '>=', $avgMaxLimit)
                        ->orWhere('price', '<=', $avgMinLimit);
                });
            if ($request->has('city')) {
                $geocodedAddress = Geocoder::geocode($request->city)->get()->first();
                if ($geocodedAddress) {
                    $qb->selectRaw("*, ST_Distance_Sphere(point(lng, lat), point(" . $geocodedAddress->getCoordinates()->getLongitude() . ", " . $geocodedAddress->getCoordinates()->getLatitude() . ")) as distance")
                        ->where('contract', 'vendita')
                        ->having("distance", "<=", $request->distance * 1000);
                }
            }

            return DataTables::eloquent($qb)
                ->addColumn('city_name', function ($row) {
                    return optional($row->city)->name;
                })
                ->editColumn('updated_at', function ($row) {
                    return $row->updated_at->format('d/m/Y H:i');
                })
                ->editColumn('price', function ($row) {
                    return "€ " . number_format($row->price, 2, ',', '.');
                })
                ->editColumn('ad_headline', function ($row) {
                    return "<a target='_blank' href='" . $row->url . "'>" . $row->ad_headline . "</a>";
                })
                ->addColumn('distance', function ($row) {
                    if (request()->has('city')) {
                        return number_format(($row->distance ?? 0) / 1000, 2, ',', '.') . " Km";
                    }
                })
                ->rawColumns(['ad_headline'])
                ->make(true);
        }

        return view('search');
    }

    public function indexAffitto(Request $request)
    {
        $qb = $this->getQuery('affitto');
        $avgPrice = $qb->get()->avg('price');

        if (request()->ajax()) {
            $qb = $this->getQuery('affitto');

            return DataTables::eloquent($qb)
                ->addColumn('city_name', function ($row) {
                    return optional($row->city)->name;
                })
                ->editColumn('updated_at', function ($row) {
                    return $row->updated_at->format('d/m/Y H:i');
                })
                ->editColumn('price', function ($row) {
                    return "€ " . number_format($row->price, 2, ',', '.');
                })
                ->editColumn('ad_headline', function ($row) {
                    return "<a target='_blank' href='" . $row->url . "'>" . $row->ad_headline . "</a>";
                })
                ->addColumn('distance', function ($row) {
                    if (request()->has('city')) {
                        return number_format(($row->distance ?? 0) / 1000, 2, ',', '.') . " Km";
                    }
                })
                ->rawColumns(['ad_headline'])
                ->make(true);
        }

        return view('search-affitto', ['avgPrice' => $avgPrice]);
    }

    public function indexAffittoAvg(Request $request)
    {
        $qb = $this->getQuery('affitto');
        $avgPrice = $qb->where('price', '>', 0)->get()->avg('price');

        if (request()->ajax()) {
            $qb = Apartment::query()
                ->where('contract', 'affitto')
                ->where(function($query) use($avgPrice) {
                    $avgMinLimit = $avgPrice - $avgPrice * .4;
                    $avgMaxLimit = $avgPrice + $avgPrice * .4;
                    $query->where('price', '>=', $avgMaxLimit)
                        ->orWhere('price', '<=', $avgMinLimit);
                });
            if ($request->has('city')) {
                $geocodedAddress = Geocoder::geocode($request->city)->get()->first();
                if ($geocodedAddress) {
                    $qb->selectRaw("*, ST_Distance_Sphere(point(lng, lat), point(" . $geocodedAddress->getCoordinates()->getLongitude() . ", " . $geocodedAddress->getCoordinates()->getLatitude() . ")) as distance")
                        ->where('contract', 'affitto')
                        ->having("distance", "<=", $request->distance * 1000);
                }
            }

            return DataTables::eloquent($qb)
                ->addColumn('city_name', function ($row) {
                    return optional($row->city)->name;
                })
                ->editColumn('updated_at', function ($row) {
                    return $row->updated_at->format('d/m/Y H:i');
                })
                ->editColumn('price', function ($row) {
                    return "€ " . number_format($row->price, 2, ',', '.');
                })
                ->editColumn('ad_headline', function ($row) {
                    return "<a target='_blank' href='" . $row->url . "'>" . $row->ad_headline . "</a>";
                })
                ->addColumn('distance', function ($row) {
                    if (request()->has('city')) {
                        return number_format(($row->distance ?? 0) / 1000, 2, ',', '.') . " Km";
                    }
                })
                ->rawColumns(['ad_headline'])
                ->make(true);
        }

        return view('search-affitto');
    }
}

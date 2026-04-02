<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AcBrand;
use App\Models\AcCapacity;
use App\Models\AcCatalog;
use App\Models\AcType;
use Illuminate\Http\Request;

class OwnerAcMasterController extends BaseApiController
{
    public function brands()
    {
        $brands = AcBrand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->ok($brands);
    }

    public function types(Request $request)
    {
        $request->validate([
            'brand_id' => 'nullable|integer|exists:ac_brands,id',
        ]);

        $q = AcType::query()
            ->where('is_active', true);

        if ($request->filled('brand_id')) {
            $q->whereIn('id', function ($sub) use ($request) {
                $sub->select('type_id')
                    ->from('ac_catalogs')
                    ->where('brand_id', $request->brand_id)
                    ->where('is_active', true);
            });
        }

        $types = $q->orderBy('name')->get();

        return $this->ok($types);
    }

    public function series(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|integer|exists:ac_brands,id',
            'type_id' => 'nullable|integer|exists:ac_types,id',
        ]);

        $q = AcCatalog::query()
            ->where('is_active', true)
            ->where('brand_id', $request->brand_id);

        if ($request->filled('type_id')) {
            $q->where('type_id', $request->type_id);
        }

        $series = $q->select('id', 'series', 'type_id', 'capacity_id')
            ->with([
                'type:id,name',
                'capacity:id,name',
            ])
            ->orderBy('series')
            ->get();

        return $this->ok($series);
    }

    public function capacities(Request $request)
    {
        $request->validate([
            'brand_id' => 'nullable|integer|exists:ac_brands,id',
            'type_id' => 'nullable|integer|exists:ac_types,id',
        ]);

        $q = AcCapacity::query()
            ->where('is_active', true);

        if ($request->filled('brand_id') || $request->filled('type_id')) {
            $q->whereIn('id', function ($sub) use ($request) {
                $sub->select('capacity_id')
                    ->from('ac_catalogs')
                    ->where('is_active', true);

                if ($request->filled('brand_id')) {
                    $sub->where('brand_id', $request->brand_id);
                }

                if ($request->filled('type_id')) {
                    $sub->where('type_id', $request->type_id);
                }
            });
        }

        $capacities = $q->orderBy('name')->get();

        return $this->ok($capacities);
    }

    public function formOptions(Request $request)
    {
        $request->validate([
            'brand_id' => 'nullable|integer|exists:ac_brands,id',
            'type_id' => 'nullable|integer|exists:ac_types,id',
        ]);

        $brands = AcBrand::where('is_active', true)
            ->orderBy('name')
            ->get();

        $typesQuery = AcType::where('is_active', true);

        if ($request->filled('brand_id')) {
            $typesQuery->whereIn('id', function ($sub) use ($request) {
                $sub->select('type_id')
                    ->from('ac_catalogs')
                    ->where('brand_id', $request->brand_id)
                    ->where('is_active', true);
            });
        }

        $types = $typesQuery->orderBy('name')->get();

        $series = collect();
        if ($request->filled('brand_id')) {
            $seriesQuery = AcCatalog::query()
                ->where('is_active', true)
                ->where('brand_id', $request->brand_id);

            if ($request->filled('type_id')) {
                $seriesQuery->where('type_id', $request->type_id);
            }

            $series = $seriesQuery
                ->select('id', 'series', 'type_id', 'capacity_id')
                ->with([
                    'type:id,name',
                    'capacity:id,name',
                ])
                ->orderBy('series')
                ->get();
        }

        $capacitiesQuery = AcCapacity::where('is_active', true);

        if ($request->filled('brand_id') || $request->filled('type_id')) {
            $capacitiesQuery->whereIn('id', function ($sub) use ($request) {
                $sub->select('capacity_id')
                    ->from('ac_catalogs')
                    ->where('is_active', true);

                if ($request->filled('brand_id')) {
                    $sub->where('brand_id', $request->brand_id);
                }

                if ($request->filled('type_id')) {
                    $sub->where('type_id', $request->type_id);
                }
            });
        }

        $capacities = $capacitiesQuery->orderBy('name')->get();

        return $this->ok([
            'brands' => $brands,
            'types' => $types,
            'series' => $series,
            'capacities' => $capacities,
        ]);
    }
}
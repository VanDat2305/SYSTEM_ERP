<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProvinceController extends Controller
{
    // Lấy tất cả tỉnh/thành
    public function getProvinces()
    {
        $cacheKey = 'vn_provinces';

        $provinces = Cache::remember($cacheKey, now()->addHours(24), function () {
            $response = Http::get('https://provinces.open-api.vn/api/p/');
            return $response->successful() ? $response->json() : [];
        });

        return response()->json($provinces);
    }

    // Lấy quận/huyện theo tỉnh
    public function getDistricts($provinceCode)
    {
        $cacheKey = "vn_districts_{$provinceCode}";

        $districts = Cache::remember($cacheKey, now()->addHours(24), function () use ($provinceCode) {
            $response = Http::get("https://provinces.open-api.vn/api/p/{$provinceCode}?depth=2");
            return $response->successful() ? ($response->json()['districts'] ?? []) : [];
        });

        return response()->json($districts);
    }

    // Lấy phường/xã theo quận
    public function getWards($districtCode)
    {
        $cacheKey = "vn_wards_{$districtCode}";

        $wards = Cache::remember($cacheKey, now()->addHours(24), function () use ($districtCode) {
            $response = Http::get("https://provinces.open-api.vn/api/d/{$districtCode}?depth=2");
            return $response->successful() ? ($response->json()['wards'] ?? []) : [];
        });

        return response()->json($wards);
    }
}

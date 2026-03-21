<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AnafController extends Controller
{
    use ApiResponder;

    public function lookupCompany(Request $request)
    {
        $data = $request->validate([
            'cui'  => 'required|integer',
            'data' => 'required|date_format:Y-m-d',
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva', [
            [
                'cui'  => (int) $data['cui'],
                'data' => $data['data'],
            ],
        ]);

        if ($response->failed()) {
            return $this->error('ANAF service unavailable', 502);
        }

        return $this->success($response->json(), 'Company data retrieved successfully');
    }
}

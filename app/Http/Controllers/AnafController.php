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
        ])->post('https://andreeaberkhout.oph.st/webhook-test/a1c92927-b2b9-48e6-864b-86a7a2022ed3', [
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

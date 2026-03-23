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
        return $this->success(null, 'endpoint called');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CldResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CldController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $clds = $request->user()
            ->clds()
            ->with('groupement.sector')
            ->paginate(20);

        return CldResource::collection($clds);
    }
}

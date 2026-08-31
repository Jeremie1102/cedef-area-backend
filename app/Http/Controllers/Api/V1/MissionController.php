<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $missions = $request->user()
            ->missions()
            ->with(['sector', 'groupement', 'clds', 'villages'])
            ->orderByDesc('date_debut_prevue')
            ->paginate(20);

        return MissionResource::collection($missions);
    }
}

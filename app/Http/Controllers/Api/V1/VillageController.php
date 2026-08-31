<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VillageResource;
use App\Models\Cld;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VillageController extends Controller
{
    public function index(Request $request, Cld $cld): AnonymousResourceCollection
    {
        $this->authorize('view', $cld);

        $villages = $cld->villages()->paginate(50);

        return VillageResource::collection($villages);
    }
}

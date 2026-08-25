<?php

namespace App\Http\Controllers\Organization;

use App\Data\StoreOrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Models\Organization;
use Illuminate\Support\Str;

class StoreOrganizationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreOrganizationRequest $request)
    {
        $payload = $request->validated();

        $data = StoreOrganizationData::from(
            $payload, 
            [
                'owner_id' => $request->user()->id,
                'slug' => Str::slug($payload['name'])
            ]
        );

        return Organization::create($data->toArray());
    }
}

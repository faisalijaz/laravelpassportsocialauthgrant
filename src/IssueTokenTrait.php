<?php

namespace faisalijaz\laravelpassportsocialauthgrant\src;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

trait IssueTokenTrait
{
    public function issueToken(Request $request, $grant_type = 'social', $scope = '*')
    {
        $params = [
            'grant_type' => 'social',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'platform' => $request->platform,
            'platform_user_id' => $request->platform_user_id,
            'scope' => $scope,
        ];

        $request->request->add($params);

        $proxy = Request::create('oauth/token', 'POST');

        return Route::dispatch($proxy);
    }
}

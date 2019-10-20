<?php

namespace faisalijaz\laravelpassportsocialauthgrant\src\Http;

use faisalijaz\laravelpassportsocialauthgrant\src\IssueTokenTrait;
use faisalijaz\laravelpassportsocialauthgrant\src\Model\SocialUser;
use faisalijaz\laravelpassportsocialauthgrant\src\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Laravel\Passport\Client;

class SocialAuth
{
    use IssueTokenTrait;

    private $client;

    /**
     * SocialAuthController constructor.
     */
    public function __construct()
    {
        $this->client = Client::find(1);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function socialAuth(Request $request)
    {
        $validateData = Validator::make((array)$this->request->all(), [
            'name' => 'required',
            'email' => 'required',
            'platform' => 'required|in:facebook,twitter,google,linkedin',
            'platform_user_id' => 'required',
        ]);

        $errors = $validateData->errors();

        if ($errors->isNotEmpty()) {
            return response(compact('errors'), 400);
        }

        $social_user = SocialUser::where('platform', $request->platform)
            ->where('platform_user_id', $request->platform_user_id)
            ->first();

        if ($social_user) {
            return $this->issueToken($request);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $this->addSocialAccountToUser($request, $user);
        } else {

            try {

                $this->createUserAccount($request);

            } catch (\Exception $exception) {

                return response('An error occoured, please try later', 422);
            }
        }

        return $this->issueToken($request);
    }

    /***
     * @param Request $request
     * @param User    $user
     * @throws \Illuminate\Validation\ValidationException
     */
    private function addSocialAccountToUser(Request $request, User $user)
    {
        $this->validate($request, [
            'platform' => [
                Rule::unique('social_users')->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                })],
            'platform_user_id' => 'required'
        ]);


        $user->SocialUser()->create([
            'platform' => $request->platform,
            'platform_user_id' => $request->platform_user_id,
            'token' => $request->token
        ]);

    }

    /**
     * @param Request $request
     */
    private function createUserAccount(Request $request)
    {
        DB::transaction(function () use ($request) {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email
            ]);

            $this->addSocialAccountToUser($request, $user);
        });
    }

}

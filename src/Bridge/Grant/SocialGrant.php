<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/17/19
 * Time: 5:00 PM
 */

namespace faisalijaz\laravelpassportsocialauthgrant\src\Bridge\Grant;


use DateInterval;
use faisalijaz\laravelpassportsocialauthgrant\src\Model\SocialUser;
use faisalijaz\laravelpassportsocialauthgrant\src\Model\User;
use Illuminate\Http\Request;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

class SocialGrant extends AbstractGrant
{
    /**
     * SocialGrant constructor.
     * @param UserRepositoryInterface         $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @throws \Exception
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository
    )
    {
        $this->setUserRepository($userRepository);
        $this->setRefreshTokenRepository($refreshTokenRepository);

        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseTypeInterface  $responseType
     * @param DateInterval           $accessTokenTTL
     * @return ResponseTypeInterface
     * @throws OAuthServerException
     * @throws \League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    )
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        $this->getEmitter()->emit(new RequestEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request));
        $responseType->setAccessToken($accessToken);

        // Issue and persist new refresh token if given
        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($refreshToken !== null) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request));
            $responseType->setRefreshToken($refreshToken);
        }

        return $responseType;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface  $client
     * @return User|void
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        $platform = $this->getRequestParameter('platform', $request);
        if (is_null($platform)) {
            throw OAuthServerException::invalidRequest('platform');
        }

        $platform_user_id = $this->getRequestParameter('platform_user_id', $request);
        if (is_null($platform_user_id)) {
            throw OAuthServerException::invalidRequest('platform_user_id');
        }

        $user = $this->getUserFromSocialNetwork(new Request($request->getParsedBody()));

        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     * @param Request $request
     * @return User|void
     */
    private function getUserFromSocialNetwork(Request $request)
    {
        #Load basic config from UserRepo
        # url: vendor/laravel/passport/src/Bridge/UserRepository.php@getUserEntityByUserCredentials
        $provider = config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        //Check if user exists in local db
        $social_user = SocialUser::where('platform', $request->platform)
            ->where('platform_user_id', $request->platform_user_id)
            ->first();

        if (!$social_user) return;

        $user = $social_user->user()->first();

        if (!$user) ;

        return new User($user->getAuthIdentifier());
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'social';
    }
}

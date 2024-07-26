<?php

namespace SocialiteProviders\Sber;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use Illuminate\Support\Facades\Log;

class Provider extends AbstractProvider
{
    protected $fields = [
        'sub', 'email', 'phone_number', 'given_name', 'family_name', 'middle_name', 'birthdate', 
        'passport', 'inn', 'snils', 'driving_license', 'international_passport', 'priority_doc', 
        'citizenship', 'place_of_birth', 'address', 'job_info', 'education', 'marital_status'
    ];

    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'SBER';

    /**
     * {@inheritdoc}
     */
    protected $stateless = false;

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid', 'profile', 'email', 'phone', 'address', 
        'passport', 'inn', 'snils', 'driving_license', 
        'international_passport', 'priority_doc', 'citizenship', 
        'place_of_birth', 'job_info', 'education', 'marital_status'
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://sberid.api.sberbank.ru:9443/auth/realms/sberid/protocol/openid-connect/auth',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://sberid.api.sberbank.ru:9443/auth/realms/sberid/protocol/openid-connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $params = http_build_query([
            'access_token' => $token,
        ]);

        $response = $this->getHttpClient()->get('https://sberid.api.sberbank.ru:9443/auth/realms/sberid/protocol/openid-connect/userinfo?' . $params);

        $contents = (string) $response->getBody();

        $response = json_decode($contents, true);

        Log::info('Sber ID API response: ', $response); // Log full response

        if (!is_array($response) || !isset($response['sub'])) {
            throw new RuntimeException(sprintf(
                'Invalid JSON response from Sber ID: %s',
                $contents
            ));
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken($response['access_token']));

        $this->credentialsResponseBody = $response;

        if ($user instanceof User) {
            $user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        return $user->setToken($this->parseAccessToken($response))
            ->setExpiresIn($this->parseExpiresIn($response));
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'                => Arr::get($user, 'sub'),
            'nickname'          => Arr::get($user, 'preferred_username'),
            'name'              => Arr::get($user, 'given_name'),
            'surname'           => Arr::get($user, 'family_name'),
            'middle_name'       => Arr::get($user, 'middle_name'),
            'email'             => Arr::get($user, 'email'),
            'phone'             => Arr::get($user, 'phone_number'),
            'birthdate'         => Arr::get($user, 'birthdate'),
            'passport'          => Arr::get($user, 'passport'),
            'inn'               => Arr::get($user, 'inn'),
            'snils'             => Arr::get($user, 'snils'),
            'driving_license'   => Arr::get($user, 'driving_license'),
            'international_passport' => Arr::get($user, 'international_passport'),
            'priority_doc'      => Arr::get($user, 'priority_doc'),
            'citizenship'       => Arr::get($user, 'citizenship'),
            'place_of_birth'    => Arr::get($user, 'place_of_birth'),
            'address'           => Arr::get($user, 'address'),
            'job_info'          => Arr::get($user, 'job_info'),
            'education'         => Arr::get($user, 'education'),
            'marital_status'    => Arr::get($user, 'marital_status'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    }

    /**
     * Set the user fields to request from Sber ID.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }
}

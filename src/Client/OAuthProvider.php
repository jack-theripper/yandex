<?php

namespace Arhitector\Yandex\Client;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * The implementation of `league/oauth2-client` provider for more easy handling of disk tokens.
 *
 * @package Arhitector\Yandex\Client
 */
class OAuthProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;
    
    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://oauth.yandex.ru/authorize';
    }
    
    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://oauth.yandex.ru/token';
    }
    
    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://login.yandex.ru/info?format=json&oauth_token='.$token->getToken();
    }
    
    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        //        0	"cloud_api:disk.app_folder"
        //1	"cloud_api:disk.read"
        //2	"cloud_api:disk.write"
        //3	"cloud_api:disk.info"
        // Если параметры scope и optional_scope не переданы, то токен будет выдан с правами, указанными при регистрации приложения.
        
        return [];
    }
    
    /**
     * Checks a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array|string      $data Parsed response data
     * @return void
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ( ! isset($data['error']))
        {
            return;
        }
        
        throw new IdentityProviderException($data['error'].': '.$data['error_description'], $response->getStatusCode(),
            $response);
    }
    
    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param array       $response
     * @param AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        // TODO: Implement createResourceOwner() method.
    }
    
}

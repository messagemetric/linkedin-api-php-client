<?php

namespace LinkedIn;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use LinkedIn\Http\Method;
use Psr\Http\Message\ResponseInterface;

class Client
{
    const OAUTH2_GRANT_TYPE = 'authorization_code';
    const OAUTH2_RESPONSE_TYPE = 'code';
    const OAUTH2_API_ROOT = 'https://www.linkedin.com/oauth/v2/';
    const API_ROOT = 'https://api.linkedin.com/v2/';

    protected string $clientId = '';
    protected string $clientSecret = '';
    protected ?AccessToken $accessToken = null;
    protected ?string $state = null;
    protected ?string $redirectUrl = null;
    protected string $apiRoot = self::API_ROOT;
    protected string $oAuthApiRoot = self::OAUTH2_API_ROOT;
    protected bool $useTokenParam = false;
    protected array $apiHeaders = [
        'Content-Type' => 'application/json',
        'x-li-format' => 'json',
    ];

    public function __construct(
        string $clientId = '',
        string $clientSecret = '',
    ) {
        if ($clientId !== '') {
            $this->clientId = $clientId;
        }
        if ($clientSecret !== '') {
            $this->clientSecret = $clientSecret;
        }
    }

    public function isUsingTokenParam(): bool
    {
        return $this->useTokenParam;
    }

    public function setUseTokenParam(bool $useTokenParam): self
    {
        $this->useTokenParam = $useTokenParam;
        return $this;
    }

    public function getApiHeaders(): array
    {
        return $this->apiHeaders;
    }

    public function setApiHeaders(array $apiHeaders): self
    {
        $this->apiHeaders = $apiHeaders;
        return $this;
    }

    public function getApiRoot(): string
    {
        return $this->apiRoot;
    }

    public function setApiRoot(string $apiRoot): self
    {
        $this->apiRoot = $apiRoot;
        return $this;
    }

    public function getOAuthApiRoot(): string
    {
        return $this->oAuthApiRoot;
    }

    public function setOAuthApiRoot(string $oAuthApiRoot): self
    {
        $this->oAuthApiRoot = $oAuthApiRoot;
        return $this;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * Retrieve Access Token from LinkedIn if we have code provided.
     * If code is not provided, return current Access Token.
     *
     * @throws \LinkedIn\Exception
     */
    public function getAccessToken(string $code = ''): ?AccessToken
    {
        if (!empty($code)) {
            $uri = $this->buildUrl('accessToken', []);
            $guzzle = new GuzzleClient([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-li-format' => 'json',
                    'Connection' => 'Keep-Alive'
                ]
            ]);
            try {
                $response = $guzzle->post($uri, ['form_params' => [
                    'grant_type' => self::OAUTH2_GRANT_TYPE,
                    self::OAUTH2_RESPONSE_TYPE => $code,
                    'redirect_uri' => $this->getRedirectUrl(),
                    'client_id' => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                ]]);
            } catch (RequestException $exception) {
                throw Exception::fromRequestException($exception);
            }
            $this->setAccessToken(
                AccessToken::fromResponse($response)
            );
        }
        return $this->accessToken;
    }

    public static function responseToArray(ResponseInterface $response): array
    {
        return json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function setAccessToken(AccessToken|string $accessToken): self
    {
        if (is_string($accessToken)) {
            $accessToken = new AccessToken($accessToken);
        }
        $this->accessToken = $accessToken;
        return $this;
    }

    protected function getCurrentScheme(): string
    {
        $scheme = 'http';
        if (isset($_SERVER['HTTPS']) && "on" === $_SERVER["HTTPS"]) {
            $scheme = 'https';
        }
        return $scheme;
    }

    public function getCurrentUrl(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return $this->getCurrentScheme() . '://' . $host . $path;
    }

    public function getState(): string
    {
        $this->state ??= rtrim(
            base64_encode(uniqid('', true)),
            '='
        );
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Retrieve URL which will be used to send User to LinkedIn for authentication.
     *
     * @param Scope[] $scope Permissions that your application requires
     */
    public function getLoginUrl(
        array $scope = [Scope::READ_BASIC_PROFILE, Scope::READ_EMAIL_ADDRESS]
    ): string {
        $params = [
            'response_type' => self::OAUTH2_RESPONSE_TYPE,
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUrl(),
            'state' => $this->getState(),
            'scope' => implode(' ', array_map(fn(Scope $s) => $s->value, $scope)),
        ];
        return $this->buildUrl('authorization', $params);
    }

    public function getRedirectUrl(): string
    {
        if ($this->redirectUrl === null) {
            $this->setRedirectUrl($this->getCurrentUrl());
        }
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): self
    {
        $redirectUrl = filter_var($redirectUrl, FILTER_VALIDATE_URL);
        if (false === $redirectUrl) {
            throw new \InvalidArgumentException('The argument is not an URL');
        }
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    protected function buildUrl(string $endpoint, array $params): string
    {
        $url = $this->getOAuthApiRoot();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $authority = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $path .= trim($endpoint, '/');
        $fragment = '';
        return Uri::composeComponents(
            $scheme,
            $authority,
            $path,
            Query::build($params),
            $fragment
        );
    }

    /**
     * Perform API call to LinkedIn.
     *
     * @throws \LinkedIn\Exception
     */
    public function api(
        string $endpoint,
        array $params = [],
        Method $method = Method::GET,
    ): array {
        $headers = $this->getApiHeaders();
        $options = $this->prepareOptions($params, $method);
        Method::isMethodSupported($method);
        if ($this->isUsingTokenParam()) {
            $params['oauth2_access_token'] = $this->accessToken->getToken();
        } else {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken->getToken();
        }
        $guzzle = new GuzzleClient([
            'base_uri' => $this->getApiRoot(),
            'headers' => $headers,
        ]);
        if (!empty($params) && Method::GET === $method) {
            $endpoint .= '?' . Query::build($params);
        }
        try {
            $response = $guzzle->request($method->value, $endpoint, $options);
        } catch (RequestException $requestException) {
            throw Exception::fromRequestException($requestException);
        }
        return self::responseToArray($response);
    }

    /**
     * @throws \LinkedIn\Exception
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->api($endpoint, $params, Method::GET);
    }

    /**
     * @throws \LinkedIn\Exception
     */
    public function post(string $endpoint, array $params = []): array
    {
        return $this->api($endpoint, $params, Method::POST);
    }

    /**
     * @throws \LinkedIn\Exception
     */
    public function delete(string $endpoint, array $params = []): array
    {
        return $this->api($endpoint, $params, Method::DELETE);
    }

    /**
     * @throws \LinkedIn\Exception
     */
    public function upload(string $path): array
    {
        $headers = $this->getApiHeaders();
        unset($headers['Content-Type']);
        if (!$this->isUsingTokenParam()) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken->getToken();
        }
        $guzzle = new GuzzleClient([
            'base_uri' => $this->getApiRoot()
        ]);
        $fileinfo = pathinfo($path);
        $filename = preg_replace('/\W+/', '_', $fileinfo['filename']);
        if (isset($fileinfo['extension'])) {
            $filename .= '.' . $fileinfo['extension'];
        }
        $options = [
            'multipart' => [
                [
                    'name' => 'source',
                    'filename' => $filename,
                    'contents' => fopen($path, 'r')
                ]
            ],
            'headers' => $headers,
        ];
        try {
            $response = $guzzle->request(Method::POST->value, 'media/upload', $options);
        } catch (RequestException $requestException) {
            throw Exception::fromRequestException($requestException);
        }
        return self::responseToArray($response);
    }

    protected function prepareOptions(array $params, Method $method): array
    {
        $options = [];
        if ($method === Method::POST) {
            $options['body'] = json_encode($params, JSON_THROW_ON_ERROR);
        }
        return $options;
    }
}

<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Firebase\JWT\JWT;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;

class JWTService implements JWTServiceInterface
{
    /**
     * @var AppOptions
     */
    private $appOptions;

    public function __construct(AppOptions $appOptions)
    {
        $this->appOptions = $appOptions;
    }

    /**
     * Creates a new JSON web token por provided API key
     *
     * @param ApiKey $apiKey
     * @param int $lifetime
     * @return string
     */
    public function create(ApiKey $apiKey, $lifetime = self::DEFAULT_LIFETIME)
    {
        $currentTimestamp = time();

        return $this->encode([
            'iss' => $this->appOptions->__toString(),
            'iat' => $currentTimestamp,
            'exp' => $currentTimestamp + $lifetime,
            'sub' => 'auth',
            'key' => $apiKey->getId(), // The ID is opaque. Returning the key would be insecure
        ]);
    }

    /**
     * Refreshes a token and returns it with the new expiration
     *
     * @param string $jwt
     * @param int $lifetime
     * @return string
     * @throws AuthenticationException If the token has expired
     */
    public function refresh($jwt, $lifetime = self::DEFAULT_LIFETIME)
    {
        $payload = $this->getPayload($jwt);
        $payload['exp'] = time() + $lifetime;
        return $this->encode($payload);
    }

    /**
     * Verifies that certain JWT is valid
     *
     * @param string $jwt
     * @return bool
     */
    public function verify($jwt)
    {
        try {
            // If no exception is thrown while decoding the token, it is considered valid
            $this->decode($jwt);
            return true;
        } catch (\UnexpectedValueException $e) {
            return false;
        }
    }

    /**
     * Decodes certain token and returns the payload
     *
     * @param string $jwt
     * @return array
     * @throws AuthenticationException If the token has expired
     */
    public function getPayload($jwt)
    {
        try {
            return $this->decode($jwt);
        } catch (\UnexpectedValueException $e) {
            throw AuthenticationException::expiredJWT($e);
        }
    }

    /**
     * @param array $data
     * @return string
     */
    protected function encode(array $data)
    {
        return JWT::encode($data, $this->appOptions->getSecretKey(), self::DEFAULT_ENCRYPTION_ALG);
    }

    /**
     * @param $jwt
     * @return array
     */
    protected function decode($jwt)
    {
        return (array) JWT::decode($jwt, $this->appOptions->getSecretKey(), [self::DEFAULT_ENCRYPTION_ALG]);
    }
}

<?php
namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Entity\RestToken;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;

interface RestTokenServiceInterface
{
    /**
     * @param string $token
     * @return RestToken
     * @throws InvalidArgumentException
     */
    public function getByToken($token);

    /**
     * Creates and returns a new RestToken if username and password are correct
     * @param $username
     * @param $password
     * @return RestToken
     * @throws AuthenticationException
     */
    public function createToken($username, $password);

    /**
     * Updates the expiration of provided token, extending its life
     *
     * @param RestToken $token
     */
    public function updateExpiration(RestToken $token);
}

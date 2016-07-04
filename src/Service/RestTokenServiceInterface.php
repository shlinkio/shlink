<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\RestToken;
use Acelaya\UrlShortener\Exception\AuthenticationException;
use Acelaya\UrlShortener\Exception\InvalidArgumentException;

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
}

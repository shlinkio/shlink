<?php
namespace Shlinkio\Shlink\Rest\Service;

use Acelaya\UrlShortener\Entity\RestToken;
use Acelaya\UrlShortener\Exception\InvalidArgumentException;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;

class RestTokenService implements RestTokenServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var array
     */
    private $restConfig;

    /**
     * ShortUrlService constructor.
     * @param EntityManagerInterface $em
     *
     * @param array $restConfig
     * @Inject({"em", "config.rest"})
     */
    public function __construct(EntityManagerInterface $em, array $restConfig)
    {
        $this->em = $em;
        $this->restConfig = $restConfig;
    }

    /**
     * @param string $token
     * @return RestToken
     * @throws InvalidArgumentException
     */
    public function getByToken($token)
    {
        $restToken = $this->em->getRepository(RestToken::class)->findOneBy([
            'token' => $token,
        ]);
        if (! isset($restToken)) {
            throw new InvalidArgumentException(sprintf('RestToken not found for token "%s"', $token));
        }

        return $restToken;
    }

    /**
     * Creates and returns a new RestToken if username and password are correct
     * @param $username
     * @param $password
     * @return RestToken
     * @throws AuthenticationException
     */
    public function createToken($username, $password)
    {
        $this->processCredentials($username, $password);

        $restToken = new RestToken();
        $this->em->persist($restToken);
        $this->em->flush();

        return $restToken;
    }

    /**
     * @param string $username
     * @param string $password
     */
    protected function processCredentials($username, $password)
    {
        $configUsername = strtolower(trim($this->restConfig['username']));
        $providedUsername = strtolower(trim($username));
        $configPassword = trim($this->restConfig['password']);
        $providedPassword = trim($password);

        if ($configUsername === $providedUsername && $configPassword === $providedPassword) {
            return;
        }

        // If credentials are not correct, throw exception
        throw AuthenticationException::fromCredentials($providedUsername, $providedPassword);
    }

    /**
     * Updates the expiration of provided token, extending its life
     *
     * @param RestToken $token
     */
    public function updateExpiration(RestToken $token)
    {
        $token->updateExpiration();
        $this->em->flush();
    }
}

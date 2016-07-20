<?php
namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Zend\Diactoros\Uri;

class UrlShortenerTest extends TestCase
{
    /**
     * @var UrlShortener
     */
    protected $urlShortener;
    /**
     * @var ObjectProphecy
     */
    protected $em;
    /**
     * @var ObjectProphecy
     */
    protected $httpClient;

    public function setUp()
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);

        $this->em = $this->prophesize(EntityManagerInterface::class);
        $conn = $this->prophesize(Connection::class);
        $conn->isTransactionActive()->willReturn(false);
        $this->em->getConnection()->willReturn($conn->reveal());
        $this->em->flush()->willReturn(null);
        $this->em->commit()->willReturn(null);
        $this->em->beginTransaction()->willReturn(null);
        $this->em->persist(Argument::any())->will(function ($arguments) {
            /** @var ShortUrl $shortUrl */
            $shortUrl = $arguments[0];
            $shortUrl->setId(10);
        });
        $repo = $this->prophesize(ObjectRepository::class);
        $repo->findOneBy(Argument::any())->willReturn(null);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->urlShortener = new UrlShortener($this->httpClient->reveal(), $this->em->reveal());
    }

    /**
     * @test
     */
    public function urlIsProperlyShortened()
    {
        // 10 -> 12C1c
        $shortCode = $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
        $this->assertEquals('12C1c', $shortCode);
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\RuntimeException
     */
    public function exceptionIsThrownWhenOrmThrowsException()
    {
        $conn = $this->prophesize(Connection::class);
        $conn->isTransactionActive()->willReturn(true);
        $this->em->getConnection()->willReturn($conn->reveal());
        $this->em->rollback()->shouldBeCalledTimes(1);
        $this->em->close()->shouldBeCalledTimes(1);

        $this->em->flush()->willThrow(new ORMException());
        $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Core\Exception\InvalidUrlException
     */
    public function exceptionIsThrownWhenUrlDoesNotExist()
    {
        $this->httpClient->request(Argument::cetera())->willThrow(
            new ClientException('', $this->prophesize(Request::class)->reveal())
        );
        $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
    }

    /**
     * @test
     */
    public function whenShortUrlExistsItsShortcodeIsReturned()
    {
        $shortUrl = new ShortUrl();
        $shortUrl->setShortCode('expected_shortcode');
        $repo = $this->prophesize(ObjectRepository::class);
        $repo->findOneBy(Argument::any())->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $shortCode = $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
        $this->assertEquals($shortUrl->getShortCode(), $shortCode);
    }

    /**
     * @test
     */
    public function shortCodeIsProperlyParsed()
    {
        // 12C1c -> 10
        $shortUrl = new ShortUrl();
        $shortUrl->setShortCode('12C1c')
                 ->setOriginalUrl('expected_url');

        $repo = $this->prophesize(ObjectRepository::class);
        $repo->findOneBy(['shortCode' => '12C1c'])->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $url = $this->urlShortener->shortCodeToUrl('12C1c');
        $this->assertEquals($shortUrl->getOriginalUrl(), $url);
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Core\Exception\InvalidShortCodeException
     */
    public function invalidCharSetThrowsException()
    {
        $this->urlShortener->shortCodeToUrl('&/(');
    }
}

<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
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
    /**
     * @var Cache
     */
    protected $cache;

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

        $this->cache = new ArrayCache();

        $this->urlShortener = new UrlShortener($this->httpClient->reveal(), $this->em->reveal(), $this->cache);
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
        $shortCode = '12C1c';
        $shortUrl = new ShortUrl();
        $shortUrl->setShortCode($shortCode)
                 ->setOriginalUrl('expected_url');

        $repo = $this->prophesize(ObjectRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->assertFalse($this->cache->contains($shortCode . '_longUrl'));
        $url = $this->urlShortener->shortCodeToUrl($shortCode);
        $this->assertEquals($shortUrl->getOriginalUrl(), $url);
        $this->assertTrue($this->cache->contains($shortCode . '_longUrl'));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Core\Exception\InvalidShortCodeException
     */
    public function invalidCharSetThrowsException()
    {
        $this->urlShortener->shortCodeToUrl('&/(');
    }

    /**
     * @test
     */
    public function cachedShortCodeDoesNotHitDatabase()
    {
        $shortCode = '12C1c';
        $expectedUrl = 'expected_url';
        $this->cache->save($shortCode . '_longUrl', $expectedUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn(null)->shouldBeCalledTimes(0);

        $url = $this->urlShortener->shortCodeToUrl($shortCode);
        $this->assertEquals($expectedUrl, $url);
    }
}

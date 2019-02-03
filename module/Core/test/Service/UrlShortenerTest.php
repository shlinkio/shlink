<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Zend\Diactoros\Uri;

class UrlShortenerTest extends TestCase
{
    /** @var UrlShortener */
    private $urlShortener;
    /** @var ObjectProphecy */
    private $em;
    /** @var ObjectProphecy */
    private $httpClient;

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
            $shortUrl->setId('10');
        });
        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->count(Argument::any())->willReturn(0);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->setUrlShortener(false);
    }

    public function setUrlShortener(bool $urlValidationEnabled)
    {
        $this->urlShortener = new UrlShortener(
            $this->httpClient->reveal(),
            $this->em->reveal(),
            new UrlShortenerOptions(['validate_url' => $urlValidationEnabled])
        );
    }

    /**
     * @test
     */
    public function urlIsProperlyShortened()
    {
        // 10 -> 12C1c
        $shortUrl = $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
        $this->assertEquals('12C1c', $shortUrl->getShortCode());
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Core\Exception\RuntimeException
     */
    public function exceptionIsThrownWhenOrmThrowsException()
    {
        $conn = $this->prophesize(Connection::class);
        $conn->isTransactionActive()->willReturn(true);
        $this->em->getConnection()->willReturn($conn->reveal());
        $this->em->rollback()->shouldBeCalledOnce();
        $this->em->close()->shouldBeCalledOnce();

        $this->em->flush()->willThrow(new ORMException());
        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Core\Exception\InvalidUrlException
     */
    public function exceptionIsThrownWhenUrlDoesNotExist()
    {
        $this->setUrlShortener(true);

        $this->httpClient->request(Argument::cetera())->willThrow(
            new ClientException('', $this->prophesize(Request::class)->reveal())
        );
        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createEmpty()
        );
    }

    /**
     * @test
     */
    public function exceptionIsThrownWhenNonUniqueSlugIsProvided()
    {
        $repo = $this->prophesize(ShortUrlRepository::class);
        $countBySlug = $repo->count(['shortCode' => 'custom-slug'])->willReturn(1);
        $repo->findOneBy(Argument::cetera())->willReturn(null);
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $countBySlug->shouldBeCalledOnce();
        $getRepo->shouldBeCalled();
        $this->expectException(NonUniqueSlugException::class);

        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            ShortUrlMeta::createFromRawData(['customSlug' => 'custom-slug'])
        );
    }

    /**
     * @test
     */
    public function shortCodeIsProperlyParsed()
    {
        // 12C1c -> 10
        $shortCode = '12C1c';
        $shortUrl = new ShortUrl('expected_url');
        $shortUrl->setShortCode($shortCode);

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $repo->findOneByShortCode($shortCode)->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $url = $this->urlShortener->shortCodeToUrl($shortCode);
        $this->assertSame($shortUrl, $url);
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

<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Cocur\Slugify\SlugifyInterface;
use Doctrine\Common\Persistence\ObjectRepository;
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
    /** @var ObjectProphecy */
    private $slugger;

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
        $repo = $this->prophesize(ObjectRepository::class);
        $repo->findOneBy(Argument::any())->willReturn(null);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $this->slugger = $this->prophesize(SlugifyInterface::class);

        $this->setUrlShortener(false);
    }

    /**
     * @param bool $urlValidationEnabled
     */
    public function setUrlShortener($urlValidationEnabled)
    {
        $this->urlShortener = new UrlShortener(
            $this->httpClient->reveal(),
            $this->em->reveal(),
            $urlValidationEnabled,
            UrlShortener::DEFAULT_CHARS,
            $this->slugger->reveal()
        );
    }

    /**
     * @test
     */
    public function urlIsProperlyShortened()
    {
        // 10 -> 12C1c
        $shortUrl = $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
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
        $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
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
        $this->urlShortener->urlToShortCode(new Uri('http://foobar.com/12345/hello?foo=bar'));
    }

    /**
     * @test
     */
    public function whenCustomSlugIsProvidedItIsUsed()
    {
        /** @var MethodProphecy $slugify */
        $slugify = $this->slugger->slugify('custom-slug')->willReturnArgument();

        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            null,
            null,
            'custom-slug'
        );

        $slugify->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function exceptionIsThrownWhenNonUniqueSlugIsProvided()
    {
        /** @var MethodProphecy $slugify */
        $slugify = $this->slugger->slugify('custom-slug')->willReturnArgument();

        $repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        /** @var MethodProphecy $findBySlug */
        $findBySlug = $repo->findOneBy(['shortCode' => 'custom-slug'])->willReturn(new ShortUrl(''));
        $repo->findOneBy(Argument::cetera())->willReturn(null);
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $slugify->shouldBeCalledOnce();
        $findBySlug->shouldBeCalledOnce();
        $getRepo->shouldBeCalled();
        $this->expectException(NonUniqueSlugException::class);

        $this->urlShortener->urlToShortCode(
            new Uri('http://foobar.com/12345/hello?foo=bar'),
            [],
            null,
            null,
            'custom-slug'
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

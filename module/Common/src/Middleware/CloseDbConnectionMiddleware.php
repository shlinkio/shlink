<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Middleware;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class CloseDbConnectionMiddleware implements MiddlewareInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            // FIXME Mega ugly hack to avoid a closed EntityManager to make shlink fail forever on swoole contexts
            //       Should be fixed with request-shared EntityManagers, which is not supported by the ServiceManager
            if (! $this->em->isOpen()) {
                (function () {
                    $this->closed = false;
                })->bindTo($this->em, EntityManager::class)();
            }

            throw $e;
        } finally {
            $this->em->getConnection()->close();
            $this->em->clear();
        }
    }
}

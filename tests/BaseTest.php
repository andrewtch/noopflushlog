<?php

namespace Noop\FlushLog\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Noop\FlushLog\Doctrine\ORM\FlushLogSubscriber;
use Noop\FlushLog\Tests\Entity\LogEntry;
use Noop\FlushLog\Tests\Entity\Product;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    protected $connectionName = 'mysql';

    protected $configuration = [
        'entities' => [
            Product::class => []
        ]
    ];

    /**
     * @return LogEntry
     */
    protected function getLastLogEntry()
    {
        return $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(LogEntry::class, 'l')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    protected function assertEntityCount($expectedCount, $class)
    {
        $this->assertEquals(
            $expectedCount,
            $this->entityManager->createQueryBuilder()
                ->select('COUNT(o)')
                ->from($class, 'o')
                ->getQuery()
                ->getSingleScalarResult()
        );
    }

    protected function assertLogCount($expectedCount)
    {
        $this->assertEquals(
            $expectedCount,
            $this->entityManager->createQueryBuilder()
                ->select('COUNT(l)')
                ->from(LogEntry::class, 'l')
                ->getQuery()
                ->getSingleScalarResult()
        );
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->close();
        $this->entityManager->close();
    }

    protected function setUp(): void
    {
        // aux classes
        $eventManager = new EventManager();

        // configuration
        $configuration = Setup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/Entity'],
            true,
            null,
            null,
            false
        );

        // connection
        $key = strtoupper($this->connectionName) . '_URL';

        if (!isset($_SERVER[$key])) {
            throw new \Exception(sprintf('No connection url for "%s". Please create php/server variable in your phpunit.xml with name "%s" to connect, set "static::$connectionName" in your class', $this->connectionName, $key));
        }

        $url = $_SERVER[strtoupper($this->connectionName) . '_URL'];

        $connection = DriverManager::getConnection(
            ['url' => $url],
            $configuration,
            $eventManager
        );

        // finally, em
        $this->entityManager = EntityManager::create($connection, $configuration);

        // configure and add subscriber
        $subscriber = new FlushLogSubscriber();

        $subscriber->setConfiguration($this->configuration);

        $eventManager->addEventSubscriber($subscriber);

        // create schema
        $schemaTool = new SchemaTool($this->entityManager);

        $metas = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metas);

        $schemaTool->createSchema($metas);
    }
}

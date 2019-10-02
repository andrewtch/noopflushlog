<?php

namespace Noop\FlushLog\Tests\ORM;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Type;
use Noop\FlushLog\Tests\BaseTest;
use Noop\FlushLog\Tests\Entity\LogEntry;
use Noop\FlushLog\Tests\Entity\Product;
use Noop\FlushLog\Tests\Entity\SkippedProduct;

class FlushLogSubscriberTest extends BaseTest
{
    public function testMultiColumnKeys()
    {
        $this->markTestIncomplete();
    }

    public function testPersistsUserCorrectly()
    {
        $this->markTestIncomplete();
    }

    public function testTimestamps()
    {
        $this->markTestIncomplete();
    }

    public function testCanDeserializeCorrectly()
    {
        $this->markTestIncomplete();
    }

    public function testQueryWithDefaultDriver()
    {
        $this->markTestIncomplete();
    }

    public function testRelationAdditionAndDeletion()
    {
        $this->markTestIncomplete();
    }

    public function testAffectedOnInsertsUpdatesAndDeletes()
    {
        $this->markTestIncomplete();
    }

    public function testRemovals()
    {
        $this->markTestIncomplete();
    }

    public function testChangeSets()
    {
        $this->markTestIncomplete();
    }

    public function testSkippedFields()
    {
        $this->markTestIncomplete();
    }

    public function testEmptyFlushIfAllEntitiesAreFiltered()
    {
        $this->markTestIncomplete();
    }

    public function testEmptyFlush()
    {
        $this->markTestIncomplete();
    }

    public function testUpdates()
    {
        $product1 = (new Product())
            ->setName('name1');

        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $this->assertLogCount(1);

        $product1->setName('other name');

        $this->entityManager->flush();

        $this->assertLogCount(2);

        $entry = $this->getLastLogEntry();

        $this->assertEquals([$product1->getId()], $entry->getLogData()['e'][Product::class]);
        $this->assertEquals(['name1', 'other name'], $entry->getLogData()['cs'][Product::class][$product1->getId()]['name']);
    }

    public function testSkippedEntities()
    {
        $product1 = (new Product())
            ->setName('name1');

        $product2 = (new SkippedProduct())
            ->setName('name2');

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);

        $this->entityManager->flush();

        $this->assertEntityCount(1, Product::class);
        $this->assertEntityCount(1, SkippedProduct::class);

        $this->assertLogCount(1);

        $entry = $this->getLastLogEntry();

        $this->assertEquals([Product::class], array_keys($entry->getLogData()['i']));
    }

    public function testMultipleLog()
    {
        $product1 = (new Product())
            ->setName('name1');

        $product2 = (new Product())
            ->setName('name2');

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);

        $this->entityManager->flush();

        $this->assertLogCount(1);

        $entry = $this->getLastLogEntry();

        $this->assertEquals([1, 2], $entry->getLogData()['i'][Product::class]);

        // changesets
        $this->assertEquals(['name' => [null, 'name1']], $entry->getLogData()['cs'][Product::class][1]);
        $this->assertEquals(['name' => [null, 'name2']], $entry->getLogData()['cs'][Product::class][2]);

        $this->assertCount(2, $entry->getLogData()['cs'][Product::class]);

        // affected
        $this->assertEquals([1, 2], $entry->getLogData()['e'][Product::class]);
    }

    public function testBasicLog()
    {
        $product = (new Product())
            ->setName('name');

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertLogCount(1);
    }
}

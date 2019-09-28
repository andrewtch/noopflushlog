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
    public function testPersistsUserCorrectly() {}

    public function testTimestamps() {}

    public function testCanDeserializeCorrectly() {}

    public function testQueryWithDefaultDriver() {}

    public function testRelationAdditionAndDeletion() {}

    public function testAffectedOnInsertsUpdatesAndDeletes() {}

    public function testRemovals() {}

    public function testChangeSets() {}

    public function testSkippedFields() {}

    public function testUpdates() {}

    public function testSkippedEntities()
    {
        $product1 = (new Product())
            ->setName('name1');

        $product2 = (new SkippedProduct())
            ->setName('name2');

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);

        $this->entityManager->flush();

        $this->assertCount(1, $this->entityManager->getRepository(Product::class)->findAll());
        $this->assertCount(1, $this->entityManager->getRepository(SkippedProduct::class)->findAll());

        $entries = $this->entityManager->getRepository(LogEntry::class)->findAll();

        $this->assertCount(1, $entries);

        $entry = $entries[0];

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

        $entries = $this->entityManager->getRepository(LogEntry::class)->findAll();

        $this->assertCount(1, $entries);

        $entry = $entries[0];

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

        $this->assertCount(1, $this->entityManager->getRepository(LogEntry::class)->findAll());
    }
}

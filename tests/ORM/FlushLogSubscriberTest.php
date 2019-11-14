<?php

namespace Noop\FlushLog\Tests\ORM;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Type;
use Noop\FlushLog\Tests\BaseTest;
use Noop\FlushLog\Tests\Entity\LogEntry;
use Noop\FlushLog\Tests\Entity\PartialProduct;
use Noop\FlushLog\Tests\Entity\Product;
use Noop\FlushLog\Tests\Entity\SkippedProduct;
use Noop\FlushLog\User\TestUserResolver;

class FlushLogSubscriberTest extends BaseTest
{
    public function testMultiColumnKeys()
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

    public function testAffectedManyToManyOnInsertsUpdatesAndDeletes()
    {
        // ahem?
        $this->markTestIncomplete();
    }

    public function testAffectedOneToManyOnInsertsUpdatesAndDeletes()
    {
        // deletes should be really tricky as after flush we don't have an Id anymore
        // general testing scenario:
        // create product, persist
        // create a translation, set product, should affect product
        // update translation, should affect product
        // remove translation, should affect product
        $this->markTestIncomplete();
    }

    public function testPersistsUserCorrectly()
    {
        $product = (new Product())
            ->setName('name');

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $entry = $this->getLastLogEntry();

        $this->assertNull($entry->getUserId());
        $this->assertNull($entry->getUserName());

        $this->subscriber->setUserResolver(new TestUserResolver());

        $product = (new Product())
            ->setName('name');

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $entry = $this->getLastLogEntry();

        $this->assertEquals(1, $entry->getUserId());
        $this->assertEquals('test user', $entry->getUserName());
    }

    public function testRemovals()
    {
        $product = (new Product())
            ->setName('name');

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $id = $product->getId();

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->assertLogCount(2);

        $entry = $this->getLastLogEntry();

        $this->assertEquals([Product::class], array_keys($entry->getLogData()['e']));
        $this->assertEquals([$id], $entry->getLogData()['e'][Product::class]);
        $this->assertEquals([$id], $entry->getLogData()['r'][Product::class]);
    }

    public function testAllSkippedFieldsSkipEntity()
    {
        $product = (new Product())
            ->setName('name');

        $partial = (new PartialProduct())
            ->setVisibleName('visible')
            ->setShadowName('shadow');

        $this->entityManager->persist($product);
        $this->entityManager->persist($partial);

        $this->entityManager->flush();;

        $this->assertLogCount(1);

        $product->setName('new name');
        $partial->setShadowName('new shadow');

        $this->entityManager->flush();

        $this->assertLogCount(2);

        $entry = $this->getLastLogEntry();

        $this->assertEquals([Product::class], array_keys($entry->getLogData()['e']));
    }

    public function testSkippedFields()
    {
        $product1 = (new PartialProduct())
            ->setVisibleName('visible')
            ->setShadowName('shadow');

        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $entry = $this->getLastLogEntry();
        $this->assertEquals(['visibleName' => [null, 'visible']], $entry->getLogData()['cs'][PartialProduct::class][$product1->getId()]);

        // udpates
        $product1->setShadowName('new shadow');
        $this->entityManager->flush();

        $this->entityManager->refresh($product1);
        $this->assertLogCount(1);
        $this->assertEquals('new shadow', $product1->getShadowName());

        $product1->setVisibleName('new visible');
        $this->entityManager->flush();
        $this->assertLogCount(2);
    }

    public function testEmptyFlushIfAllEntitiesAreFiltered()
    {
        $product1 = (new SkippedProduct())
            ->setName('skipped');

        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $this->assertEntityCount(1, SkippedProduct::class);
        $this->assertLogCount(0);
    }

    public function testEmptyFlush()
    {
        $product1 = (new Product())
            ->setName('name1');

        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $this->assertLogCount(1);

        $this->entityManager->flush();
        $this->assertLogCount(1);
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
        $this->assertEquals([$product1->getId()], $entry->getLogData()['u'][Product::class]);
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

<?php

namespace Noop\FlushLog\Doctrine\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Noop\FlushLog\Doctrine\Entity\BaseLogEntry;

class FlushLogSubscriber implements EventSubscriber
{
    protected $data = [];

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postFlush
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $hash => $entity) {
            // queue for resolution
            $this->data['_i'][get_class($entity)][] = $hash;

            // queue cs
            $this->data['_cs'][get_class($entity)][$hash] = $uow->getEntityChangeSet($entity);

            // hash
            $this->data['_hashmap'][$hash] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // post-process data

        // resolve hashmap
        foreach ($this->data['_hashmap'] as $hash => $entity) {
            $this->data['_hashmap'][$hash] = $this->getMergedIdentifier($uow, $entity);
        }

        // resolve inserts
        foreach ($this->data['_i'] as $class => $hashes) {
            foreach ($hashes as $hash) {
                $this->data['i'][$class][] = $this->data['_hashmap'][$hash];

                // add to affected entities
                $this->data['e'][$class][] = $this->data['_hashmap'][$hash];
            }
        }
        unset($this->data['_i']);

        // resolve changesets
        foreach ($this->data['_cs'] as $class => $changesets) {
            foreach ($changesets as $hash => $changeset) {
                $this->data['cs'][$class][$this->data['_hashmap'][$hash]] = $changeset;
            }
        }
        unset($this->data['_cs']);

        // we don't need hashmap anymore
        unset($this->data['_hashmap']);

        // post-processing end

        // persist
        $tableConfig = $this->resolveTableConfig($em);

        $em->getConnection()->insert($tableConfig['name'], [
            $tableConfig['log_data_name'] => json_encode($this->data),
        ]);

        $this->data = [];
    }

    protected function getMergedIdentifier(UnitOfWork $uow, object $entity)
    {
        $identifier = $uow->getEntityIdentifier($entity);

        if (count($identifier) === 1) {
            return array_values($identifier)[0];
        }

        return implode('#', $identifier);
    }

    protected function resolveTableConfig(EntityManagerInterface $entityManager)
    {
        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        $tableConfig = [];

        foreach ($metadatas as $metadata) {
            /** @var $metadata ClassMetadata */

            $refClass = $metadata->getReflectionClass();

            if (
                $refClass->getParentClass() &&
                $refClass->getParentClass()->getName() === BaseLogEntry::class
            ) {
                $tableConfig['name'] = $metadata->getTableName();
                $tableConfig['log_data_name'] = $metadata->getColumnName('logData');

                break;
            }
        }

        if (!$tableConfig) {
            throw new \Exception(
                sprintf('Using NoopFlushLog requires you to extend "%s" entity to support correct database structure generation, but no entity extending it has been found.', BaseLogEntry::class)
            );
        }

        return $tableConfig;
    }
}

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
use Noop\FlushLog\User\UserResolverInterface;

class FlushLogSubscriber implements EventSubscriber
{
    protected const EMPTY_LOG = [
        '_hashmap' => [],
        '_i' => [],
        '_cs' => [],
        'e' => [],
        'i' => [],
        'u' => [],
        'r' => [],
    ];

    protected $log = self::EMPTY_LOG;

    protected $configuration;

    /**
     * @var UserResolverInterface
     */
    protected $userResolver;

    /**
     * @param UserResolverInterface $userResolver
     */
    public function setUserResolver(UserResolverInterface $userResolver)
    {
        $this->userResolver = $userResolver;
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

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

        // process insertions
        foreach ($uow->getScheduledEntityInsertions() as $hash => $entity) {
            if (!$this->isSupportedEntity($entity)) {
                continue;
            }

            $class = get_class($entity);

            // queue cs
            $changeset = $this->getEntityChangeSet($uow, $entity);

            if (!$changeset) {
                continue;
            }

            $this->log['_cs'][$class][$hash] = $changeset;

            // queue for resolution
            $this->log['_i'][$class][] = $hash;

            // hash
            $this->log['_hashmap'][$hash] = $entity;
        }

        // process updates
        foreach ($uow->getScheduledEntityUpdates() as $hash => $entity) {
            if (!$this->isSupportedEntity($entity)) {
                continue;
            }

            $class = get_class($entity);
            $id = $this->getMergedIdentifier($uow, $entity);

            // add cs
            $changeset = $this->getEntityChangeset($uow, $entity);

            if (!$changeset) {
                continue;
            }

            $this->log['cs'][$class][$id] = $changeset;

            // add to updated
            $this->log['u'][$class][] = $id;
            
            // add to affected
            $this->log['e'][$class][] = $id;
        }

         // process removals
        foreach ($uow->getScheduledEntityDeletions() as $hash => $entity) {
            if (!$this->isSupportedEntity($entity)) {
                continue;
            }

            $class = get_class($entity);
            $id = $this->getMergedIdentifier($uow, $entity);

            // add to removed
            $this->log['r'][$class][] = $id;

            // add to affected
            $this->log['e'][$class][] = $id;
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // post-process data

        // resolve hashmap
        foreach ($this->log['_hashmap'] as $hash => $entity) {
            $this->log['_hashmap'][$hash] = $this->getMergedIdentifier($uow, $entity);
        }

        // resolve inserts
        foreach ($this->log['_i'] as $class => $hashes) {
            foreach ($hashes as $hash) {
                $this->log['i'][$class][] = $this->log['_hashmap'][$hash];

                // add to affected entities
                $this->log['e'][$class][] = $this->log['_hashmap'][$hash];
            }
        }
        unset($this->log['_i']);

        // resolve changesets
        foreach ($this->log['_cs'] as $class => $changesets) {
            foreach ($changesets as $hash => $changeset) {
                $this->log['cs'][$class][$this->log['_hashmap'][$hash]] = $changeset;
            }
        }
        unset($this->log['_cs']);

        // we don't need hashmap anymore
        unset($this->log['_hashmap']);

        // post-processing end

        // persist
        if (count($this->log['e'])) {
            $tableConfig = $this->resolveTableConfig($em);

            $insert = [
                $tableConfig['log_data_name'] => json_encode($this->log),
                $tableConfig['created_at_name'] => (new \DateTime())->format('Y-m-d H:i:s'),
            ];

            if ($this->userResolver) {
                $insert[$tableConfig['user_id_name']] = $this->userResolver->resolveUserId();
                $insert[$tableConfig['username_name']] = $this->userResolver->resolveUsername();
            }

            $em->getConnection()->insert($tableConfig['name'], $insert);
        }

        $this->log = self::EMPTY_LOG;
    }

    protected function getEntityChangeset(UnitOfWork $unitOfWork, object $entity)
    {
        if (!$this->isSupportedEntity($entity)) {
            return null;
        }

        $changeset = $unitOfWork->getEntityChangeSet($entity);

        $fields = $this->configuration['entities'][get_class($entity)]['fields'] ?? [];

        if (!$fields) {
            return $changeset;
        }

        $changeset = array_intersect_key($changeset, array_flip($fields));

        if (!$changeset) {
            return null;
        }

        return $changeset;
    }

    protected function isSupportedEntity(object $entity)
    {
        return array_key_exists(get_class($entity), $this->configuration['entities']);
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
                $tableConfig['user_id_name'] = $metadata->getColumnName('userId');
                $tableConfig['username_name'] = $metadata->getColumnName('username');
                $tableConfig['created_at_name'] = $metadata->getColumnName('createdAt');

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

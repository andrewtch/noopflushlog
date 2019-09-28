# What's this?

This is add-on to [Doctrine2](https://github.com/doctrine/orm) the implements `EntityManager::flush()` logging and history. It can serve as a replacement to both [EntityAudit](https://github.com/simplethings/EntityAuditBundle) and Atlantic18 [Loggable / Blameable](https://github.com/Atlantic18/DoctrineExtensions) extensions.

# Why?

Because relations. Most of the libraries log single entity changes, but a change in `ProductTranslation` is actually a change in `Product` from application perspective. Collection updates also matter (who attached that image to this product?).

Therefore, this library logs _flushes_, consisting of many changes (insertions, removals, deletions, AND collection changes). Also, it tries to be a low-level library, that does not create new entities on each flush, manipulating DBAL directly.

# How?

Basically, this library serializes `UnitOfWork` into JSON and relies on DB native functions to browse and search the logs. This seems like as the only way to log relation changes (who added tag "Cancelled" to order #216?), and provides a bit of flexibility.

# It is stable? What is supported?

_Stability_: in development. Please join!

_Database support_: MySQL 5.6+

_ORM_: 2.5+

_PHP_: 7.1+ 

# Features

* filtering entities
* filtering entity fields (entity is tracked only if one of the fields is updated)
* collection change tracking
* "Affected entities" concept. `ProductTranslation` change affects `Product` change, event if the `Product` entity wasn't changed at all.

# FAQ

**JSON search is very slow. Can you add indexes?**

No and this is a bad approach. If your application relies on entity changes and queries the flushlog frequently, a viable approach would be to use external search system that can work with JSON - like Elasticsearch, Sphinxsearch, or Lucene.

**Can you support versioning of objects?**

Yes, I could (creating a version for each entity, linking to particular flushlog), but it will take some time. You can submit a pull request or, alternatively, sponsor the development.
 

# And Symfony?

There is a bundle in development.

# Ok, how do I install it?

1. Initialize the `FlushLogSubscriber`:
```php
$subscriber = new Noop\FlushlLog\Doctrine\ORM\FlushLogSubscriber();
```

2. Set the configuration (see example below):
```php
$subscriber->setConfiguration(...);
```

3. Add the subscriber to Doctrine's `EventManager`:
```php
$eventManager->addEventSubscriber($subscriber);
```

4. Extend `Noop\FlushLog\Doctrine\Entity\BaseLogEntry` to update your schema accordingly (it's a `MappedSuperclass`, so your entity should be beautifully empty):
```php
<?php

namespace App\Tests\Entity;

use Noop\FlushLog\Doctrine\Entity\BaseLogEntry;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class LogEntry extends BaseLogEntry
{

}
```

# Internal structure of JSON field

Example JSON:

```json
{
    "e": {
        "App\\Entity\\EntityName": [6, 18, "123;31"] 
    },
    "i": {
        "App\\Entity\\EntityName": [6, 18, "123;31"] 
    },
    "cs": {
        "App\\Entity\\EntityName": {
            "6": {
                "field": [null, "asdf"],
                "field1": [null, 6]
            }
        }
    }
}
```

* `"e"` contains class -> id map of all affected entities (either directly or indirectly, by configuration or by collection change)
* `"i"` contains class -> id maps of all inserted entities
* `"cs"` contains class -> id -> field map of all changesets. You can set tracked fields per-entity in the configuration. By default, if entity is tracked, all fields are tracked

# Configuration

Example configuration (set in `FlushLogSubscriber::setConfiguration`): 

```php
[
    // lists tracked entities. All other entities are ignored
    'entities' => [
        'App\Entity\EntityName' => [
        ]
    ]
]
```

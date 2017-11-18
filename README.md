# database

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

PHP library and ORM to handle DB connection, apply C.R.U.D. operations.

## Install

Via Composer

``` bash
$ composer require thephpleague/database
```

## Usage

``` php
$config = [
    'host'      => 'localhost',
    'port'      => 3306,
    'database'  => 'master_db',
    'username'  => 'root',
    'password'  => '',
];

$connection = new League\Database\ConnectionManager('core', $config);
```

### `BulkSql` usage

Bulk SQL classes could be useful in scripts, when you need to insert big amount of records.

**Example 1:**

<details>
    <summary>`BulkInsert` usage</summary>
    
``` php
use League\Database\BulkSql\BulkInsert;

$db = $connection->getMasterConnection();

$bulkInsert = new BulkInsert($db, 'users');
$bulkInsert
    ->setItemsPerQuery(50)
    ->useIgnore()
    ->disableIndexes();

try {
    $db->beginTransaction();
    
    foreach ($users as $user) {
        $bulkInsert->add($user);
    }
    
    $bulkInsert->finish();
    $affectedCount = $bulkInsert->getAffectedCount();
    
    $db->commit();
} catch (\PDOException $e) {
    $db->rollBack();
}
```
</details>

**Example 2:**

<details>
    <summary>`BulkReplace` and `BulkDelete` usage (could be useful with feeds)</summary>
    
``` php
use League\Database\BulkSql\BulkReplace;
use League\Database\BulkSql\BulkDelete;

$db = $connection->getMasterConnection();

$bulkReplace = new BulkReplace($db, 'offers');
$bulkReplace->setItemsPerQuery(500);
$bulkDelete = new BulkDelete($db, 'offers');
$bulkDelete->setItemsPerQuery(1000);

try {
    $db->beginTransaction();
    
    foreach ($offers as $offer) {
        $flag = $offer['deltaStatus'] ?? null;
        
        switch ($delta) {
            case 'REMOVE':
                $bulkDelete->add(['id' => $data['id']]);
                break;
            case 'ADD':
                $bulkReplace->add($data);
                break;
            default:
                $logger->notice("Unsupported delta flag \"{$flag}\"";
        }
    }

    $bulkReplace->finish();
    $bulkDelete->finish();
    
    $db->commit();
    
    $insertsCount = $bulkReplace->getInsertedCount();
    $updatesCount = $bulkReplace->getReplacedCount();
    $deletesCount = $bulkDelete->getAffectedCount();
} catch (\PDOException $e) {
    $db->rollBack();
}
```
</details>

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email sergey.podgornyy@yahoo.de instead of using the issue tracker.

## Credits

- [Sergey Podgornyy][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/thephpleague/database.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/thephpleague/database/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/thephpleague/database.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/thephpleague/database.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/thephpleague/database.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/thephpleague/database
[link-travis]: https://travis-ci.org/thephpleague/database
[link-scrutinizer]: https://scrutinizer-ci.com/g/thephpleague/database/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/thephpleague/database
[link-downloads]: https://packagist.org/packages/thephpleague/database
[link-author]: https://github.com/SergeyPodgornyy
[link-contributors]: ../../contributors

# Changelog

All notable changes to `database` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## 2017-11-22

### Deprecated
- Usage of `Utils/array_functions.php` and will be completely deleted in next MINOR release

## 2017-11-18

### Added
- Optional `options` from `$config` while initializing `Driver\Engine`. Have more priority, then default options from `Driver\Engine`
- Array functions in `League\Database\Utils` namespace, such as:
  ⋅⋅⋅ `array_is_assoc`
  ⋅⋅⋅ `array_depth`
  ⋅⋅⋅ `array_flatten`
  ⋅⋅⋅ `array_flatten_assoc`
  ⋅⋅⋅`is_array_of_type`
- `BulkSql` feature
<details>
    <summary>Read more...</summary>
    
    * `BulkInsert`, `BulkReplace` and `BulkDelete` classes 
    * `BulkSqlTrait` with `iterateOverItems` method
    * `IGeneralSql` and `IBulkSql` interfaces
</details>

### Fixed
- Methods return types in `Utils\Transaction`
- `League\Database\Utils\Transaction` rename method `try` to `attempt`

### Removed
- `hhvm` support from `travis-ci.yml`

***

## 2017-09-30

### Added
- `Driver\Engine` class to set connection with DB
- `Utils\Transaction` class to operate with transaction in static context
- `ConnectionManager` wrapper class to set connection for `master` and `slave` connections

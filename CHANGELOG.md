# Changelog

## [3.0.0] - 2022-10-17

Breaking changes: refactored entirely for PHP 8.1.
Many interfaces and method signatures are updated, strict typing is enforced.

## [2.0.4] - 2019-10-24

Added Dennis' feature to better handle soft deleting models, including a configuration option that
controls whether updating trashed records is allowed.

## [2.0.3] - 2019-10-21

Added Dennis' feature to allow `forceCreate()` and `forceUpdate()`, which ignores fillable guarding.

## [2.0.2] - 2019-09-27

Fixed a few incorrect method signatures (not compatible with the PHP 7+ strict hints).
Replaced a reference to the `App` alias with a direct reference to the facade.

## [2.0.1] - 2019-09-27

Fixed an issue with BelongsToMany where saving on the relation causes duplicates to be added (or SQL errors to occur).

## [2.0.0] - 2019-09-21

Introduced strict return types and scalar typehints.
Added test setup for Laravel 6.0 context.


[3.0.0]: https://github.com/czim/laravel-nestedupdater/compare/2.0.4...3.0.0
[2.0.4]: https://github.com/czim/laravel-nestedupdater/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/czim/laravel-nestedupdater/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/czim/laravel-nestedupdater/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/czim/laravel-nestedupdater/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/czim/laravel-nestedupdater/compare/1.5.0...2.0.0

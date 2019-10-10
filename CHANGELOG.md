# Changelog

## [2.0.2] - 2019-09-27

Fixed a few incorrect method signatures (not compatible with the PHP 7+ strict hints).
Replaced a reference to the `App` alias with a direct reference to the facade.

## [2.0.1] - 2019-09-27

Fixed an issue with BelongsToMany where saving on the relation causes duplicates to be added (or SQL errors to occur).

## [2.0.0] - 2019-09-21

Introduced strict return types and scalar typehints.  
Added test setup for Laravel 6.0 context.


[2.0.1]: https://github.com/czim/laravel-nestedupdater/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/czim/laravel-nestedupdater/compare/1.5.0...2.0.0

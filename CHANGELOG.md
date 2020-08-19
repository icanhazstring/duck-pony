# Changelog

## [Unreleased]
### Added:
- Nothing
### Changed:
- Nothing
### Removed:
- Nothing

## [0.6.6] - 2020-07-15
### Added:
- Added the `keep-days` option to the `folder:clean` command to specify the maximum amount of days a branch is allowed to remain
  - The branch will be deleted after the maximum amount of days, even if the issue status does not match

## [0.6.5] - 2020-07-12
### Added:
- Added a `host_name` option to prevent accidentally database purges on non-master hosts.[#26](https://github.com/icanhazstring/duck-pony/pull/26) (thanks to [@doganoo](https://github.com/doganoo))
### Changed:
- Updated `monolog` library to v2.x.

## [0.6.4] - 2020-03-02
### Changed:
- Added a `JiraTicketNotFound` exception to better analyse deleted/or missing jira tickets. Which resultet in database or folder to be ignored instead of removed. [#24](https://github.com/icanhazstring/duck-pony/pull/24) (thanks to [@d-feller](https://github.com/d-feller))

## [0.6.3] - 2020-02-20
### Changed:
- Wrapped `PDOConnection` to avoid instant connection which makes it impossible to use this tool on systems which don't have
pdo installed [#23](https://github.com/icanhazstring/duck-pony/pull/23) (thanks to [@mheist](https://github.com/mheist))

## [0.6.2] - 2020-02-17
### Changed:
- Dropped support for `php:^7.1`
- Required `icanhazstring/systemctl:^0.7`

## [0.6.1] - 2020-02-17
### Fixed:
- Fixed issue where configuration was wrong [#21](https://github.com/icanhazstring/duck-pony/pull/21) (thanks to [@smuggli](https://github.com/smuggli))

## [0.6.0] - 2019-11-22
### Added:
- Added new `PurgeDatabase (purge:database)` command which enables dropping databases using a given `pattern`

### Changed:
- Changed yaml config to php configuration files using `zend-config`
- Renamed some configuration values to reflect general naming (snake_case)
- Renamed `CleanBranch (folder:clean)` to `PurgeIssueFolder (issue:purge-folder)` (old command name will be still intact as alias)
- Renamed `CleanMySQLDatabase (db:clean)` to `PurgeIssueDatabase (issue:purge-db)` (old command name will be still intact as alias)
- Renamed `PurgeService (service:purge)` to `PurgeIssueService (issue:purge-service)` (old command name will be still intact as alias)
- Changed name of `RemoveOrphanedSymlinks` from `symlinks:remove_orphaned` to `symlinks:remove-orphaned` (old alias still intact)

### Removed:
- Removed support of `--config|c` option on certain commands as the configs is created using `tempa-php`
- Removed `symfony/yaml` as a dependency

## [0.5.1] - 2019-11-19
### Fixed:
- Fixed an issue where the typehint of `branchname-filter` was expected as `string` where it is actually an `array`

## [0.5.0] - 2019-11-19
### Added:
- Added dependency injection through `league/container`

## [0.4] - 2019-11-18
### Added:
- Critical error messages can now be sent to a slack channel
### Updated:
- Code cleanup, add editorconfig
- Add php extensions to composer.json

## [0.3] - not tagged
### Updated:
- `CleanBranch` now accepts a `branchname-filter` argument
  for matching only relevant parts of the respective jira ticket.
- Introduced `CleanMySQLDatabase` Command for cleaning orphaned mysql databases.
- Unifies Jira config which is now shared between `CleanBranch` and `CleanMySQLDatabase`.

## [0.2] - 2019-03-20
### Updated:
- `symfony/*` updated to version `^4.0`
- `icanhazstring/tempa-php` updated to version `^2.0`
- `icanhazstring/systemctl-php` updated to version `^0.6`

### Added:
- `CleanBranch` now has some debug console output
  - You can trigger those outputs using the `-vvv` command option
- Finally added a README.md

### Performance:
- `CleanBranch` now only scans *folders* on the first level of provided folder
  - It used to scan all files recursively which broke down if there were many folders with amount of files in them
- `PurgeService` nw only scans *folders* on the first level of provided folder
  - It used to scan all files recursively which broke down if there were many folders with amount of files in them

## [0.1.0] - 2019-03-04
Initial Release

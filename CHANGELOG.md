# Changelog

# [0.6.0] - TBA
## Changed:
- Changed yaml config to php configuration files using `zend-config`
- Renamed some configuration values to reflect general naming (snake_case)
- Renamed `CleanBranch (folder:clean)` to `PurgeIssueFolder (issue:purge-folder)` (old command name will be still intact as alias)
- Renamed `CleanMySQLDatabase (db:clean)` to `PurgeIssueDatabase (issue:purge-db)` (old command name will be still intact as alias)
- Renamed `PurgeService (service:purge)` to `PurgeIssueService (issue:purge-service)` (old command name will be still intact as alias)
- Changed name of `RemoveOrphanedSymlinks` from `symlinks:remove_orphaned` to `symlinks:remove-orphaned` (old alias still intact)

## Removed:
- Removed support of `--config|c` option on certain commands as the configs is created using `tempa-php`
- Removed `symfony/yaml` as a dependency 

# [0.5.1] - 2019-11-19
## Fixed:
- Fixed an issue where the typehint of `branchname-filter` was expected as `string` where it is actually an `array`

# [0.5.0] - 2019-11-19
## Added:
- Added dependency injection through `league/container`

# [0.4] - 2019-11-18
## Added:
- Critical error messages can now be sent to a slack channel
## Updated:
- Code cleanup, add editorconfig
- Add php extensions to composer.json

# [0.3] - not tagged
## Updated:
- `CleanBranch` now accepts a `branchname-filter` argument
  for matching only relevant parts of the respective jira ticket.
- Introduced `CleanMySQLDatabase` Command for cleaning orphaned mysql databases.
- Unifies Jira config which is now shared between `CleanBranch` and `CleanMySQLDatabase`.

# [0.2] - 2019-03-20
## Updated:
- `symfony/*` updated to version `^4.0`
- `icanhazstring/tempa-php` updated to version `^2.0`
- `icanhazstring/systemctl-php` updated to version `^0.6`

## Added:
- `CleanBranch` now has some debug console output
  - You can trigger those outputs using the `-vvv` command option
- Finally added a README.md

## Performance:
- `CleanBranch` now only scans *folders* on the first level of provided folder
  - It used to scan all files recursively which broke down if there were many folders with amount of files in them
- `PurgeService` nw only scans *folders* on the first level of provided folder
  - It used to scan all files recursively which broke down if there were many folders with amount of files in them

# [0.1.0] - 2019-03-04
Initial Release

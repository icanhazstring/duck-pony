# Changelog

# 0.2
Updated:
- `symfony/*` updated to version `^4.0`
- `icanhazstring/tempa-php` updated to version `^2.0`
- `icanhazstring/systemctl-php` updated to version `^0.6`

Added:
- `CleanBranch` now has some debug console output
  - You can trigger those outputs using the `-vvv` command option
- Finally added a README.md

Performance:
- `CleanBranch` now only scans *folders* on the first level of provided folder
  - It used to scan all files recursively which broke down if there were many folders with amount of files in them
- `PurgeService` nw only scans *folders* on the first level of provided folder
  - It used to scan all files recursively which broke down if there were many folders with amount of files in them

# 0.1
Initial Release

# duck-pony

`duck-pony` is a little maintenance tool used to manage integration server instances.
It is linked to Atlassian Jira to retrieve information of what actions to perform.

These actions include
- CleanBranch
- CleanMySQLDatabase
- PurgeService
- RemoveOrphanedSymlinks

# Installation
To use this tool simply clone or download it onto your integration server.
> Be aware that some commands needs some kind of `root` execution rights (e.g. to shutdown a service using `systemctl`)

Start by using composer to install all needed packages. (You might want to skip dev packages)
```bash
$ composer install --no-dev
```

To configure your `config.yml` use [icanhazstring/tempa-php](https://github.com/icanhazstring/tempa-php) which is included in this tool.

```bash
$ vendor/bin/tempa file:substitute \
    config/ \
    tempa.json \
    host=JIRA_HOST \
    username=JIRA_USER \
    password=JIRA_USER_PASSWORD \
    pattern=BRANCH_PATTERN_REGEX \
    db_host=DB_HOST \
    db_username=DB_USER \
    db_password=DB_PASSWORD \
    instancePattern=SYSTEMD_SERVICE_PATTERN
    
```

> The `pattern` is used to identify tickets and folders alike. This means, your folders **MUST** have the same name
as the ticket in your jira board.

# Commands
## CleanBranch

```bash
$ bin/dp folder:clean --help

Description:
  Scan folder an clean branches

Usage:
  folder:clean [options] [--] <folder>

Arguments:
  folder                 Folder
  branchname-filter      Remove parts of the folder name to match jira ticket

Options:
  -s, --status=STATUS    Status
  -p, --pattern=PATTERN  Branch pattern
  -i, --invert           Invert status
  -y, --yes              Confirm questions with yes
  -c, --config=CONFIG    Config [default: "/home/vendor/duck-pony/config/config.yml"]
  -f, --force            Force delete
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
  --branchname-filter    Remove parts of the branchname for better jira ticket matching

Help:
  Scan folder iterate over sub folders and removes
  them under certain conditions
```

**Example**: Clean every branch that is currently **not** "in progress", "reopened", "todo" or "in review".
```bash
$ bin/dp folder:clean /path/to/folder --status="reopened,open,in progress,in review" --invert --yes
```

## PurgeService

```bash
Description:
  Scan folder an purge services with same name

Usage:
  service:purge [options] [--] <folder>

Arguments:
  folder                 Deployment folder as reference

Options:
  -u, --unit=UNIT        Name of unit
  -p, --pattern=PATTERN  Instance pattern
  -c, --config=CONFIG    Config [default: "/home/vendor/duck-pony/config/config.yml"]
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Disables and stops systemd services that have
  no reference folder in given folder argument
```

**Example**: Purge every `awesome` service from systemd which is not present in scanned folder
Assuming the following folder structure and services:
```
/path/to/folder
 |- ABC-123
 
 
systemd services:
- awesome@ABC-124
- awesome@ABC-125
```

Executing this command:
```bash
$ bin/dp service:purge /path/to/folder --unit=awesome@'
```

Will remove the following systemd services:
- awesome@ABC-124
- awesome@ABC-125

## RemoveOrphanedSymlinks

```bash
Description:
  Removes orphaned symlinks of a given folder

Usage:
  symlinks:remove_orphaned <folder>

Arguments:
  folder                Folder

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Removes only orphaned symlinks under a given folder without any recursion.
```

Example: Remove every orphaned symlink under nginx site-enabled
```bash
$ bin/dp symlinks:remove_orphaned /etc/nginx/sites-enabled
```

## CleanMySQLDatabase
```bash
$ bin/dp db:clean --help

Description:
  Scans Database and cleans orphaned

Usage:
  db:clean [options] [--] <pattern>

Arguments:
  branchname-filter      Remove parts of the folder name to match jira ticket                 

Options:
  -s, --status=STATUS    Status
  -p, --pattern=PATTERN  Branch pattern
  -i, --invert           Invert status
  -c, --config=CONFIG    Config [default: "/home/vendor/duck-pony/config/config.yml"]
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Scans MySQL Databases and removes
  them under certain conditions
```

**Example**: Clean every database of tickets that are currently **not** "in progress", "reopened", "todo" or "in review".
```bash
$ bin/dp db:clean --status="reopened,open,in progress,in review" --invert rsv_feature- rsv_bugfix-
```

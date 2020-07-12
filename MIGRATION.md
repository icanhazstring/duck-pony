# Migrations

## 0.5 to 0.6
- `slackToken`, `slackChannel` and `instancePattern` got renamed to to use snake case namings.

     ```bash
     $ vendor/bin/tempa file:substitute \
         config/ \
         tempa.json \
         jira_host=JIRA_HOST \
         jira_username=JIRA_USER \
         jira_password=JIRA_PASSWORD \
         pattern=BRANCH_PATTERN_REGEX \
         db_host=DB_HOST \
         db_username=DB_USER \
         db_password=DB_PASSWORD \
         instance_pattern=SYSTEMD_SERVICE_PATTERN \
         slack_token=SLACK_TOKEN \
         slack_channel=SLACK_CHANNEL
     ```
- add the `host_name` option to `purge:database` and `db:clean` with the master's hostname to prevent accidentally dropping databases on slaves

## 0.3 to 0.4
Adjust config.yml:

```bash
$ vendor/bin/tempa file:substitute \
    config/ \
    tempa.json \
    jira_host=JIRA_HOST \
    jira_username=JIRA_USER \
    jira_password=JIRA_PASSWORD \
    pattern=BRANCH_PATTERN_REGEX \
    db_host=DB_HOST \
    db_username=DB_USER \
    db_password=DB_PASSWORD \
    instancePattern=SYSTEMD_SERVICE_PATTERN \
    slackToken=SLACK_TOKEN \
    slackChannel=SLACK_CHANNEL
```

## 0.2 to 0.3
Adjust config.yml:

```bash
$ vendor/bin/tempa file:substitute \
    config/ \
    tempa.json \
    jira_host=JIRA_HOST \
    jira_username=JIRA_USER \
    jira_password=JIRA_PASSWORD \
    pattern=BRANCH_PATTERN_REGEX \
    db_host=DB_HOST \
    db_username=DB_USER \
    db_password=DB_PASSWORD \
    instancePattern=SYSTEMD_SERVICE_PATTERN
```

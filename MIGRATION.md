# Migrations

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

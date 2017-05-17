# Httpauth
Prompts the user for credentials and prevents access for unauthorized users.

## Options
option      | type   | required | description
------------|--------|----------|------------
username    | string | ✓        | The username to be provided by user.
password    | string | ✓        | The password to be provided by user.

**Example**
```yaml
# site.yml

httpauth:
    username: user
    password: pass
```

## Provides
N/A

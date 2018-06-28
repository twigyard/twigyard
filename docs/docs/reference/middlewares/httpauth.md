# Httpauth
Prompts the user for credentials and prevents access for unauthorized users.

## Options
option                 | type   | required | description
-----------------------|--------|----------|------------
username               | string | ✓        | The username to be provided by user.
password               | string | ✓        | The password to be provided by user.
exclude_ip_addresses   | string | ❌       | The list of IP addresses that can access the site without authorization.

**Example**
```yaml
# site.yml

httpauth:
    username: user
    password: pass
    exclude_ip_addresses:
        - '75.76.77.78'
```

## Provides
N/A

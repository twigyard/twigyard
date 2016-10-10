# Tracking
Sets tracking codes to template.

## Options
option           | type   | required | description
-----------------|--------|----------|------------
google_analytics | string | ❌       | The Account id string

```yaml
tracking:
    google_analytics: UA-000000-01
```

## Provides
name           | type   | description
---------------|--------|------------
tracking       | map    | A map of strings where each key is the name of the service and  the value is the individual tracking code.



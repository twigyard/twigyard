# Data
This middleware parses the specified yaml files and makes their content available in a map within the templates.

## Provides
name | type | description
-----|------|-------------
data | map  | The name of the attribute in the data map is defined by the key in the definition map in site.yml. The value is then the content of the data yaml file defined in options.

## Options
Options are a map where the key is the name of the attribute and the value is the name of the data file relative to src/data folder. For example:

```yaml
data:
    references: references.yml
```

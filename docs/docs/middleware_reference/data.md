# Data
This middleware parses the specified yaml files and makes their content available in a map variable within the templates.

## Options
Options are a map where the key is the name of the attribute and the value is the path to the data file relative to the `src/data` folder.

```yaml
data:
    references: references.yml
```

## Provides
name | type | description
-----|------|-------------
data | map  | The name of the key in the data map is the same as the key in the definition map in site.yml. The value is the contents of the data yaml file.

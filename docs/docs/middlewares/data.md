# Data
This middleware parses the specified yaml or json files or resources from url and makes their content available in a map variable within the templates.

## Options
Options are a map where the key is the name of the attribute and the value can be either a map with type, format and resource location specified or is the path to the yml formatted data file relative to the `src/data` folder.

**Example**
```yaml
# site.yml

data:
    references: references.yml
    other_references:
        type: http
        format: json
        resource: http://www.example.com/references.json
     
```

## Provides
name     | type   | description
---------|--------|-------------
data     | map    | The name of the key in the data map is the same as the key in the definition map in site.yml. The value is the contents of the data yaml file.
type     | enum   | The type indicating whether a resource is of type `local` or `http`.
format   | enum   | The type indicating whether a resource is in `yml` or `json` format.
resource | string | The path to the local file or the url to the remote resource

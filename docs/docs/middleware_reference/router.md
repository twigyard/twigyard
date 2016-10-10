# Router
Parses the request URI. The routes are processed sequentially and the first match is used.

## Options
Page and param names should always use undescore_notation.

Params can be part of the path and are written in curly brackets (i.e. `{param_name}`). If the param name is to be matched against data attribute the format is `{param_name | data_attribute_name:element_name.subelement_name}`. The matching  only works against list data attributes. If the param is to be matched, but a match can not be found a response with 404 HTTP status code is returned.

###Single Language Sites
A map of pages where the key is the page name and the value is the route definition.

```yaml
router:
    index: /
    product: /products/{product_id | products:manufacturer.id}
```

### Multi Language Sites
A map of pages where the key is the page name and the value is a map defining the page routes for the individual locales. Each page routes map has locale as a key (i.e. en_US) and the value is the path to be matched without the language key.

```yaml
router:
    index:
        cs_CZ: /
        en_US: /
    product:
        cs_CZ: /zbozi/{product_id | products:manufacturer.id}
        en_US: /products/{product_id | products:manufacturer.id}
```
 
## Provides
name           | type   | description
---------------|--------|------------
page           | string | The name of the page to be rendered.
url_params     | map    | A map of parameters parsed from URI if any are present.

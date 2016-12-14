# Renderer
Renders templates.

## Options
The configuration is a map wher each key is the name of the page as it is defined in the configuration of the router middleware and the corresponding value is the path to the template to be used for that page. If the template can not be found an exception is thrown.

#####Example
```yaml
# site.yml

renderer:
    index: index.html.twig
    product: product.html.twig
```

### Single language
For single language sites, the path is relative to `<site_root>src/templates` folder.

### Multi language
For multi language sites, the path is first checked relative to `<site_root>src/templates/<locale>` folder. If no match is found, the path is checked relative to `<site_root>src/templates` folder.

## Provides
N/A

# Path
This function returns the URL for the given route. 

## Parameters
```path(page, queries, locale)```

parameter        | type           | required | description
-----------------|----------------|----------|------------
page             | string         | ✓        | Key of current page listed in `site.yml` (e.g. `index` or `contact`).
queries          | array          | ❌        | Map of URL parameters (e.g. `{ 'product_slug': product.slug }`)
locale           | string         | ❌        | Language-Territory code (e.g. `cz_CZ`).

**Example**

```
# file.html.twig

…
<a href="{{ path('index') }}">
    Back to homepage
</a>
…

```

**Example**

In this example you can see how to set path to page with product details. 
The product is identified by `slug` which is validated against corresponding YAML file 
containing all products (here `products.yml` assigned to `data.products` variable in `site.yml`).

```
# file.html.twig

…
{% for product in data.products %}
    <a href="{{ path('product', { 'product_slug': product.slug }) }}">
        Product detail
    </a>
{% endfor %}
…

```

**Example**

This example shows how you can correctly link across multilingual versions of a page.

```
# file.html.twig

…
<head>
    …
    <link 
        rel="alternate"    
        hreflang="en" 
        href="{{ 'https://' ~ url ~ path(page, url_params, 'en_US') }}" 
    />
    …
</head>
…

```

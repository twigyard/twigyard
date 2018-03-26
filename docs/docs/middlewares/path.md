# Path
This function returns the URL for the given route. 

## Parameters
```path(page, queries, locale)```

parameter        | type           | required | description
-----------------|----------------|----------|------------
page             | string         | ✓        | Key of current page listen in `site.yml` (e.g. `index` or `contact`).
queries          | array          | ❌        | Map of url parameters (e.g. `{ 'product_slug': product.slug }`)
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

This example shows how you can set path to detail page contents information about specific product.
The specific product is here clearly identified by `slug` which must be key in relevant `.yml` file
(in this case `products.yml`, which has to be listed in `site.yml`).

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

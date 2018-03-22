# Path
This component returns the URL for the given route. 

## Parameters
```path(page, queries, locale)```

parameter        | type           | required | description
-----------------|----------------|----------|------------
page             | array          | ✓        | The name of page (e.g. www.mysite.com).
queries          | string         | ❌        | Array of url parameters (e.g. [gallery: nature])
locale           | string         | ❌        | Shortcut for language.

**Example**

```
# file.html.twig

...
<a href="{{ path('index') }}">
    Back to homepage
</a>
...

```

**Example**

This example shows how set absolute path in case of multilingual content of webpage.

```
# file.html.twig

...
<link 
    rel="alternate"    
    hreflang="en" 
    href="{{ 'https://' ~ url ~ path(page, url_params, 'en_US') }}" 
/>
...

```

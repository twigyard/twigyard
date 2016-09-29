# URL
Parses the HTTP request URL and sets the appropriate site configuration for current request. If there is no site with the requested URL a response with 404 HTTP status code is returned.

## Provides
name
type
always
description
url	string	(tick)	The canonical URL. 

## Options
option           | type   | required | description
-----------------|--------|----------|------------
canonical        | string | ✓        | The main URL of the site.
extra            | list   | ❌        | An array of additional URLs.

```yaml
url:
    canonical: www.example.com
    extra:
        -   example.com
        -   web.example.com
```

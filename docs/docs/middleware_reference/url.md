# URL
Parses the HTTP request URL and sets the appropriate site configuration for current request. If the request URL is listed among the `extra` addresses a 301 redirect to the canonical URL is made. If there is no site with the requested URL a response with 404 HTTP status code is returned. If the `ssl` option is set to `true` a 301 redirect to the same path over HTTPS is made.

## Options
option           | type   | required | description
-----------------|--------|----------|------------
canonical        | string | ✓        | The main URL of the site.
extra            | list   | ❌       | An array of additional URLs.
ssl              | bool   | ❌       | Automatic redirection from HTTP to HTTPS.

**Example**
```yaml
# site.yml

url:
    canonical: www.example.com
    extra:
        -   example.com
        -   web.example.com
    ssl: true
```

## Provides
name | type     | description
-----|----------|------------
url  | string   | The canonical URL. 


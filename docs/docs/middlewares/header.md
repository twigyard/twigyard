# Header

Injects HTTP headers to HTTP response based on `site.yml` configuration.

## Options
option                     | type   | required  | description
---------------------------|--------|-----------|------------
Content-Security-Policy    | array  | ❌        | [Content-Security-Policy documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy)
Referrer-Policy            | string | ❌        | [Referrer-Policy documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy)
X-Content-Type-Options     | string | ❌        | [X-Content-Type-Options](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options)

**Default configuration** 

These HTTP headers are injected to HTTP response when `header` attribute is not present in `site.yml`

```yaml
# site.yml

header:
    'Content-Security-Policy':
        'default-src':
            - "'self'"     # default value for HTTP
            - "https:"     # default value for HTTPS
    'Referrer-Policy': strict-origin
    'X-Content-Type-Options': nosniff
```

**Advanced configuration**

```yaml
# site.yml

header:
    'Content-Security-Policy':
        'default-src':
            - "'self'"
        'img-src':
            - "'self'"
            - "https://i.imgur.com"
    'Referrer-Policy': same-origin
    'X-Content-Type-Options': nosniff
```

**Globally disabled configuration**

To disable HTTP headers injection to HTTP response globally, set `header` value to `~` in `site.yml`

```yaml
# site.yml

header: ~
```

**Individually disabled configuration**

To disable HTTP headers injection to HTTP response individually, set specific attribute value to `~` in `site.yml`

```yaml
# site.yml

header:
    'Content-Security-Policy': ~
    'Referrer-Policy': ~
    'X-Content-Type-Options': ~
```

## Provides
N/A

# Redirect
This middleware checks the map of configured urls and it redirects (with http status code 301 Moved Permanently) to the first found url. If there is no matching url in the map, middleware passes control to next middleware in the queue.

## Provides
N/A

## Options
The configuration is a map where each key is the path to be redirected and each value is the path to be redirected to.

```yaml
redirect:
    /page/old/1: /page/new/1
    /page/old/2: /page/new/2
```

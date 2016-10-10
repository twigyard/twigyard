# Redirect
This middleware checks the configuration map and it performs 301 redirect upon the first match. If there is no matching url in the map, middleware passes control to next middleware in the queue.

## Options
The configuration is a map where each key is the path to be redirected and each value is the path to be redirected to.

```yaml
redirect:
    /page/old/1: /page/new/1
    /page/old/2: /page/new/2
```

## Provides
N/A

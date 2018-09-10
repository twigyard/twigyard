# Asset
This function returns path to file with a hash query parameter to invalidate browser cache. The query parameter is generated from the file contents, so it only changes when the file was changed. 

## Parameters
```asset(path)```

parameter        | type           | required | description
-----------------|----------------|----------|------------
path             | string         | ✓        | Relative path to file inside `web` directory.

**Example**
```
# file.html.twig

…
<img 
    src="{{ asset('animations/circle.svg') }}"
    alt="File name" 
/>
…

```

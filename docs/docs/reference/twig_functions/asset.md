# Asset
This function returns path to file. 

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
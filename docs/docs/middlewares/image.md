# Image
This component returns path to image in `image_cache` directory. It also offers edit functions thanks Gregwar.

For more information about Gregwar visit [Gregwar documentation](https://github.com/Gregwar/Image/).

## Parameters
```image(path)```

parameter   | type   | required   | description
------------|--------|------------|------------
path        | string | ✓          | Relative path to image inside `web` directory.


**Example**
```
# file.html.twig

...
<img
    src="{{ image('images/cover.jpg').zoomCrop(1920,1080) }}"
    alt="Cover photo"
/>
...

```

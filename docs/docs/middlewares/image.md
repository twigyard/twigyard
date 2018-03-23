# Image
This function returns path to image in `image_cache` directory.
The output image can be modified by functions provided by *Gregwar/Image* library.

For more information about *Gregwar/Image* and the features it provides do visit 
[Gregwar documentation](https://github.com/Gregwar/Image/).

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

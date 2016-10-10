# Error
Displays error pages. All pages must be plain HTML. No templating engine can be used. The pages must be named `404.html` and `500.html`.

In multi language sites the localized version of the error page can be saved in `templates/<locale_name>/<page_name>`. If the file does not exist or if the site is single language then the location `templates/<locale_name>/<page_name>` is checked. If the error page still can not be found a global one will be used.

## Options
N/A

## Provides
N/A

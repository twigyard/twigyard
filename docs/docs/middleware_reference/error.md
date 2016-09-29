# Error
Displays error pages. All pages must be plain html, no templating engine can be used.
 
**`404`**
If the received response has 404 HTTP status code, it displays 404 error page. For multi language sites the page must be located at `templates/<locale_name>/404.html`. For single language sites the page must be located at `templates/404.html`. If the site specific 404 page file does not exist, a global 404 page is used.

**`500`**
If there is an error during the request processing and the site is in production mode, an error page is displayed. In development mode the PHP error messages are dumped.

## Provides
N/A

## Options
N/A

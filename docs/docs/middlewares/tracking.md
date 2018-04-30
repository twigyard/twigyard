# Tracking
Sets tracking codes to template.

## Options
option           | type   | required | description
-----------------|--------|----------|------------
google_analytics | string | ❌       | The path to the file that contains tracking code which is relative to `web` folder.

**Example**
```yaml
# site.yml

tracking:
    google_analytics: js/google-analytics.js
```

```js
# web/js/google-analytics.js

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'example-ga-code', 'auto');
ga('send', 'pageview');
```

```twig
# src/templates/_layouts/base.html.twig

<head>
    {% if tracking.google_analytics is not empty %}
        <script src="{{ asset(tracking.google_analytics) }}"></script>
    {% endif %}
</head>
```

## Provides
name           | type   | description
---------------|--------|------------
tracking       | map    | A map of strings where each key is the name of the service and the value is the path to the file that contains tracking code which is relative to `web` folder.



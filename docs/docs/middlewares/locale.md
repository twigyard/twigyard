# Locale
Sets the locale. For multilingual sites it parses the url and sets the locale accordingly. If the language key is missing, it performs a 301 redirect to the requested page with the default language key set. Supported locales are:

* cs_CZ
* en_US
* de_DE

## Options

N/A

###Single Language Sites
For single language sites it accepts the locale string.

**Example**
```yaml
# site.yml 

locale: en_US
```

### Multi Language Sites
For multilingual sites it accepts a map with the following options:

option      | type   | required | description
------------|--------|----------|------------
default     | map    | ✓        | A map that defines the default locale. It must provide two keys: `name` which identifies the default locale for application and `key` which identifies the locale in the url.
extra       | map    |✓         | A map of additional locales where the key is the url identifier of the locale and the value is the locale name.

**Example**
```yaml
# site.yml 

locale:
    default:
        key: cs
        name: cs_CZ
    extra: { en: en_US }
```
 
## Provides
name           | type   | description
---------------|--------|------------
locale         | string | The locale name used for the current request (i.e. en_US).

#### Note
It is possible return error pages according the locale. Add into `src/templates` new directories according locale (eg. `en_US`) and there keeps error pages.

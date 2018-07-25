# Core concepts
Each site is represented by a folder in the `sites` directory. The name of the folder must be the same as the canonical URL of the site defined in its `site.yml` file. As the bare minimum TwigYard requires the `site.yml` file and the `src` folder to be present for the site to be functional.

On the frontend side of things TwigYard is pretty technology agnostic. Type raw css, use grunt, gulp or whatever you like. You do not need to modify your current devstack in any way to use TwigYard. Within the site folder you are free to use any tools you like to as long as they generate the required file structure.

## site.yml
TwigYard requires that each site has a main config file whose name is defined in the instance wide `parameters.yml` file. While the name can be anything, it is recommended to name it `site.yml` if possible. For simplicity we will refer to this main site config as `site.yml` in this manual.

The `site.yml` has two versions. The now deprecated version 1 functions as the fallback if no version is specified. See the middleware reference for supported configuration options.

### Substitution
Some data needed in the `site.yml` file might be too sensitive to be commited to VCS. These secrets can be defined in a file `parameters.yml` which can be kept out of version control. Data from this file can be used in the `site.yml` via the `%param_name%` syntax. All parameters to be substituted must be defined in the `parameters.yml` as a flat map under top level `parameters` key. The file must be manually included in the `site.yml`. 
 
### Composition
The `site.yml` can include other files to make the configuration more flexible. For example it allows the config to be divided into multiple files for better readability.
```yml
# site.yml

...
imports:
    - { resource: 'router.yml' }
    - { resource: 'renderer.yml' }
```

### Overloading
Imports make it possible to have the site behave differently between environments. On a staging instance, for example, we might prefer to use a different configuration then on production. Typically we need to protect the site by httpauth. This can be solved by creating a separate `site_staging.yml` which imports the production `site.yml` and set the staging instance to use this is the main config file. This way we can overload any configuration in `site.yml`.


**Example**
```yml
# parameters.yml

parameters:
    mailing_smtp_host: mail
    mailing_smtp_port: 1025
```

```yml
# site_staging.yml

version: 2
middlewares:
    httpauth:
        username: user
        password: pass

imports:
    - { resource: 'site.yml' }
```
          
```yml
# site.yml

version: 2

componets:
    mailer:
        smtp_host: "%mailer_smtp_host%"
        smtp_port: "%mailer_smtp_port%"
        smtp_encryption: "%mailer_smtp_encryption%"
        smtp_username: "%mailer_smtp_username%"
        smtp_password: "%mailer_smtp_password%"

middlewares:
    url:
        canonical: www.example.com
        extra: [ example.com, web.example.com ]
     
    locale:
        default:
            key: cs
            name: cs_CZ
        extra: { en: en_US, de: de_DE }

    data:
        contact: contact.yml
        products: products.yml

    router:
        index:
            cs_CZ: /
            en_US: /
            de_DE: /
        product:
            cs_CZ: /zbozi/{product_id | products:manufacturer.id}
            en_US: /products/{product_id | products:manufacturer.id}
            de_DE: /produkte/{product_id | products:manufacturer.id}
    form:
        contact:
            handlers:
                -   type: email
                    from:
                        name: John Doe Corp.
                        address: john@doe.com
                    recipients:
                        to: [ example_1@example.com ]
                        bcc: [ example_2@example.com ]
                    templates:
                        subject: email/subject.html.twig
                        body: email/body.html.twig
            fields:
                name: { type: string, min_length: 3, max_length: 100 }
                email: { type: email, required: true }
                message: { type: string, min_length: 10, max_length: 1000, required: true }
    renderer:
        index: index.html.twig
        product: product.html.twig

imports:
    - { resource: 'parameters.yml' }
```

## src
All site files except site config files (i.e. `site.yml`, `site_staging.yml`, `parameters.yml`, etc.) are to be located according to their type in a folder within the `src` directory. The subfolders are:

* data (For data yaml files used by the `data` middleware)
* languages (For translation strings)
* templates (The Twig templates)

### Twig templates
TwigYard uses template engine for PHP &ndash; Twig. For more information about <a href="https://twig.symfony.com/doc/2.x/templates.html" target="_blank">templates</a> or available functions and filters please read <a href="https://twig.symfony.com/doc/2.x" target="_blank">Twig documentation</a>.


# Sites
Each site is represented by a folder in the sites directory. The name of the folder must be the canonical URL of the site. As the bare minimum TwigYard requires the `site.yml` file and the `src` folder to be present. 

On the frontend side of things TwigYard is pretty technology agnostic. Type raw css, use grunt, gulp or whatever you like. You do not need to modify your devstack in any way. Within the site folder you are free to use any tools you like to as long as they generate the required files.

## site.yml
TwigYard requires that each site has a main config file whose name is defined in the instance wide `parameters.yml` file. While the name can be anything, it is recommended to name it `site.yml` if possible. For simplicity we will refer to this main site config as `site.yml` in this manual.

See the middleware reference for supported configuration options.

### Includes
The `site.yml` can include other files to make the configuration more flexible.
This feature has two benefits.

First, it allows the config to be broken up into multiple files for better readability.
```
...
imports:
    - { resource: 'router.yml' }
    - { resource: 'renderer.yml' }
```
Second, it makes it possible to have the site behave differently on different instances. If, for example, we would like to have a staging instance where the site must be protected by httpauth header, we would have the following in `site_staging.yml` and set the instance to use this is the main config file.
```
httpauth:
    username: user
    password: pass

imports:
    - { resource: 'site.yml' }
```

**Example** 
```
# site.yml

url:
    canonical: www.example.com
    extra: [ example.com, web.example.com ]
 
httpauth:
    username: user
    password: pass
 
locale:
    default:
        key: cs
        name: cs_CZ
    extra: { en: en_US, es: es_ES }
  
data:
    contact: contact.yml
    products: products.yml
 
router:
    index:
        cs_CZ: /
        en_US: /
        es_ES: /
    product:
        cs_CZ: /zbozi/{product_id | products:manufacturer.id}
        en_US: /products/{product_id | products:manufacturer.id}
        es_ES: /productos/{product_id | products:manufacturer.id}
 
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
```

## parameters.yml
Some site specific configurations can differ between the different environments. For example it might be desirable to use different SMTP server during development and on production. To achieve this it is possible to have a `parameters.yml` within the site directory. If the file is not present, the values defined in instance wide `default_site_parameters.yml` are used. 

## src
All site files except the site config and site parameters are to be located according to their type in a folder within the `src` directory. The subfolders are:

* data (For data yaml files used by the `data` middleware)
* languages (For translation strings)
* templates (The Twig templates)

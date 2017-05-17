# Tools

All scripts are located in the folder `vendor/bin`. To run them, simply ensure that you are in the root of your application and type `php vendor/bin/<name_of_the_script>`

## lintsite
TwigYard comes bundled with a script to check the syntax of yaml and twig files in the site directory. It checks all twig files in the `src/templates` folder and all yaml files in the site root and in the `src/languages` and `src/data` folders.

option          | description
----------------|-------------
--help          | Displays help
--all           | Lints all sites in the `/sites` directory
<name_of_site\> | Lints only the specified site. The site name must match the sites canonical URL.

# TwigYard

TwigYard is a PHP library to host multiple websites written in the [twig templating language](http://twig.sensiolabs.org).

Please refer to the [documentation](http://docs.twigyard.com) for details.

Pull requests are welcome. Please make sure to write tests and use PSR-2. We use the [Robo](http://robo.li/) task runner, so before submitting a pull request please run `$ robo test` to make sure everything is in order. 

## Docs

### Build:

To build the static site files run:

`docker-compose run --rm mkdocs bash -c "sh /root/init-container.sh /workspace && cd docs && su docker-container-user -c 'mkdocs build'"`

### Dev:

To run the site locally for easy development run:

`docker-compose run --rm --service-ports mkdocs bash -c "sh /root/init-container.sh /workspace && cd docs && su docker-container-user -c 'mkdocs serve'"`

Site will be accessible at http://localhost:8000

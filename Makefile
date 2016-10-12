.PHONY: clean clean-test clean-build docs help test vagrant
.DEFAULT_GOAL := help

define PRINT_HELP_PYSCRIPT
import re, sys

for line in sys.stdin:
	match = re.match(r'^([a-zA-Z_-]+):.*?## (.*)$$', line)
	if match:
		target, help = match.groups()
		print("%-20s %s" % (target, help))
endef
export PRINT_HELP_PYSCRIPT

define EXTRACT_PHPUNIT
<?php
$$phar = new Phar('vendor/phpunit.phar');
$$phar->extractTo('vendor/phpunit');
endef
export EXTRACT_PHPUNIT


help:
	@python -c "$$PRINT_HELP_PYSCRIPT" < $(MAKEFILE_LIST)

clean: clean-build clean-test ## remove all build, test, coverage and artifacts

clean-build: ## remove build artifacts
	rm -fr build/
	rm -fr dist/
	rm -fr .eggs/
	find . -name '*.egg-info' -exec rm -fr {} +
	find . -name '*.egg' -exec rm -f {} +

clean-test: ## remove test and coverage artifacts
	rm -fr .tox/
	rm -f .coverage
	rm -fr htmlcov/


test: ## run tests quickly with the default PHP
	phpunit

phpunit: vendor/phpunit

vendor/phpunit: vendor/phpunit.phar
	echo $$EXTRACT_PHPUNIT | php

vendor/phpunit.phar: vendor 
	wget -O vendor/phpunit.phar https://phar.phpunit.de/phpunit.phar

vendor:
	mkdir vendor

vagrant: ## Start vagrant virtual machine
	cd vagrant && vagrant up

docs: phpdoc graphviz ## Update documentation
	./vendor/phpdocumentor/phpdocumentor/bin/phpdoc -d lib -t docs

phpdoc:
	test -f ./vendor/phpdocumentor/phpdocumentor/bin/phpdoc || composer install

graphviz:
	@echo "Checking if graphviz is installed"
	@which dot > /dev/null 2>&1 || echo "Install graphviz. On MacOS it's brew install graphviz"

composer: ## Install composer locally
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	php composer-setup.php -- --cafile /tmp/cacert.pem
	php -r "unlink('composer-setup.php');"
	php -r "unlink('/tmp/cacert.pem');"

cov:  ## Print test coverage report
	phpunit --whitelist lib/ --coverage-html .cov test/

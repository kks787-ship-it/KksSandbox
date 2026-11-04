# Local disposable validation only. No image or dependencies are deployed.
ARG CSF_TEST_PHP_VERSION=8.1
FROM php:${CSF_TEST_PHP_VERSION}-cli-bookworm
RUN docker-php-ext-install mysqli

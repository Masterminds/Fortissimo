#!/bin/bash
# You might need to run this using sudo.

# Configure all default channels
pear channel-discover pear.phing.info
pear channel-discover pear.phpunit.de
pear channel-discover pear.symfony-project.com
pear channel-discover pear.twig-project.org

# Run pear
pear install phpDocumentor
pear install phpunit/PHPUnit
pear install phing/phing
pear install twig/Twig
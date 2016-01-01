<?php

require 'src/Component/Autoloader/Psr4ClassLoader.php';

$classLoader = new Psr4ClassLoader();

$classLoader->register();
$classLoader->addNamespace('bblue/ruby/tests', '../tests');
$classLoader->addNamespace('bblue/ruby', '../src');
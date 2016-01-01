<?php

namespace bblue\ruby\tests;

require 'src/Component/Autoloader/Psr4ClassLoader.php';

$classLoader = new bblue\ruby\Component\Autoloader\Psr4ClassLoader();

$classLoader->register();
$classLoader->addNamespace('bblue/ruby/tests', '../tests');
$classLoader->addNamespace('bblue/ruby', '../src');
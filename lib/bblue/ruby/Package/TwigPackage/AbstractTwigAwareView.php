<?php

namespace bblue\ruby\Package\TwigPackage;

use bblue\ruby\Component\Module\AbstractView;
use bblue\ruby\Package\TwigPackage\TwigAwareInterface;
use bblue\ruby\Package\TwigPackage\TwigAwareTrait;

abstract class AbstractTwigAwareView extends AbstractView implements TwigAwareInterface
{
	use TwigAwareTrait;
}
<?php

namespace bblue\ruby\Package\TwigPackage;

trait TwigAwareTrait
{
	/**
	 * Variable to hold instance of the twig library
	 * @var \Twig_Environment
	 */
	protected $twig;
	
	public function setTwig(\Twig_Environment $twig)
	{
		$this->twig = $twig;
	}
}
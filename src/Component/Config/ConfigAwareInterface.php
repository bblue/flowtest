<?php

namespace bblue\ruby\Component\Config;

interface ConfigAwareInterface
{
    /**
     * Set configuration instance into the class
     * @param ConfigInterface $config
     */
    public function setConfig(ConfigInterface $config);

    public function hasConfig();
}
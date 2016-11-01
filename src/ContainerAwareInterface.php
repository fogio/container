<?php

namespace Fogio\Container;

interface ContainerAwareInterface
{
    /**
     * Sets the container.
     *
     * @param ContainerInterface
     */
    public function setContainer(ContainerInterface $container);
}

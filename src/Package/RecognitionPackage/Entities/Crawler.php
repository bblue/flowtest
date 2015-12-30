<?php
namespace bblue\ruby\Entities;

/**
 * @Entity
 */
final class Crawler extends User
{
    public function isGuest() {return true;}
}
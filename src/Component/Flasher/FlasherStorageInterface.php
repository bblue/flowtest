<?php

namespace bblue\ruby\Component\Flasher;

interface FlasherStorageInterface
{
    public function get($level, $index = null);
    public function getAll();
    public function store(FlashItem $flash);
    public function storeArray(array $flashes);
    public function delete(FlashItem $flash);
    public function deleteAll();
}
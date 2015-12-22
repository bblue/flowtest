<?php

namespace bblue\ruby\Component\Validation;

interface iValidationBasics
{
    public function isValid();
    public function getErrors();
    public function hasError();
    public function validate();
}
<?php

namespace App\Http\Controllers;

use App\Contracts\Responses\ResponseMakerInterface;

abstract class Controller
{
    public function __construct(
        protected readonly ResponseMakerInterface $responseMaker,
    ) {}
}

<?php

namespace App\Exceptions;

use App\Data\JoesChangeRequestPreview;
use RuntimeException;

class MainSiteRequestAlreadyCompleted extends RuntimeException
{
    public function __construct(public readonly JoesChangeRequestPreview $preview)
    {
        parent::__construct('This change request was already fulfilled on the main site.');
    }
}

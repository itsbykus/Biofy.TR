<?php

namespace App\Exceptions;

use RuntimeException;

/** The draft was changed elsewhere (another tab/device) since the editor loaded it. */
class EditConflictException extends RuntimeException
{
}

<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A failure whose message is already translated and safe to show to the user.
 * Controllers turn it into a validation-style error instead of a 500 page.
 */
class UserFacingException extends RuntimeException
{
}

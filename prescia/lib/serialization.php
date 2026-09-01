<?php

declare(strict_types=1);

/**
 * Decode legacy serialized data without instantiating attacker-controlled classes.
 * Invalid payloads return false, matching the legacy decoder's semantics.
 *
 * @param mixed $payload
 * @return mixed
 */
function presciaSafeUnserialize($payload, array $allowedClasses = [])
{
    if (!is_string($payload) || $payload === '') {
        return false;
    }

    return unserialize($payload, ['allowed_classes' => $allowedClasses]);
}

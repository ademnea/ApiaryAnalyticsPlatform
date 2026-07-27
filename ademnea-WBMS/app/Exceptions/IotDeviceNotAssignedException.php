<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a device without a hive assignment attempts to submit data.
 * Callers (sensor/heartbeat/media services, or their HTTP-facing wrapper)
 * catch this and translate it into a 422 response / rejected_validation
 * ingestion log entry — it is a client-input problem (device provisioned
 * but not yet assigned), not a server fault.
 */
class IotDeviceNotAssignedException extends RuntimeException
{
}
<?php

namespace App\Support;

enum ApiErrorCode: string
{
    case Unauthorized = 'unauthorized';
    case NotFound = 'not_found';
    case ValidationError = 'validation_error';
    case UnknownParameter = 'unknown_parameter';
    case RateLimited = 'rate_limited';
    case QuotaExceeded = 'quota_exceeded';
    case ServerError = 'server_error';
}

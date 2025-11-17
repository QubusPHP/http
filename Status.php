<?php

declare(strict_types=1);

namespace Qubus\Http;

use function is_numeric;

class Status
{
    public const int CONTINUE = 100;
    public const int SWITCHING_PROTOCOLS = 101;
    public const int PROCESSING = 102;
    public const int EARLY_HINTS = 103;

    /**
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#2xx_success
     */
    public const int OK = 200;
    public const int CREATED = 201;
    public const int ACCEPTED = 202;
    public const int NON_AUTHORITATIVE_INFORMATION = 203;
    public const int NO_CONTENT = 204;
    public const int RESET_CONTENT = 205;
    public const int PARTIAL_CONTENT = 206;
    public const int MULTI_STATUS = 207;
    public const int ALREADY_REPORTED = 208;
    public const int THIS_IS_FINE = 218;
    public const int IM_USED = 226;

    /**
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_redirection
     */
    public const int MULTIPLE_CHOICES = 300;
    public const int MOVED_PERMANENTLY = 301;
    public const int FOUND = 302;
    public const int SEE_OTHER = 303;
    public const int NOT_MODIFIED = 304;
    public const int USE_PROXY = 305;
    public const int TEMPORARY_REDIRECT = 307;
    public const int PERMANENT_REDIRECT = 308;

    /**
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#4xx_client_errors
     */
    public const int BAD_REQUEST = 400;
    public const int UNAUTHORIZED = 401;
    public const int PAYMENT_REQUIRED = 402;
    public const int FORBIDDEN = 403;
    public const int NOT_FOUND = 404;
    public const int METHOD_NOT_ALLOWED = 405;
    public const int NOT_ACCEPTABLE = 406;
    public const int PROXY_AUTHENTICATION_REQUIRED = 407;
    public const int REQUEST_TIMEOUT = 408;
    public const int CONFLICT = 409;
    public const int GONE = 410;
    public const int LENGTH_REQUIRED = 411;
    public const int PRECONDITION_FAILED = 412;
    public const int PAYLOAD_TOO_LARGE = 413;
    public const int URI_TOO_LONG = 414;
    public const int UNSUPPORTED_MEDIA_TYPE = 415;
    public const int RANGE_NOT_SATISFIABLE = 416;
    public const int EXPECTATION_FAILED = 417;
    public const int I_AM_A_TEAPOT = 418;
    public const int PAGE_EXPIRED = 419;
    public const int MISDIRECTED_REQUEST = 421;
    public const int UNPROCESSABLE_ENTITY = 422;
    public const int LOCKED = 423;
    public const int FAILED_DEPENDENCY = 424;
    public const int TOO_EARLY = 425;
    public const int UPGRADE_REQUIRED = 426;
    public const int PRECONDITION_REQUIRED = 428;
    public const int TOO_MANY_REQUESTS = 429;
    public const int REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const int LOGIN_TIME_OUT = 440;
    public const int NO_RESPONSE = 444;
    public const int RETRY_WITH = 449;
    public const int BLOCKED_BY_WINDOWS_PARENTAL_CONTROL = 450;
    public const int UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const int CLIENT_CLOSED_THE_CONNECTION = 460;
    public const int X_FORWARDED_FOR_TOO_LARGE = 463;
    public const int REQUEST_HEADER_TOO_LARGE = 494;
    public const int SSL_CERTIFICATE_ERROR = 495;
    public const int SSL_CERTIFICATE_REQUIRED = 496;
    public const int HTTP_REQUEST_SENT_TO_HTTPS_PORT = 497;
    public const int INVALID_TOKEN = 498;
    public const int TOKEN_REQUIRED = 499;

    /**
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#5xx_server_errors
     */
    public const int INTERNAL_SERVER_ERROR = 500;
    public const int NOT_IMPLEMENTED = 501;
    public const int BAD_GATEWAY = 502;
    public const int SERVICE_UNAVAILABLE = 503;
    public const int GATEWAY_TIMEOUT = 504;
    public const int HTTP_VERSION_NOT_SUPPORTED = 505;
    public const int VARIANT_ALSO_NEGOTIATES = 506;
    public const int INSUFFICIENT_STORAGE = 507;
    public const int LOOP_DETECTED = 508;
    public const int BANDWIDTH_LIMIT_EXCEEDED = 509;
    public const int NOT_EXTENDED = 510;
    public const int NETWORK_AUTHENTICATION_REQUIRED = 511;
    public const int WEB_SERVER_RETURNED_AN_UNKNOWN_ERROR = 520;
    public const int WEB_SERVER_IS_DOWN = 521;
    public const int CONNECTION_TIMED_OUT = 522;
    public const int ORIGIN_IS_UNREACHABLE = 523;
    public const int A_TIMEOUT_OCCURRED = 524;
    public const int SSL_HANDSHAKE_FAILED = 525;
    public const int INVALID_SSL_CERTIFICATE = 526;
    public const int RAILGUN_ERROR = 527;
    public const int SITE_IS_OVERLOADED = 529;
    public const int SITE_IS_FROZEN = 530;
    public const int NETWORK_READ_TIMEOUT_ERROR = 598;

    private static array $messages = [
        // [Informational 1xx]
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // [Successful 2xx]
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // [Redirection 3xx]
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // [Client Error 4xx]
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        // [Server Error 5xx]
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended (OBSOLETED)',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    public static function getMessageForCode(int $code): string
    {
        return self::$messages[$code];
    }

    public static function isError($code): bool
    {
        return is_numeric($code) && $code >= self::BAD_REQUEST;
    }
}

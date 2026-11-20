<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Config;
use App\Core\Events\EventDispatcher;
use App\Core\Http\ApiResponse;
use ErrorException;
use Throwable;

final class Handler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public static function handle(Throwable $e): void
    {
        $status = 500;
        $code = 'server_error';

        if ($e instanceof NotFoundException) {
            $status = 404;
            $code = 'not_found';
        } elseif ($e instanceof ValidationException) {
            $status = 422;
            $code = 'validation_error';
        } elseif ($e instanceof PluginException) {
            $status = 500;
            $code = 'plugin_error';
        }

        $meta = [];
        if ($e instanceof ValidationException && $e->getErrors() !== []) {
            $meta['errors'] = $e->getErrors();
        }

        if ((bool) Config::get('app.debug', false)) {
            $meta['exception'] = get_class($e);
            $meta['file'] = $e->getFile() . ':' . $e->getLine();
            $meta['trace'] = array_slice(explode("\n", $e->getTraceAsString()), 0, 10);
        }

        error_log(sprintf('[Groo] %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api') || 
                 (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if (!$isApi) {
            EventDispatcher::instance()->dispatch('core.exception.render', [
                'exception' => $e,
                'status' => $status,
                'code' => $code,
                'meta' => $meta
            ]);
        }

        ApiResponse::error($e->getMessage() !== '' ? $e->getMessage() : 'Internal Server Error', $status, $code, $meta)->send();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditWriter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Wraps state-changing HTTP routes with an audit-trail entry.
 *
 * Captures: controller@action, authenticated user, HTTP method, response
 * status, and the resolved route name. Skips read-only requests (GET, HEAD,
 * OPTIONS) so we do not flood the audit log with read operations — those are
 * covered by access logs at the infrastructure layer.
 *
 * Registration: add to state-changing route groups in bootstrap/app.php via
 * withMiddleware()->appendToGroup('web', Audit::class) or apply inline to
 * specific route groups.
 */
final class Audit
{
    public function __construct(private readonly AuditWriter $auditWriter) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Audit only state-changing verbs.
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], strict: true)) {
            return $response;
        }

        // Only audit requests that resolved to a named route or action.
        $route = $request->route();
        if ($route === null) {
            return $response;
        }

        $action = $route->getActionName();
        $routeName = $route->getName() ?? $action;
        $statusCode = $response->getStatusCode();

        try {
            $this->auditWriter->record(
                action: 'http.request',
                subject: null,
                context: [
                    'method'      => $request->method(),
                    'route'       => $routeName,
                    'action'      => $action,
                    'status_code' => $statusCode,
                    'ip'          => $request->ip(),
                ],
            );
        } catch (Throwable $e) {
            // Log the failure but do not swallow it on non-2xx responses
            // where the business operation already failed anyway.
            if ($statusCode >= 200 && $statusCode < 300) {
                // On a successful business operation, an audit-write failure is
                // a critical error — re-throw so the caller sees a 500.
                throw $e;
            }

            Log::error('AuditMiddleware: failed to write audit event', [
                'exception' => $e->getMessage(),
                'route'     => $routeName,
            ]);
        }

        return $response;
    }
}

<?php
/**
 * HotelOS - Simple Request Router
 * 
 * Lightweight router for SPA-like navigation without frameworks
 * Supports clean URLs and HTMX/Alpine fetch requests
 */

declare(strict_types=1);

namespace HotelOS\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $basePath = '';

    /**
     * Set base path for subdirectory installations
     */
    public function setBasePath(string $path): self
    {
        $this->basePath = rtrim($path, '/');
        return $this;
    }

    /**
     * Register a GET route
     */
    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add a route to the collection
     */
    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): self
    {
        $pattern = $this->pathToRegex($path);
        
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Register global middleware
     */
    public function middleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Convert path to regex pattern
     * Supports: {param} for required params, {param?} for optional
     */
    private function pathToRegex(string $path): string
    {
        // Escape special regex chars
        $pattern = preg_quote($path, '#');
        
        // Convert {param} to named capture groups
        $pattern = preg_replace(
            '/\\\{([a-zA-Z_]+)\\\}/',
            '(?P<$1>[^/]+)',
            $pattern
        );
        
        // Convert {param?} to optional capture groups
        $pattern = preg_replace(
            '/\\\{([a-zA-Z_]+)\\\?\\\}/',
            '(?P<$1>[^/]*)?',
            $pattern
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch the request to matching route
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getRequestUri();

        // Handle preflight CORS requests
        if ($method === 'OPTIONS') {
            $this->handleCors();
            return;
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run global middleware
                foreach ($this->middleware as $mw) {
                    $result = $mw($params);
                    if ($result === false) {
                        return; // Middleware halted request
                    }
                }

                // Run route-specific middleware
                foreach ($route['middleware'] as $mw) {
                    $result = $mw($params);
                    if ($result === false) {
                        return;
                    }
                }

                // Execute handler
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // No route matched
        $this->notFound();
    }

    /**
     * Get clean request URI
     */
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove base path
        if ($this->basePath && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Normalize
        $uri = '/' . trim($uri, '/');
        
        return $uri ?: '/';
    }

    /**
     * Execute route handler
     */
    private function executeHandler(callable|array $handler, array $params): void
    {
        try {
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class();
                $controller->$method($params);
            } else {
                $handler($params);
            }
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        
        if ($this->isApiRequest()) {
            $this->json(['error' => 'Not Found'], 404);
        } else {
            include __DIR__ . '/../views/errors/404.php';
        }
    }

    /**
     * Handle exceptions
     */
    private function handleError(\Exception $e): void
    {
        error_log("Router Error: " . $e->getMessage());
        
        http_response_code(500);
        
        $config = require __DIR__ . '/../config/app.php';
        
        if ($config['debug']) {
            if ($this->isApiRequest()) {
                $this->json([
                    'error'   => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ], 500);
            } else {
                echo '<h1>Error</h1>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
        } else {
            if ($this->isApiRequest()) {
                $this->json(['error' => 'Internal Server Error'], 500);
            } else {
                include __DIR__ . '/../views/errors/500.php';
            }
        }
    }

    /**
     * Handle CORS preflight
     */
    private function handleCors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        http_response_code(204);
    }

    /**
     * Check if request expects JSON response
     */
    private function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        return str_contains($accept, 'application/json') 
            || strtolower($xhr) === 'xmlhttprequest';
    }

    /**
     * Send JSON response
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * Get POST/PUT body as array
     */
    public static function getBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $body = file_get_contents('php://input');
            return json_decode($body, true) ?? [];
        }
        
        return $_POST;
    }

    /**
     * Validate required fields
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $rule);
            
            foreach ($ruleList as $r) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $r, 2), 2, null);
                
                $error = match($ruleName) {
                    'required' => empty($value) ? "{$field} is required" : null,
                    'email'    => !filter_var($value, FILTER_VALIDATE_EMAIL) ? "Invalid email" : null,
                    'min'      => strlen($value) < (int)$ruleParam ? "{$field} must be at least {$ruleParam} characters" : null,
                    'max'      => strlen($value) > (int)$ruleParam ? "{$field} must be at most {$ruleParam} characters" : null,
                    default    => null,
                };
                
                if ($error) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }
        
        return $errors;
    }
}

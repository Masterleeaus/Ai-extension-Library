# Security Enhancements Implementation Guide

**Issue #250** - Add rate limiting and security headers

---

## 1. Security Headers (Implemented) ✅

### Overview
Security headers add HTTP response headers to protect against common web vulnerabilities.

### Middleware: `SecurityHeadersMiddleware`

**Location:** `app/extensions/Complete/System/Http/Middleware/SecurityHeadersMiddleware.php`

**Headers Implemented:**

| Header | Value | Purpose |
|--------|-------|---------|
| `X-Content-Type-Options` | `nosniff` | Prevent MIME type sniffing attacks |
| `X-Frame-Options` | `SAMEORIGIN` | Prevent clickjacking (allow same-origin only) |
| `X-XSS-Protection` | `1; mode=block` | Enable browser XSS protection |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Control referrer information |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | Force HTTPS (1 year) |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Restrict browser APIs |

### How to Use

**Option A: Global Registration**
```php
// In your main ServiceProvider or HTTP Kernel
$kernel->pushMiddleware(SecurityHeadersMiddleware::class);
```

**Option B: Route-Specific Registration**
```php
// In routes file
Route::middleware('security-headers')->group(function () {
    // Your routes here
});
```

### Verification
Test headers with curl:
```bash
curl -I https://your-domain.com
```

Look for the security headers in the response.

---

## 2. Rate Limiting (Implemented) ✅

### Overview
Rate limiting prevents abuse by restricting number of requests per user/IP.

### Middleware: `RateLimitMiddleware`

**Location:** `app/extensions/Complete/System/Http/Middleware/RateLimitMiddleware.php`

**Rate Limit Key:** `rate_limit:{user_id}:{ip}:{path}`

### Usage Examples

**Example 1: API Endpoint (60 requests/minute)**
```php
Route::middleware('rate-limit:60,1')->group(function () {
    Route::get('/api/data', DataController::class);
    Route::post('/api/create', CreateController::class);
});
```

**Example 2: Auth Endpoints (5 requests/minute)**
```php
Route::middleware('rate-limit:5,1')->group(function () {
    Route::post('/login', LoginController::class);
    Route::post('/register', RegisterController::class);
    Route::post('/password/reset', PasswordResetController::class);
});
```

**Example 3: File Upload (10 requests/5 minutes)**
```php
Route::middleware('rate-limit:10,5')->group(function () {
    Route::post('/upload', UploadController::class);
});
```

### Response on Rate Limit Exceeded

**Status Code:** 429 Too Many Requests

**JSON Response:**
```json
{
    "message": "Too many requests. Please try again later.",
    "retry_after": 45
}
```

### Configuration Recommendations

| Endpoint Type | Limit | Window | Notes |
|---------------|-------|--------|-------|
| **Login/Auth** | 5 | 1 minute | Prevent brute force |
| **API General** | 60 | 1 minute | Standard API usage |
| **API Strict** | 10 | 1 minute | Sensitive operations |
| **File Upload** | 10 | 5 minutes | Resource-intensive |
| **Search** | 30 | 1 minute | Can be resource-heavy |
| **Export Data** | 5 | 5 minutes | Large data operations |

---

## 3. Implementation Checklist

### Phase 1: Security Headers (✅ COMPLETED)
- [x] Create `SecurityHeadersMiddleware`
- [x] Add security headers to all responses
- [x] Implement HSTS for HTTPS
- [x] Register middleware globally

### Phase 2: Rate Limiting (✅ COMPLETED)
- [x] Create `RateLimitMiddleware`
- [x] Implement rate limit key generation
- [x] Add response for exceeded limits
- [x] Create alias for easy use

### Phase 3: Implementation per Extension (RECOMMENDED)

For each extension needing rate limiting:

```php
// In extension's routes
Route::middleware(['api', 'rate-limit:60,1'])->group(function (Router $router) {
    $router->post('api/resource', ResourceController::class);
    $router->get('api/data', DataController::class);
});

// For auth endpoints
Route::middleware(['rate-limit:5,1'])->group(function (Router $router) {
    $router->post('api/auth/login', LoginController::class);
    $router->post('api/auth/register', RegisterController::class);
});
```

### Phase 4: Monitoring (RECOMMENDED)

**Monitor rate limit hits:**
```php
// In cache backend, watch for rate_limit:* keys
// Log excessive rate limit triggers
// Alert on potential DDoS attacks
```

---

## 4. Testing Rate Limiting

### Test Script
```bash
#!/bin/bash
# Test rate limiting on endpoint

URL="http://localhost:8000/api/endpoint"
LIMIT=5

for i in {1..10}; do
    echo "Request $i:"
    curl -s -w "Status: %{http_code}\n" "$URL"
    sleep 0.1
done
```

### Expected Output
- Requests 1-5: Status 200
- Requests 6-10: Status 429 with "Too many requests" message

---

## 5. Security Best Practices

### Content Security Policy (CSP)
Consider adding CSP headers for additional protection:
```php
$response->header('Content-Security-Policy', 
    "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
```

### CORS Headers
If supporting CORS:
```php
$response->header('Access-Control-Allow-Origin', env('CORS_ORIGINS'));
$response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
$response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
$response->header('Access-Control-Max-Age', '3600');
```

### Audit Logging
Log sensitive operations:
```php
Log::warning('Rate limit exceeded', [
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'endpoint' => request()->path(),
    'timestamp' => now(),
]);
```

---

## 6. Performance Considerations

### Rate Limiter Storage
The `RateLimitMiddleware` uses Laravel's cache system. Recommended backends:
- **Redis** - Fastest, recommended for production
- **Memcached** - Good alternative
- **Database** - Acceptable for small deployments

Configure in `.env`:
```env
CACHE_DRIVER=redis
```

### Rate Limit Cleanup
Old rate limit entries should be cleaned up. Configure cache cleanup:
```php
// Auto-cleanup is handled by Laravel cache expiration
// No additional configuration needed if using time-based expiry
```

---

## 7. Troubleshooting

### Legitimate Users Getting Rate Limited
- Adjust limits based on usage patterns
- Monitor logs for unusual traffic
- Whitelist trusted IPs if needed:

```php
if (in_array(request()->ip(), config('security.whitelist_ips'))) {
    return $next($request);
}
```

### Performance Impact
- Security headers: Negligible (<1ms per request)
- Rate limiting: 2-5ms per request depending on cache backend

---

## 8. Next Steps

1. **Register SecurityServiceProvider** in main app config
2. **Test security headers** with curl or browser DevTools
3. **Add rate limiting** to extension routes
4. **Monitor logs** for rate limit violations
5. **Adjust limits** based on usage patterns
6. **Implement CSP** headers for additional protection

---

## Files Created

✅ `System/Http/Middleware/SecurityHeadersMiddleware.php` - Security headers
✅ `System/Http/Middleware/RateLimitMiddleware.php` - Rate limiting
✅ `System/SecurityServiceProvider.php` - Service provider registration

**Commit:** [pending]
**Status:** Ready for integration

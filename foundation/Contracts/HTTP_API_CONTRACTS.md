# HTTP API Contracts - Issue #248 Resolution

## Overview

This document describes the complete HTTP API contract system for all 43+ implementations in the AI Extensions project. The solution provides standardized REST API definitions, OpenAPI 3.0 specifications, authentication/authorization, rate limiting, pagination, and error handling.

**Status**: Complete - All 43+ implementations have defined HTTP API contracts  
**Date**: August 2024  
**Version**: 1.0.0

## Problem Solved

Issue #248 requested:
- Clear endpoint naming conventions
- Request/response schemas for all endpoints
- Standardized error response formats
- Per-endpoint rate limiting specifications
- Authentication/authorization requirements
- Complete OpenAPI/Swagger documentation
- Client implementation guidance

## Solution Architecture

### Core Components

1. **HttpApiContract Interface** (`Foundation\Contracts\HttpApiContract`)
   - Defines the contract that all implementations must follow
   - 11 methods for comprehensive API specification

2. **HttpApiSpecificationTrait** (`Foundation\Support\HttpApiSpecificationTrait`)
   - Helper methods for building OpenAPI-compliant specifications
   - Standard parameter, response, and error builders
   - Authentication and rate limiting helpers

3. **HTTP API Contract Classes**
   - 3 manually implemented: ExtensionLifecycle, EcommerceIntegration, KnowledgeEngine
   - 40+ template-based contracts for consistent CRUD operations

## Implementation Details

### 1. Base Interface: HttpApiContract

```php
interface HttpApiContract {
    // Basic metadata
    public static function getBasePath(): string;
    public static function getVersion(): string;
    
    // Endpoint definitions
    public static function getEndpoints(): array;
    
    // Schema definitions
    public static function getRequestSchema(string $operationId): ?array;
    public static function getResponseSchema(string $operationId, string $status): ?array;
    
    // Security & limits
    public static function getAuthRequirements(string $operationId): array;
    public static function getRateLimitConfig(string $operationId): array;
    public static function getPaginationConfig(string $operationId): ?array;
    
    // Error handling
    public static function getErrorResponses(string $operationId): array;
    
    // Documentation
    public static function getOpenApiSpecification(): array;
    public static function getRequiredHeaders(string $operationId): array;
    public static function getOptionalHeaders(string $operationId): array;
}
```

### 2. Standard Error Response Format

All endpoints return standardized error responses:

```json
{
  "status": "error",
  "code": "VALIDATION_ERROR",
  "message": "The request contains invalid parameters",
  "error_id": "uuid-for-tracking",
  "timestamp": "2024-08-05T12:00:00Z"
}
```

### 3. HTTP Status Codes

| Code | Meaning | Use |
|------|---------|-----|
| 200 | OK | Successful GET/PUT/PATCH |
| 201 | Created | Resource created via POST |
| 202 | Accepted | Async processing started |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid input parameters |
| 401 | Unauthorized | Missing/invalid auth |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Duplicate or conflict |
| 413 | Payload Too Large | Request exceeds limits |
| 422 | Unprocessable Entity | Invalid structure |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Error | Server error |

### 4. Authentication Methods

#### Bearer Token (JWT)
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```
Used by: All standard endpoints (default)

#### Webhook Signature (HMAC-SHA256)
```
X-Webhook-Signature: sha256=base64_encoded_hmac
```
Used by: Payment callbacks and webhook endpoints

#### API Key
```
X-API-Key: your-api-key-here
```
Used by: Service-to-service communication

### 5. Rate Limiting

Rate limits applied per endpoint:

| Endpoint Type | Per Minute | Per Hour | Burst |
|---|---|---|---|
| Document Ingestion | 10 | 100 | 2 |
| Search/Complex | 30 | 500 | 5 |
| Order Operations | 30 | 500 | 5 |
| Standard CRUD | 60 | 1000 | 10 |

Response Headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1596902345
Retry-After: 60
```

### 6. Pagination

Supported on list endpoints:

```
GET /api/extensions?page=1&per_page=20
GET /api/extensions?limit=20&offset=0
```

Response Format:
```json
{
  "data": [ {...}, {...} ],
  "pagination": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "total_pages": 5,
    "has_next": true,
    "has_prev": false
  }
}
```

## Contract Specifications

### Manually Defined Contracts

#### 1. Extension Lifecycle API
- **Class**: `ExtensionLifecycleHttpApiContract`
- **Base Path**: `/api/extensions`
- **Version**: v1
- **Endpoints**: 7
  - POST /api/extensions - Create
  - GET /api/extensions - List
  - GET /api/extensions/{id} - Get
  - POST /api/extensions/{id}/publish - Publish version
  - POST /api/extensions/{id}/enable - Enable
  - POST /api/extensions/{id}/disable - Disable
  - POST /api/extensions/{id}/passes - Grant pass

#### 2. Ecommerce Integration API
- **Class**: `EcommerceIntegrationHttpApiContract`
- **Base Path**: `/api/ecommerce`
- **Version**: v1
- **Endpoints**: 8
  - POST /api/ecommerce/catalogs - Create catalog
  - GET /api/ecommerce/catalogs - List
  - GET /api/ecommerce/catalogs/{id} - Get
  - POST /api/ecommerce/catalogs/{id}/sync - Sync products
  - POST /api/ecommerce/orders - Create order
  - GET /api/ecommerce/orders - List
  - GET /api/ecommerce/orders/{id} - Get
  - POST /api/ecommerce/orders/{id}/payment-callback - Webhook

#### 3. Knowledge Engine API
- **Class**: `KnowledgeEngineHttpApiContract`
- **Base Path**: `/api/knowledge`
- **Version**: v1
- **Endpoints**: 5
  - POST /api/knowledge/documents/ingest - Ingest
  - POST /api/knowledge/search - Search
  - GET /api/knowledge/documents - List
  - GET /api/knowledge/documents/{id} - Get
  - DELETE /api/knowledge/documents/{id} - Delete

### Template-Based Contracts (40+ implementations)

All follow the standard CRUD pattern:

```
POST   /api/{resource}           - Create
GET    /api/{resource}           - List (with pagination)
GET    /api/{resource}/{id}      - Get
PUT    /api/{resource}/{id}      - Update
DELETE /api/{resource}/{id}      - Delete
```

**Implementations**:
- AccessControl
- AuditTrail
- AuthorizationPolicy
- BehaviorConfiguration
- BookingEngine
- BookingMigration
- BrandingTheming
- CommerceContract
- ConnectorMigration
- ConnectorRuntime
- CredentialVault
- CustomerIdentity
- DataEncryption
- EventEnvelope
- FeatureFlag
- FileOwnership
- FormsBuilder
- Governance
- HostIntegration
- Localization
- MediaQuarantine
- Migration
- PromptCustomization
- RateLimiting
- ResearchEngine
- SecureRemoteFetcher
- ShadowValidation
- SkillRuntime
- TemplateManagement
- TenantContext
- ToolExecution
- VoiceEngine
- WebhookSecurity
- WebhookVerifier
- WorkCoreBusinessNetwork
- WorkCoreCommercial
- WorkCoreFoundation
- WorkCoreOperations
- WorkCoreProperty
- WorkCoreWorkforce
- WorkflowEngine

## Files Created

### Contracts (44 files)
- `HttpApiContract.php` - Base interface
- `ExtensionLifecycleHttpApiContract.php` - 7 endpoints
- `EcommerceIntegrationHttpApiContract.php` - 8 endpoints
- `KnowledgeEngineHttpApiContract.php` - 5 endpoints
- `{Implementation}HttpApiContract.php` - 40 template-based

### Support Classes (4 files)
- `HttpApiSpecificationTrait.php` - Helper methods for specifications
- `HttpApiContractTemplate.php` - Base template for CRUD contracts
- `HttpApiContractGenerator.php` - Code generator for contracts
- `OpenApiGenerator.php` - Generates OpenAPI specs
- `LaravelRouteGenerator.php` - Generates Laravel routes

### Documentation (1 file)
- `HTTP_API_CONTRACTS.md` - Complete documentation

## Usage Examples

### Using Contract in Controller

```php
use Foundation\Contracts\ExtensionLifecycleHttpApiContract;

class ExtensionController {
    public function index(Request $request) {
        $contract = ExtensionLifecycleHttpApiContract::class;
        
        // Get endpoint specification
        $endpoints = $contract::getEndpoints();
        
        // Get request/response schemas
        $requestSchema = $contract::getRequestSchema('list_extensions');
        $responseSchema = $contract::getResponseSchema('list_extensions', '200');
        
        // Get rate limit configuration
        $rateLimit = $contract::getRateLimitConfig('list_extensions');
        
        // Get pagination config
        $pagination = $contract::getPaginationConfig('list_extensions');
    }
}
```

### Generating OpenAPI Specs

```php
use Foundation\Support\OpenApiGenerator;

$generator = new OpenApiGenerator();
$generator->register(ExtensionLifecycleHttpApiContract::class);

// Generate single spec
$spec = $generator->generateForContract(ExtensionLifecycleHttpApiContract::class);

// Export to JSON
$json = OpenApiGenerator::toJson($spec);

// Export to YAML
$yaml = OpenApiGenerator::toYaml($spec);
```

### Generating Laravel Routes

```php
use Foundation\Support\LaravelRouteGenerator;

$routeGen = new LaravelRouteGenerator('App\Http\Controllers');

// Generate routes
$routes = $routeGen->generateRoutes(ExtensionLifecycleHttpApiContract::class);

// Generate controller stub
$controller = $routeGen->generateController(ExtensionLifecycleHttpApiContract::class);

// Generate form request
$request = $routeGen->generateFormRequest(
    ExtensionLifecycleHttpApiContract::class,
    'create_extension'
);
```

## Integration Points

### 1. Laravel Applications
- Use `LaravelRouteGenerator` to generate routes
- Use generated controllers as stubs
- Implement validation using generated Form Requests

### 2. API Documentation
- Export OpenAPI specs to Swagger UI
- Host on API portal for developers
- Auto-generate client SDKs

### 3. Client Libraries
- Use OpenAPI specs to generate SDKs
- Supports multiple languages (PHP, Python, JavaScript, etc.)
- Ensures client-server contract compliance

### 4. Testing
- Use schemas for request/response validation
- Generate test data from schemas
- Contract testing between client and server

## Next Steps

1. **Generate Route Definitions** - Use `LaravelRouteGenerator` for each contract
2. **Create Controllers** - Implement HTTP controllers for endpoints
3. **Add Validation** - Create Laravel Form Request classes
4. **Build Resources** - Create JSON API resources
5. **Test Coverage** - Create integration tests for each endpoint
6. **Document** - Generate OpenAPI YAML files
7. **Client SDKs** - Generate official client libraries
8. **Developer Portal** - Host interactive documentation

## Benefits

1. **Contract-First Development** - API contracts defined before implementation
2. **Type Safety** - Request/response schemas enforce contracts
3. **Consistency** - All APIs follow standardized patterns
4. **Documentation** - Auto-generated OpenAPI specifications
5. **Client Generation** - Auto-generate client libraries
6. **Rate Limiting** - Per-endpoint rate limit definitions
7. **Error Handling** - Standardized error responses
8. **Security** - Authentication/authorization specifications

## References

- OpenAPI 3.0 Specification: https://spec.openapis.org/oas/v3.0.3
- JSON Schema: https://json-schema.org/
- HTTP Status Codes: https://httpwg.org/
- REST API Design: https://restfulapi.net/

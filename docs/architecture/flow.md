# Architecture Flow - Service & Repository Pattern

## Proposed Flow

```
User Request
    ↓
[1] Controller (HTTP Layer)
    ↓
[2] Service (Business Logic Layer)
    ├─→ [3] Repository (ONLY if fetching from Database)
    ├─→ Other Service(s) (if need other business logic)
    ├─→ SDK/External APIs (direct call for external data)
    └─→ Business Logic Implementation
    ↓
[4] Return processed data to Controller
    ↓
[5] Response via Resource (Model-based) OR raw JSON (non-Model data)
    ↓
User Response
```

## Layer Responsibilities

### [1] Controller - HTTP Concerns Only

**Responsibilities:**
- Receive HTTP requests
- Delegate to FormRequest for validation
- Call Service methods
- Transform Service response using Resource OR raw JSON
- Handle HTTP-specific concerns (headers, status codes)

**NOT Responsible For:**
- Business logic
- Direct database access
- External API calls

**Coverage Target:** 90%

**Example with Resource (Model-based response):**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\WordPress\Http\Requests\StoreCategoryRequest;
use Modules\WordPress\Http\Resources\CategoryResource;
use Modules\WordPress\Services\CategoryService;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $service
    ) {}

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->create(
            $request->validated(),
            $request->user()
        );

        // Use Resource for Model-based data
        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    public function index(): AnonymousResourceCollection
    {
        $categories = $this->service->list();

        // Use Resource collection for Model-based data
        return CategoryResource::collection($categories);
    }
}
```

**Example with raw JSON (non-Model response):**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\WordPress\Http\Requests\StoreWpTokenRequest;
use Modules\WordPress\Services\TokenService;

final class TokenController extends Controller
{
    public function __construct(
        private readonly TokenService $service
    ) {}

    public function store(StoreWpTokenRequest $request): JsonResponse
    {
        // Service returns array with mixed data (not a Model)
        $result = $this->service->authenticate(
            $request->validated(),
            $request->user()
        );

        // Use raw JSON for non-Model data
        return ApiResponse::success(
            code: 'wordpress.token_created',
            message: 'Token created successfully.',
            data: $result, // ['remembered' => true, 'masked_token' => '***', etc.]
            status: 201
        );
    }
}
```

### [2] Service - One Business Logic = One Service Class

**CRITICAL: 1 Service = 1 Business Logic Domain**

**What is a Service?**
- A Service is a class that handles ONE specific business logic
- Different business logic = Different service class
- Examples:
  - `CategoryService` - Handles category business logic ONLY
  - `PostService` - Handles post business logic ONLY
  - `TokenService` - Handles authentication/token business logic ONLY
  - `MediaService` - Handles media/upload business logic ONLY

**Responsibilities:**
- Implement ONE specific business logic domain
- Use Repository ONLY when fetching from local database
- Call SDK/External APIs directly (no repository for external data)
- Call other Services when needing DIFFERENT business logic
- Transform data for controller consumption
- Perform audit logging (via ActionLogger)
- Handle business exceptions for THIS domain
- Return processed data (Model or array)

**NOT Responsible For:**
- HTTP concerns (status codes, headers)
- Response formatting (Resource/JSON - that's controller's job)
- Multiple unrelated business logics (create separate services)

**Coverage Target:** 95% (Core services)

**Example Service using Repository (for database access):**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use App\Logging\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\WordPress\Models\WpToken;
use Modules\WordPress\Repositories\Contracts\TokenRepositoryContract;
use Modules\WordPress\Services\WordPress\Contracts\SdkContract;

/**
 * TokenService - Handles authentication/token business logic ONLY
 * 
 * This service is responsible for:
 * - WordPress JWT authentication
 * - Token storage (remember functionality)
 * - Token retrieval and masking
 * 
 * This service does NOT handle:
 * - Category logic (use CategoryService)
 * - Post logic (use PostService)
 * - Media logic (use MediaService)
 */
final class TokenService
{
    public function __construct(
        private readonly TokenRepositoryContract $repository, // For DB access
        private readonly SdkContract $sdk,                    // For external API
        private readonly ActionLogger $logger
    ) {}

    /**
     * Authenticate user with WordPress and optionally remember token
     * 
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function authenticate(array $credentials, ?Authenticatable $actor = null): array
    {
        $remember = (bool) ($credentials['remember'] ?? false);

        // Business logic: Call external API directly (no repository)
        $response = $this->sdk->token(
            $credentials['username'],
            $credentials['password']
        );

        $tokenModel = null;

        // Business logic: Save to database if remember is true
        if ($remember) {
            // Use repository for database operations
            $tokenModel = $this->repository->updateOrCreate(
                $credentials['username'],
                [
                    'token' => $response['token'] ?? '',
                    'payload' => $response,
                ]
            );

            // Business rule: audit logging
            $this->logger->log(
                operation: 'wordpress.token.remembered',
                actor: $actor,
                before: [],
                after: ['username' => $credentials['username']],
                metadata: ['ip' => request()->ip()]
            );
        }

        // Business logic: Return processed data
        return [
            'id' => $tokenModel?->id,
            'remembered' => $remember,
            'masked_token' => $remember ? $this->maskToken($tokenModel?->token) : null,
            'username' => $remember ? $credentials['username'] : null,
        ];
    }

    /**
     * Get the latest remembered token
     * 
     * @return array<string, mixed>
     */
    public function getRememberedToken(): array
    {
        $token = $this->repository->findLatest();

        if ($token === null) {
            return [
                'remembered' => false,
                'masked_token' => null,
                'username' => null,
            ];
        }

        return [
            'remembered' => true,
            'masked_token' => $this->maskToken($token->token),
            'username' => $token->username,
        ];
    }

    /**
     * Clear remembered token
     */
    public function clearRememberedToken(): bool
    {
        $token = $this->repository->findLatest();

        if ($token === null) {
            return false;
        }

        return $this->repository->delete($token);
    }

    /**
     * Business logic: Mask token for security
     */
    private function maskToken(?string $token): ?string
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $length = strlen($token);
        if ($length <= 8) {
            return str_repeat('*', $length - 1) . substr($token, -1);
        }

        return substr($token, 0, 4) . str_repeat('*', $length - 7) . substr($token, -3);
    }
}
```

**Example Service calling another Service:**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use App\Logging\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\WordPress\Models\Post;
use Modules\WordPress\Repositories\Contracts\PostRepositoryContract;
use Modules\WordPress\Services\WordPress\Contracts\SdkContract;

/**
 * PostService - Handles post business logic ONLY
 * 
 * This service is responsible for:
 * - Creating posts
 * - Updating posts
 * - Validating post data
 * - Post-category relationships
 * 
 * This service does NOT handle:
 * - Category CRUD (delegates to CategoryService)
 * - Token logic (delegates to TokenService)
 * - Media uploads (delegates to MediaService)
 */
final class PostService
{
    public function __construct(
        private readonly PostRepositoryContract $repository,
        private readonly CategoryService $categoryService,  // Different business logic
        private readonly SdkContract $sdk,
        private readonly ActionLogger $logger
    ) {}

    /**
     * Create post with category validation
     * 
     * @param array<string, mixed> $data
     */
    public function createWithCategories(array $data, ?Authenticatable $actor = null): Post
    {
        // Business logic: Validate categories exist
        // Call DIFFERENT service for category business logic
        if (! empty($data['categories'])) {
            foreach ($data['categories'] as $categoryId) {
                $category = $this->categoryService->findById($categoryId);
                if ($category === null) {
                    throw new \InvalidArgumentException("Category {$categoryId} not found");
                }
            }
        }

        // Business logic: Create post via external API
        $postData = $this->sdk->createPost($data);

        // Business logic: Save to local database via repository
        $post = $this->repository->create($postData);

        // Business rule: audit logging
        $this->logger->log(
            operation: 'wordpress.post.created',
            actor: $actor,
            before: [],
            after: $post->toArray(),
            metadata: ['source' => 'wordpress']
        );

        return $post;
    }

    /**
     * Update post
     * 
     * @param array<string, mixed> $data
     */
    public function update(int $postId, array $data, ?Authenticatable $actor = null): Post
    {
        // Business logic for THIS domain only
        $existing = $this->repository->findById($postId);
        
        if ($existing === null) {
            throw new \InvalidArgumentException("Post {$postId} not found");
        }

        $before = $existing->toArray();
        
        // Update via external API
        $postData = $this->sdk->updatePost($postId, $data);
        
        // Update local database
        $post = $this->repository->update($existing, $postData);

        $this->logger->log('wordpress.post.updated', $actor, $before, $post->toArray());

        return $post;
    }
}
```

**Example Service with NO Repository (external API only):**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use Modules\WordPress\Services\WordPress\Contracts\SdkContract;

/**
 * CategoryService - Handles category business logic ONLY
 * 
 * This service is responsible for:
 * - Fetching categories from WordPress
 * - Creating categories
 * - Updating categories
 * - Deleting categories
 * 
 * This service does NOT handle:
 * - Post logic (use PostService)
 * - Token logic (use TokenService)
 * - Tag logic (use TagService)
 * 
 * Note: No repository - categories only exist in WordPress (external API)
 */
final class CategoryService
{
    public function __construct(
        private readonly SdkContract $sdk  // No repository - external API only
    ) {}

    /**
     * List categories with filters
     * 
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function list(array $filters = []): array
    {
        // Business logic: apply default filters
        $filters = array_merge([
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'name',
            'order' => 'asc',
        ], $filters);

        // Call external API directly (no repository needed)
        return $this->sdk->categories($filters);
    }

    /**
     * Find category by ID
     * 
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        try {
            // Call external API directly
            return $this->sdk->category($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create new category
     * 
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        // Business logic: validate parent exists if provided
        if (isset($data['parent']) && $data['parent'] > 0) {
            $parent = $this->findById($data['parent']);
            if ($parent === null) {
                throw new \InvalidArgumentException("Parent category {$data['parent']} not found");
            }
        }

        // Business logic: generate slug if missing
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        return $this->sdk->createCategory($data);
    }

    /**
     * Business logic helper: Generate slug from name
     */
    private function generateSlug(string $name): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }
}
```

**Example: Multiple Services, Each with ONE Business Logic**
```php
// ❌ WRONG - One service doing multiple business logics
final class WordPressService
{
    public function createPost() { }      // Post logic
    public function createCategory() { }  // Category logic
    public function uploadMedia() { }     // Media logic
    public function authenticate() { }    // Token logic
}

// ✅ CORRECT - Separate services for each business logic
final class PostService
{
    public function create() { }
    public function update() { }
    public function delete() { }
}

final class CategoryService
{
    public function create() { }
    public function update() { }
    public function delete() { }
}

final class MediaService
{
    public function upload() { }
    public function delete() { }
}

final class TokenService
{
    public function authenticate() { }
    public function remember() { }
    public function clear() { }
}
```

### [3] Repository - Database Access ONLY

**CRITICAL: Use Repository ONLY for local database operations**

**Responsibilities:**
- Provide clean interface for database operations
- Execute Eloquent queries
- Handle query building
- Return Models or null

**NOT Responsible For:**
- External API calls (services call SDK directly)
- Business logic
- Business rules validation
- Audit logging

**Coverage Target:** 80%

**Example Repository (Database only):**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Repositories;

use Modules\WordPress\Models\WpToken;
use Modules\WordPress\Repositories\Contracts\TokenRepositoryContract;

final class TokenRepository implements TokenRepositoryContract
{
    public function findLatest(): ?WpToken
    {
        return WpToken::query()
            ->latest('updated_at')
            ->first();
    }

    public function findByUsername(string $username): ?WpToken
    {
        return WpToken::query()
            ->where('username', $username)
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateOrCreate(string $username, array $data): WpToken
    {
        return WpToken::updateOrCreate(
            ['username' => $username],
            $data
        );
    }

    public function delete(WpToken $token): bool
    {
        return $token->delete() ?? false;
    }
}
```

### [4] SDK - External APIs (Called directly by Services)

**Services call SDKs directly - NO repository layer for external APIs**

**Example SDK:**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Services\WordPress;

use GuzzleHttp\Client;
use Modules\WordPress\Services\WordPress\Contracts\SdkContract;

final class Sdk implements SdkContract
{
    public function __construct(
        private readonly Client $client,
        private readonly string $baseUrl
    ) {}

    /**
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>
     */
    public function categories(array $query = []): array
    {
        $response = $this->client->get("{$this->baseUrl}/categories", [
            'query' => $query,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function category(int $id): array
    {
        $response = $this->client->get("{$this->baseUrl}/categories/{$id}");

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createCategory(array $data): array
    {
        $response = $this->client->post("{$this->baseUrl}/categories", [
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
```

### [5] Resources - Transform Models for API Response

**Use Resource when response data maps to a Model**

**Example Resource:**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\WordPress\Models\Post;

/**
 * @mixin Post
 */
final class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'author' => [
                'id' => $this->author_id,
                'name' => $this->author?->name,
            ],
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

**Example Resource with computed fields:**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\WordPress\Models\WpToken;

/**
 * @mixin WpToken
 */
final class WpTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'masked_token' => $this->maskToken($this->token),
            'expires_at' => $this->payload['exp'] ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function maskToken(string $token): string
    {
        $length = strlen($token);
        if ($length <= 8) {
            return str_repeat('*', $length - 1) . substr($token, -1);
        }

        return substr($token, 0, 4) . str_repeat('*', $length - 7) . substr($token, -3);
    }
}
```

### [6] Models - Database Eloquent Models
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Models;

use Illuminate\Database\Eloquent\Model;

final class WpToken extends Model
{
    protected $fillable = ['username', 'token', 'payload'];
    
    protected $casts = ['payload' => 'array'];
}
```

**Example Model:**
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'author_id',
        'wordpress_id',
    ];

    protected $casts = [
        'wordpress_id' => 'integer',
        'author_id' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
```

## Response Strategy: Resource vs Raw JSON

### ✅ Use Resource (PREFERRED) When:

1. **Data maps to an Eloquent Model**
   ```php
   // Model exists: Post, Category, Tag, WpToken
   return PostResource::make($post);
   return CategoryResource::collection($categories);
   ```

2. **Need consistent transformation across endpoints**
   ```php
   // Same Post structure everywhere
   PostResource::make($post); // show
   PostResource::collection($posts); // index
   ```

3. **Need conditional fields or relationships**
   ```php
   return [
       'id' => $this->id,
       'title' => $this->title,
       'author' => new AuthorResource($this->whenLoaded('author')),
       'admin_only' => $this->when($request->user()->isAdmin(), $this->secret),
   ];
   ```

4. **Need computed/derived fields**
   ```php
   return [
       'id' => $this->id,
       'full_name' => $this->first_name . ' ' . $this->last_name,
       'avatar_url' => $this->getAvatarUrl(),
   ];
   ```

### ⚠️ Use Raw JSON When:

1. **Response doesn't map to a Model**
   ```php
   // Authentication response (not a model)
   return ApiResponse::success('auth.success', 'Logged in', [
       'token' => $token,
       'expires_in' => 3600,
       'token_type' => 'Bearer',
   ]);
   ```

2. **Aggregated data from multiple sources**
   ```php
   // Dashboard stats from multiple tables
   return ApiResponse::success('dashboard.stats', 'Stats retrieved', [
       'total_posts' => $postCount,
       'total_users' => $userCount,
       'recent_activity' => $activities,
   ]);
   ```

3. **Simple confirmation responses**
   ```php
   // Delete confirmation
   return ApiResponse::success('post.deleted', 'Post deleted', [
       'deleted_id' => $id,
       'force' => true,
   ]);
   ```

4. **Status/health check responses**
   ```php
   return ApiResponse::success('health.ok', 'System healthy', [
       'status' => 'operational',
       'uptime' => $uptime,
       'version' => config('app.version'),
   ]);
   ```

## Complete Flow Examples

### Example 1: Create Post (with Resource)


```php
// 1. Controller receives request
final class PostController extends Controller
{
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->service->create($request->validated(), $request->user());
        
        // Use Resource - data maps to Post model
        return PostResource::make($post)
            ->response()
            ->setStatusCode(201);
    }
}

// 2. Service implements business logic
final class PostService
{
    public function __construct(
        private readonly PostRepositoryContract $repository,  // For DB
        private readonly CategoryService $categoryService,    // Other service
        private readonly SdkContract $sdk,                    // External API
        private readonly ActionLogger $logger
    ) {}
    
    public function create(array $data, ?Authenticatable $actor): Post
    {
        // Business logic: validate categories via another service
        foreach ($data['categories'] ?? [] as $categoryId) {
            $this->categoryService->findById($categoryId);
        }
        
        // Business logic: create in WordPress
        $wpPost = $this->sdk->createPost($data);
        
        // Business logic: save to local DB via repository
        $post = $this->repository->create([
            'title' => $wpPost['title']['rendered'],
            'wordpress_id' => $wpPost['id'],
            'author_id' => $actor?->id,
        ]);
        
        // Business rule: audit logging
        $this->logger->log('post.created', $actor, [], $post->toArray());
        
        return $post;
    }
}

// 3. Repository handles database access
final class PostRepository implements PostRepositoryContract
{
    public function create(array $data): Post
    {
        return Post::create($data);
    }
}

// 4. Resource transforms model for response
final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'wordpress_id' => $this->wordpress_id,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "title": "My Post",
        "wordpress_id": 123,
        "created_at": "2025-11-13T10:00:00Z"
    }
}
```

### Example 2: Authenticate (with raw JSON)

```php
// 1. Controller receives request
final class TokenController extends Controller
{
    public function store(StoreWpTokenRequest $request): JsonResponse
    {
        $result = $this->service->authenticate($request->validated(), $request->user());
        
        // Use raw JSON - response doesn't map to single model
        return ApiResponse::success(
            code: 'wordpress.token_created',
            message: 'Authentication successful.',
            data: $result,
            status: 201
        );
    }
}

// 2. Service implements business logic
final class TokenService
{
    public function __construct(
        private readonly TokenRepositoryContract $repository,  // For DB
        private readonly SdkContract $sdk,                    // External API
        private readonly ActionLogger $logger
    ) {}
    
    public function authenticate(array $credentials, ?Authenticatable $actor): array
    {
        // Business logic: authenticate with WordPress
        $response = $this->sdk->token(
            $credentials['username'],
            $credentials['password']
        );
        
        // Business logic: optionally save to DB
        $tokenModel = null;
        if ($credentials['remember']) {
            $tokenModel = $this->repository->updateOrCreate(
                $credentials['username'],
                ['token' => $response['token'], 'payload' => $response]
            );
            
            $this->logger->log('token.remembered', $actor, [], []);
        }
        
        // Return processed data (not a model - mixed data)
        return [
            'remembered' => (bool) $credentials['remember'],
            'masked_token' => $this->maskToken($response['token']),
            'expires_in' => 3600,
            'username' => $credentials['username'],
        ];
    }
}
```

**Response:**
```json
{
    "ok": true,
    "code": "wordpress.token_created",
    "status": 201,
    "message": "Authentication successful.",
    "data": {
        "remembered": true,
        "masked_token": "eyJ***xyz",
        "expires_in": 3600,
        "username": "admin"
    },
    "meta": {}
}
```

### Example 3: List Categories (external API, raw JSON)

```php
// 1. Controller
final class CategoryController extends Controller
{
    public function index(IndexCategoriesRequest $request): JsonResponse
    {
        $categories = $this->service->list($request->validated());
        
        // Raw JSON - data from external API (not local models)
        return ApiResponse::success(
            code: 'wordpress.categories.list',
            message: 'Categories retrieved.',
            data: ['items' => $categories],
            meta: ['count' => count($categories)]
        );
    }
}

// 2. Service calls SDK directly (no repository for external APIs)
final class CategoryService
{
    public function __construct(
        private readonly SdkContract $sdk  // Call SDK directly
    ) {}
    
    public function list(array $filters = []): array
    {
        // Business logic: apply defaults
        $filters = array_merge(['per_page' => 20], $filters);
        
        // Call external API directly (no repository)
        return $this->sdk->categories($filters);
    }
}
```

**Response:**
```json
{
    "ok": true,
    "code": "wordpress.categories.list",
    "status": 200,
    "message": "Categories retrieved.",
    "data": {
        "items": [
            {"id": 1, "name": "Technology", "slug": "technology"},
            {"id": 2, "name": "News", "slug": "news"}
        ]
    },
    "meta": {
        "count": 2
    }
}
```

## Architecture Decision Tree

### When to use Repository?

```
Does the service need to fetch/save to local database?
│
├─ YES → Use Repository
│   └─ Examples: User data, cached WordPress posts, stored tokens
│
└─ NO → Call SDK/API directly in Service
    └─ Examples: WordPress categories (external only), third-party APIs
```

### When to use Resource vs Raw JSON?

```
Does the response data map to an Eloquent Model?
│
├─ YES → Use Resource (PREFERRED)
│   ├─ Single model: PostResource::make($post)
│   └─ Collection: PostResource::collection($posts)
│
└─ NO → Use Raw JSON (ApiResponse)
    ├─ Authentication responses
    ├─ Aggregated stats
    ├─ Delete confirmations
    └─ Health checks
```

## Repository Contracts

**Create contracts ONLY for database repositories:**

```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Repositories\Contracts;

use Modules\WordPress\Models\Post;

interface PostRepositoryContract
{
    public function findById(int $id): ?Post;
    
    /**
     * @param array<string, mixed> $filters
     * @return \Illuminate\Support\Collection<int, Post>
     */
    public function findAll(array $filters = []): \Illuminate\Support\Collection;
    
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Post;
    
    /**
     * @param array<string, mixed> $data
     */
    public function update(Post $post, array $data): Post;
    
    public function delete(Post $post): bool;
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Repositories\Contracts;

use Modules\WordPress\Models\WpToken;

interface TokenRepositoryContract
{
    public function findLatest(): ?WpToken;
    
    public function findByUsername(string $username): ?WpToken;
    
    /**
     * @param array<string, mixed> $data
     */
    public function updateOrCreate(string $username, array $data): WpToken;
    
    public function delete(WpToken $token): bool;
}
```

## Service Provider Registration

```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\WordPress\Repositories\Contracts\PostRepositoryContract;
use Modules\WordPress\Repositories\Contracts\TokenRepositoryContract;
use Modules\WordPress\Repositories\PostRepository;
use Modules\WordPress\Repositories\TokenRepository;

final class WordPressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository contracts (database only)
        $this->app->singleton(
            PostRepositoryContract::class,
            PostRepository::class
        );

        $this->app->singleton(
            TokenRepositoryContract::class,
            TokenRepository::class
        );
        
        // SDK is already registered in CoreServiceProvider
    }
}
```

## Key Principles Summary

### ✅ DO:

1. **Controller → Service flow**
   - Controllers call services
   - Controllers transform service data via Resource or ApiResponse

2. **Use Repository for database access**
   - ONLY when fetching/saving to local database
   - Repository returns Models

3. **Call SDK directly in Services**
   - NO repository layer for external APIs
   - Services call SDK directly

4. **Use Resources for Model data**
   - Preferred way to transform Models
   - Consistent structure across endpoints

5. **Use raw JSON for non-Model data**
   - Authentication responses
   - Mixed/aggregated data
   - Status responses

6. **Services call other Services**
   - When needing different business logic domains
   - Inject service dependencies

7. **Business logic lives in Services**
   - All business rules
   - All orchestration
   - All transformations

### ❌ DON'T:

1. **Don't create repositories for external APIs**
   - Services call SDK directly

2. **Don't put business logic in Controllers**
   - Controllers are thin HTTP handlers

3. **Don't put business logic in Repositories**
   - Repositories only handle data access

4. **Don't use raw JSON when Resource is possible**
   - Always prefer Resource for Model data

5. **Don't inject Models into Controllers**
   - Always go through Services

## Testing Strategy

### Controller Tests (90% coverage)
```php
public function test_store_creates_post_and_returns_resource(): void
{
    $service = Mockery::mock(PostService::class);
    $post = Post::factory()->make(['id' => 1]);
    $service->shouldReceive('create')->once()->andReturn($post);
    
    $this->app->instance(PostService::class, $service);
    
    $response = $this->postJson('/api/v1/posts', ['title' => 'Test']);
    
    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'title']]);
}
```

### Service Tests (95% coverage)
```php
public function test_create_calls_repository_and_logs_action(): void
{
    $repository = Mockery::mock(PostRepositoryContract::class);
    $repository->shouldReceive('create')->once()->andReturn(new Post(['id' => 1]));
    
    $logger = Mockery::mock(ActionLogger::class);
    $logger->shouldReceive('log')->once();
    
    $service = new PostService($repository, $categoryService, $sdk, $logger);
    $post = $service->create(['title' => 'Test'], null);
    
    $this->assertInstanceOf(Post::class, $post);
}
```

### Repository Tests (80% coverage)
```php
public function test_create_saves_post_to_database(): void
{
    $repository = new PostRepository();
    $post = $repository->create(['title' => 'Test', 'author_id' => 1]);
    
    $this->assertDatabaseHas('posts', ['title' => 'Test']);
    $this->assertInstanceOf(Post::class, $post);
}
```

## Migration Path from Current State

### Current Issues:
1. ❌ TokenController has business logic (should be in TokenService)
2. ❌ CategoryService calls SDK directly (correct per new architecture)
3. ❌ No Resources used (should use for Model data)

### Migration Steps:

1. **Create TokenService** - Move logic from TokenController
2. **Create TokenRepository** - For database operations
3. **Create Resources** - For all Model-based responses
4. **Update Controllers** - Use Resources instead of raw arrays
5. **Keep direct SDK calls in Services** - No repository for external APIs

**Priority Order:**
1. Create Resources (high impact, easy)
2. Create TokenService (fix architectural issue)
3. Create database Repositories (as needed)
4. Update all controllers to use Resources

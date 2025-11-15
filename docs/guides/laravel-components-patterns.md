# Laravel Components & Patterns Guide

Complete guide to all Laravel components with naming conventions, decision trees, data passing policies, and real examples from the codebase.

> **Related Documentation:**
> - [Layered Architecture](../architecture/flow.md) - Request/response flow and layer responsibilities
> - [SOLID Principles](../architecture/principles.md#11-solid-design-principles) - Object-oriented design principles
> - [Development Guidelines](../development/guidelines.md) - Step-by-step implementation workflows
> - [Standards Reference](../reference/standards.md#naming-conventions) - Quick lookup for naming rules

---

## Overview

This guide covers:
- **Naming Conventions** - Standard patterns for all Laravel components
- **Event vs Job Decision Tree** - When to use each pattern
- **Data Passing Policies** - Security and best practices for Jobs and Events
- **Component Patterns** - Complete examples for each Laravel component type

---

## Naming Conventions

### Complete Naming Standards

| Component | Pattern | Examples | Notes |
|-----------|---------|----------|-------|
| **Job** | `<Verb><Name>Job` | `ProcessLmStudioJob`, `SendEmailJob`, `GenerateReportJob` | Verb at the beginning, describes action |
| **Event** | `<Name><PastParticiple>` or `<Name>Happened` | `LmStudioInferenceStreamed`, `UserCreated`, `OrderShipped` | Past tense, describes event that occurred |
| **Interface/Contract** | `<Name>Contract` | `SdkContract`, `RepositoryContract`, `ServiceContract` | Always use suffix `Contract` |
| **Service** | `<Name>Service` | `CategoryService`, `TokenService`, `ChatInferenceService` | Business logic domain |
| **Repository** | `<Name>Repository` | `TokenRepository`, `PostRepository` | Data access layer |
| **Repository Contract** | `<Name>RepositoryContract` | `TokenRepositoryContract`, `PostRepositoryContract` | Interface for repository |
| **Controller** | `<Name>Controller` | `CategoryController`, `LmStudioController`, `LmStudioJobController` | HTTP layer |
| **FormRequest** | `<Action><Name>Request` | `StoreCategoryRequest`, `UpdateCategoryRequest`, `IndexCategoriesRequest`, `DeleteCategoryRequest` | Actions: Store, Update, Index, Show, Delete |
| **Resource** | `<Name>Resource` | `CategoryResource`, `PostResource`, `ProductResource` | Response transformation |
| **Policy** | `<Name>Policy` | `CategoryPolicy`, `PostPolicy` | Authorization |
| **Command** | `<Name>Command` | `LmStudioPingCommand`, `LmStudioSyncModelsCommand` | Artisan commands |
| **Middleware** | `<Name>Middleware` | `HandleInertiaRequests`, `AuthenticateMiddleware` | Cross-cutting concerns |
| **Observer** | `<Name>Observer` | `UserObserver`, `PostObserver` | Model events |
| **Scope** | `<Name>Scope` | `ActiveScope`, `PublishedScope` | Query scopes |
| **Enum** | `<Name>` | `ChatRole`, `OrderStatus`, `PaymentMethod` | Type-safe constants |
| **DTO** | `<Name>Request` / `<Name>Response` | `ChatCompletionRequest`, `ImageGenerationRequest` | Data Transfer Objects |
| **Model** | `<Name>` (singular) | `LmStudioJob`, `WpToken`, `Post` | Eloquent models, singular |

### Naming Rules

#### ✅ MUST (PHP/PSR-12 Standards):
- **Class names:** PascalCase (e.g., `CategoryService`, `LmStudioJob`)
- **Method names:** camelCase (e.g., `createUser()`, `getCategoryById()`)
- **Variable names:** camelCase (e.g., `$userId`, `$jobUuid`, `$orderIds`)
- **Constants:** UPPER_SNAKE_CASE (e.g., `MAX_RETRY_COUNT`, `DEFAULT_TIMEOUT`)
- **Property names:** camelCase (e.g., `$jobUuid`, `$userId`)

#### ✅ MUST (Project-Specific):
- **Singular** for Models, Services, Repositories
- **Descriptive verbs** for Jobs (`Process`, `Send`, `Generate`, `Calculate`)
- **Past tense** for Events (`Streamed`, `Created`, `Updated`, `Deleted`)
- **Action verbs** for FormRequests (`Store`, `Update`, `Index`, `Show`, `Delete`)
- **Explicit types** for Job parameters (`$userId`, `$jobUuid`, `$orderIds`)

#### ❌ FORBIDDEN:
- **Abbreviations** (`LmSdk` → `LmStudioSdk`)
- **Plural** in class names (`CategoriesService` → `CategoryService`)
- **Generic names** (`Data`, `Info`, `Helper` → Use specific names)
- **Hungarian notation** (`strName`, `intId` → Use `$name`, `$id`)

### Examples from Codebase

```php
// ✅ GOOD: Job naming
final class ProcessLmStudioJob implements ShouldQueue
{
    public function __construct(private readonly string $jobUuid) {}
}

// ✅ GOOD: Event naming
final class LmStudioInferenceStreamed implements ShouldBroadcastNow
{
    public function __construct(
        public readonly string $jobId,
        public readonly string $type,
        public readonly array $payload = [],
    ) {}
}

// ✅ GOOD: Contract naming
interface SdkContract
{
    public function createChatCompletion(ChatCompletionRequest $request): ChatCompletionResponse;
}

// ✅ GOOD: Service naming
final class CategoryService
{
    public function list(array $filters = []): array {}
}

// ✅ GOOD: FormRequest naming
final class StoreCategoryRequest extends FormRequest {}
final class UpdateCategoryRequest extends FormRequest {}
final class IndexCategoriesRequest extends FormRequest {}

// ✅ GOOD: Command naming
final class LmStudioPingCommand extends Command {}
final class LmStudioSyncModelsCommand extends Command {}
```

---

## Event vs Job Decision Tree

### When to Use Event

**Use Event when:**

1. **Need to notify multiple listeners**
   ```
   UserCreated Event
   ├─→ SendWelcomeEmail (Listener)
   ├─→ CreateProfile (Listener)
   └─→ LogActivity (Listener)
   ```

2. **Need real-time notification (broadcasting)**
   ```php
   final class LmStudioInferenceStreamed implements ShouldBroadcastNow
   {
       // Broadcasts to frontend in real-time
   }
   ```

3. **Need to decouple components**
   ```php
   // Service A doesn't need to know Service B exists
   event(new UserCreated($user));
   // Multiple listeners can react without Service A knowing
   ```

4. **Need synchronous processing**
   ```php
   // Events fire synchronously by default
   event(new OrderShipped($order));
   // All listeners execute immediately
   ```

### When to Use Job

**Use Job when:**

1. **Need async processing (background)**
   ```php
   // Long-running task
   ProcessLmStudioJob::dispatch($jobUuid);
   // Runs in background, doesn't block request
   ```

2. **Need retry on failure**
   ```php
   // Queue system handles retries automatically
   SendEmailJob::dispatch($userId)
       ->onQueue('emails')
       ->retry(3);
   ```

3. **Need delay/schedule**
   ```php
   SendEmailJob::dispatch($userId)
       ->delay(now()->addHours(1));
   ```

4. **Need to handle heavy computation**
   ```php
   GenerateReportJob::dispatch($reportId);
   // Heavy computation doesn't block user request
   ```

### Decision Matrix

| Scenario | Use Event | Use Job | Use Both |
|----------|----------|---------|----------|
| Notify multiple listeners | ✅ | ❌ | ✅ (Event → Job) |
| Real-time broadcasting | ✅ | ❌ | ✅ (Event → Job) |
| Background processing | ❌ | ✅ | ✅ (Event → Job) |
| Retry on failure | ❌ | ✅ | ✅ (Event → Job) |
| Decouple components | ✅ | ❌ | ✅ (Event → Job) |
| Long-running task | ❌ | ✅ | ✅ (Event → Job) |
| Heavy computation | ❌ | ✅ | ✅ (Event → Job) |

### Common Pattern: Event → Job

```php
// Event fires synchronously
final class UserCreated
{
    public function __construct(public readonly User $user) {}
}

// Job processes asynchronously
final class SendWelcomeEmailJob implements ShouldQueue
{
    public function __construct(private readonly int $userId) {}
    
    public function handle(): void
    {
        $user = User::find($this->userId);
        if ($user === null) {
            return;
        }
        // Send email...
    }
}

// In EventServiceProvider:
protected $listen = [
    UserCreated::class => [
        SendWelcomeEmailJob::class,  // Event listener dispatches Job
    ],
];
```

---

## Data Passing Policies

### Job Data Passing Policy

#### ✅ MUST: Pass ID/UUID, NOT Model Instances

**Security and Data Integrity Rules:**

1. ✅ **MUST:** Pass primitive types (string, int, array) to Job constructor
2. ✅ **MUST:** Fetch Model in `handle()` method
3. ✅ **MUST:** Handle missing data gracefully (return early, do not throw)
4. ✅ **MUST:** Remove `SerializesModels` trait if not passing Model
5. ❌ **FORBIDDEN:** Pass Model instances to Job constructor
6. ❌ **FORBIDDEN:** Pass sensitive data (passwords, tokens) to Job

#### Why ID/UUID Preferred?

**Security Concerns:**
```php
// ❌ BAD: Model may contain sensitive data
ProcessLmStudioJob::dispatch($user);  // $user has password hash, tokens, etc.

// Queue payload may expose data:
// - Failed jobs table
// - Queue monitoring tools
// - Logs
```

**Data Integrity:**
```php
// ❌ BAD: ModelNotFoundException if model deleted
final class ProcessLmStudioJob implements ShouldQueue
{
    use SerializesModels;  // Auto-deserialize, throws if missing
    
    public function __construct(private readonly LmStudioJob $job) {}
    
    public function handle(): void
    {
        // ❌ Throws ModelNotFoundException if $job deleted
        $this->job->update([...]);
    }
}

// ✅ GOOD: Graceful handling
final class ProcessLmStudioJob implements ShouldQueue
{
    public function __construct(private readonly string $jobUuid) {}
    
    public function handle(): void
    {
        $job = LmStudioJob::where('uuid', $this->jobUuid)->first();
        
        // ✅ Handle missing data gracefully
        if ($job === null) {
            \Log::warning('LmStudioJob not found', ['uuid' => $this->jobUuid]);
            return;  // Don't throw, just return
        }
        
        // ✅ Use fresh data
        $job->update([...]);
    }
}
```

**Stale Data Prevention:**
```php
// ❌ BAD: Model serialized at dispatch time
$job = LmStudioJob::find(1);
ProcessLmStudioJob::dispatch($job);

// If $job updated afterwards, job still uses old data
$job->update(['status' => 'cancelled']);
// Job still runs with old data!

// ✅ GOOD: Always fetch fresh data
ProcessLmStudioJob::dispatch($job->uuid);

// Job will fetch fresh data when running
```

#### Job Parameter Naming

| Type | Pattern | Example |
|------|---------|---------|
| Single ID | `$<name>Id` | `$userId`, `$orderId` |
| UUID | `$<name>Uuid` | `$jobUuid`, `$orderUuid` |
| Multiple IDs | `$<name>Ids` | `$categoryIds`, `$productIds` |
| Composite | `$<name>Data` | `$emailData`, `$reportData` |

#### Examples

**✅ GOOD: Single ID**
```php
final class SendWelcomeEmailJob implements ShouldQueue
{
    public function __construct(private readonly int $userId) {}
    
    public function handle(): void
    {
        $user = User::find($this->userId);
        if ($user === null) {
            \Log::warning('User not found', ['user_id' => $this->userId]);
            return;
        }
        // Send email...
    }
}
```

**✅ GOOD: UUID**
```php
final class ProcessLmStudioJob implements ShouldQueue
{
    public function __construct(private readonly string $jobUuid) {}
    
    public function handle(SdkContract $sdk): void
    {
        $job = LmStudioJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();
            
        if ($job === null) {
            return;  // Graceful handling
        }
        // Process...
    }
}
```

**✅ GOOD: Multiple IDs**
```php
final class GenerateReportJob implements ShouldQueue
{
    public function __construct(
        private readonly array $orderIds,
        private readonly int $userId,
    ) {}
    
    public function handle(): void
    {
        $orders = Order::whereIn('id', $this->orderIds)->get();
        if ($orders->isEmpty()) {
            return;
        }
        // Generate report...
    }
}
```

**❌ BAD: Passing Model Instance**
```php
final class ProcessLmStudioJob implements ShouldQueue
{
    use SerializesModels;  // ❌ Not needed if not passing Model
    
    public function __construct(private readonly LmStudioJob $job) {}
    
    public function handle(): void
    {
        // ❌ ModelNotFoundException if deleted
        $this->job->update([...]);
    }
}
```

**❌ BAD: Passing Sensitive Data**
```php
final class SendPasswordResetJob implements ShouldQueue
{
    public function __construct(
        private readonly int $userId,
        private readonly string $token,  // ❌ Sensitive data
    ) {}
}
```

### Event Data Passing Policy

**Similar rules apply to Events:**

1. ✅ **MUST:** Pass ID/UUID for Models (preferred)
2. ✅ **MUST:** Pass primitive types for simple data
3. ⚠️ **ALLOWED:** Pass Model instances ONLY if:
   - Data is not sensitive
   - Data won't be stale (event fires immediately)
   - Multiple listeners need the same Model

**Example:**
```php
// ✅ GOOD: Pass ID
final class UserCreated
{
    public function __construct(public readonly int $userId) {}
}

// ⚠️ ALLOWED: Pass Model (if not sensitive)
final class OrderShipped
{
    public function __construct(public readonly Order $order) {}
    // OK if Order doesn't contain sensitive data
}
```

---

## Request Layer

### FormRequest

**Naming:** `<Action><Name>Request`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }
    
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
    
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
        ];
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Type hint return types
- ✅ Document array shapes with PHPDoc
- ✅ Custom messages for better UX

**Example from Codebase:**
```php
final class LmStudioInferenceRequest extends FormRequest
{
    public function rules(): array
    {
        $roles = array_map(
            static fn (ChatRole $role): string => $role->value,
            ChatRole::cases()
        );

        return [
            'model' => ['nullable', 'string', 'max:255'],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', Rule::in($roles)],
        ];
    }
    
    public function toDto(): ChatCompletionRequest
    {
        // Transform to DTO
    }
}
```

### Custom Validation Rules

**Naming:** `<Name>Rule`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidCategoryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Category::exists($value)) {
            $fail('The selected category is invalid.');
        }
    }
}
```

---

## Authorization Layer

### Policy

**Naming:** `<Name>Policy`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Product\Models\Product;
use App\Models\User;

final class ProductPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Type hint all parameters
- ✅ Return explicit boolean

---

## HTTP Layer

### Middleware

**Naming:** `<Name>Middleware` or `<Action><Name>`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
```

**Example from Codebase:**
```php
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
        ];
    }
}
```

### Controller

**Naming:** `<Name>Controller`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Services\ProductService;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service
    ) {}

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated());
        
        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Dependency injection via constructor
- ✅ Thin controllers (delegate to services)
- ✅ Return typed responses

### Resource

**Naming:** `<Name>Resource`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Models\Product;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => number_format($this->price, 2),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Document with `@mixin` for IDE support
- ✅ Type hint return array
- ✅ Use conditional fields with `when()`

---

## Business Logic Layer

### Service

**Naming:** `<Name>Service`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use App\Logging\ActionLogger;
use Modules\Product\Repositories\Contracts\ProductRepositoryContract;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryContract $repository,
        private readonly ActionLogger $logger
    ) {}

    public function create(array $data, ?Authenticatable $actor = null): Product
    {
        $product = $this->repository->create($data);
        
        $this->logger->log(
            operation: 'product.created',
            actor: $actor,
            before: [],
            after: $product->toArray(),
        );
        
        return $product;
    }
}
```

**Best Practices:**
- ✅ One service = one business domain
- ✅ Use repository contracts (not concrete classes)
- ✅ Audit logging for mutations
- ✅ Return Models or arrays (not HTTP responses)

### Repository

**Naming:** `<Name>Repository`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use Modules\Product\Models\Product;
use Modules\Product\Repositories\Contracts\ProductRepositoryContract;

final class ProductRepository implements ProductRepositoryContract
{
    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }
}
```

**Best Practices:**
- ✅ Implement contract interface
- ✅ Return Models or null
- ✅ No business logic
- ✅ Database operations only

### Repository Contract

**Naming:** `<Name>RepositoryContract`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Repositories\Contracts;

use Modules\Product\Models\Product;

interface ProductRepositoryContract
{
    public function findById(int $id): ?Product;
    
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Product;
}
```

---

## Data Layer

### Model

**Naming:** `<Name>` (singular)

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;

final class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Explicit `$fillable` array
- ✅ Type casts for data integrity
- ✅ Relationships as methods

### Enum

**Naming:** `<Name>`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

enum ChatRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
```

**Example from Codebase:**
```php
enum ChatRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}

// Usage:
ChatRole::from('user');  // Returns ChatRole::User
ChatRole::User->value;   // Returns 'user'
```

### DTO (Data Transfer Object)

**Naming:** `<Name>Request` / `<Name>Response`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

use Spatie\DataTransferObject\DataTransferObject;

final class ChatCompletionRequest extends DataTransferObject
{
    public function __construct(
        public readonly string $model,
        /** @var array<int, ChatMessage> */
        public readonly array $messages,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
    ) {}
}
```

**Best Practices:**
- ✅ Use `readonly` properties
- ✅ Type hint all properties
- ✅ Document array shapes
- ✅ Immutable (no setters)

---

## Event System

### Event

**Naming:** `<Name><PastParticiple>` or `<Name>Happened`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LmStudioInferenceStreamed implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $jobId,
        public readonly string $type,
        public readonly array $payload = [],
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('lmstudio.inference.' . $this->jobId);
    }

    public function broadcastAs(): string
    {
        return 'LmStudioInferenceStreamed';
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Use `readonly` properties
- ✅ Implement `ShouldBroadcastNow` for real-time
- ✅ Define `broadcastOn()` and `broadcastAs()`

### Listener

**Naming:** `<EventName>Listener` or `<Action><Name>`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Listeners;

use Modules\Product\Events\ProductCreated;
use Modules\Product\Jobs\SendProductNotificationJob;

final class SendProductNotificationListener
{
    public function handle(ProductCreated $event): void
    {
        // Dispatch job for async processing
        SendProductNotificationJob::dispatch($event->productId);
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Type hint event parameter
- ✅ Dispatch jobs for async work
- ✅ Keep listeners thin

---

## Queue System

### Job

**Naming:** `<Verb><Name>Job`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessLmStudioJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    // ❌ REMOVE SerializesModels if not passing Model instances

    public function __construct(private readonly string $jobUuid)
    {
        $this->onConnection(config('lmstudio.queue_connection'));
        $this->onQueue(config('lmstudio.queue'));
    }

    public function handle(SdkContract $sdk): void
    {
        $job = LmStudioJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();

        // ✅ Handle missing data gracefully
        if ($job === null) {
            \Log::warning('LmStudioJob not found', ['uuid' => $this->jobUuid]);
            return;
        }

        // Process...
    }
}
```

**Best Practices:**
- ✅ Pass ID/UUID, not Model instances
- ✅ Fetch Model in `handle()` method
- ✅ Handle missing data gracefully
- ✅ Remove `SerializesModels` if not needed
- ✅ Configure queue connection and name

**Example from Codebase:**
```php
final class ProcessLmStudioJob implements ShouldQueue
{
    public function __construct(private readonly string $jobUuid)
    {
        $this->onConnection(config('lmstudio.queue_connection', config('queue.default')));
        $this->onQueue(config('lmstudio.queue', 'default'));
    }

    public function handle(SdkContract $sdk): void
    {
        $job = LmStudioJob::query()
            ->where('uuid', $this->jobUuid)
            ->first();

        if ($job === null) {
            return;  // Graceful handling
        }

        // Process...
    }
}
```

---

## Console Layer

### Command

**Naming:** `<Name>Command`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use App\Logging\ActionLogger;

final class LmStudioPingCommand extends Command
{
    protected $signature = 'lmstudio:ping {--json : Output JSON payload}';
    protected $description = 'Check LM Studio health status from the CLI.';

    public function __construct(private readonly ActionLogger $actionLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Implementation...
        return self::SUCCESS;
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Define `$signature` and `$description`
- ✅ Return exit codes (`SUCCESS`, `FAILURE`)
- ✅ Use `$this->info()`, `$this->error()`, `$this->table()`

**Example from Codebase:**
```php
final class LmStudioPingCommand extends Command
{
    protected $signature = 'lmstudio:ping {--json : Output JSON payload}';
    protected $description = 'Check LM Studio health status from the CLI.';

    public function handle(): int
    {
        if (! config('features.lmstudio.enabled', false)) {
            $this->warn('LM Studio integration is disabled via feature flag.');
            return self::FAILURE;
        }

        // Implementation...
        return self::SUCCESS;
    }
}
```

---

## Advanced Patterns

### Observer

**Naming:** `<Name>Observer`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Observers;

use Modules\Product\Models\Product;

final class ProductObserver
{
    public function created(Product $product): void
    {
        // Handle created event
    }

    public function updated(Product $product): void
    {
        // Handle updated event
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Type hint Model parameter
- ✅ Keep observers thin (dispatch events/jobs if needed)

### Scope

**Naming:** `<Name>Scope`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ActiveScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('active', true);
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Implement `Scope` interface
- ✅ Reusable query constraints

### Service Provider

**Naming:** `<Name>ServiceProvider`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Repositories\Contracts\ProductRepositoryContract;
use Modules\Product\Repositories\ProductRepository;

final class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProductRepositoryContract::class,
            ProductRepository::class
        );
    }

    public function boot(): void
    {
        // Boot logic
    }
}
```

**Best Practices:**
- ✅ Use `final` keyword
- ✅ Bind contracts in `register()`
- ✅ Load routes/migrations in `boot()`

---

## Component Interaction Patterns

### Complete Request Flow Example

```php
// 1. FormRequest (Validation)
final class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string']];
    }
}

// 2. Controller (HTTP Layer)
final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service
    ) {}

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated());
        return ProductResource::make($product)->response()->setStatusCode(201);
    }
}

// 3. Service (Business Logic)
final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryContract $repository,
        private readonly ActionLogger $logger
    ) {}

    public function create(array $data): Product
    {
        $product = $this->repository->create($data);
        $this->logger->log('product.created', auth()->user(), [], $product->toArray());
        return $product;
    }
}

// 4. Repository (Data Access)
final class ProductRepository implements ProductRepositoryContract
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }
}

// 5. Resource (Response Transformation)
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
```

---

## Common Mistakes

### ❌ Passing Model Instances to Jobs

```php
// ❌ BAD
ProcessLmStudioJob::dispatch($job);

// ✅ GOOD
ProcessLmStudioJob::dispatch($job->uuid);
```

### ❌ Using Plural in Class Names

```php
// ❌ BAD
final class CategoriesService {}
final class ProductsRepository {}

// ✅ GOOD
final class CategoryService {}
final class ProductRepository {}
```

### ❌ Missing Graceful Handling in Jobs

```php
// ❌ BAD
public function handle(): void
{
    $job = LmStudioJob::findOrFail($this->jobId);  // Throws exception
    // Process...
}

// ✅ GOOD
public function handle(): void
{
    $job = LmStudioJob::find($this->jobId);
    if ($job === null) {
        \Log::warning('Job not found', ['id' => $this->jobId]);
        return;
    }
    // Process...
}
```

### ❌ Using SerializesModels Unnecessarily

```php
// ❌ BAD
final class ProcessLmStudioJob implements ShouldQueue
{
    use SerializesModels;  // Not needed if passing ID
    
    public function __construct(private readonly string $jobUuid) {}
}

// ✅ GOOD
final class ProcessLmStudioJob implements ShouldQueue
{
    // Remove SerializesModels if not passing Model instances
    public function __construct(private readonly string $jobUuid) {}
}
```

---

## Summary

### Key Takeaways

1. **Naming Conventions:** Follow standard patterns for all components
2. **Event vs Job:** Use decision tree to choose the right pattern
3. **Data Passing:** Always pass ID/UUID to Jobs, not Model instances
4. **Security:** Avoid exposing sensitive data in queue payloads
5. **Data Integrity:** Always fetch fresh data in Jobs and handle missing data gracefully

### Quick Reference

- **Jobs:** `<Verb><Name>Job`, pass ID/UUID
- **Events:** `<Name><PastParticiple>`, can pass Model if not sensitive
- **Services:** `<Name>Service`, one domain per service
- **Repositories:** `<Name>Repository`, implement contracts
- **Controllers:** `<Name>Controller`, thin HTTP layer
- **FormRequests:** `<Action><Name>Request`, validation layer
- **Resources:** `<Name>Resource`, response transformation


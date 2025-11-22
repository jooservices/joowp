# jooclient MongoDB Logging Configuration

Complete guide for configuring jooclient to use MongoDB for HTTP request logging.

## Overview

jooclient has been configured by default to use MongoDB for logging. All HTTP requests from WordPress SDK (and other SDKs using jooclient) will be logged to MongoDB.

## Configuration

### 1. Environment Variables

Add the following environment variables to your `.env` file:

```env
# jooclient Logging
JOOCLIENT_LOGGING_ENABLED=true
JOOCLIENT_LOGGING_DRIVER=mongodb

# MongoDB Connection
JOOCLIENT_MONGODB_LOGGING=true
JOOCLIENT_MONGODB_DSN=mongodb://127.0.0.1:27017
JOOCLIENT_MONGODB_DATABASE=jooclient
JOOCLIENT_MONGODB_COLLECTION=client_request_logs

# MongoDB Log Rotation (optional)
JOOCLIENT_MONGODB_LOG_PATH=/path/to/logs/mongodb_errors.log
JOOCLIENT_MONGODB_ROTATE_SIZE=10485760  # 10MB
JOOCLIENT_MONGODB_ROTATE_FILES=5
```

### 2. MongoDB Connection Options

jooclient also supports using MongoDB connection from Laravel config if available:

```env
# Use MongoDB connection from Laravel config (if available)
MONGODB_DSN=mongodb://127.0.0.1:27017
MONGODB_DATABASE=jooclient
```

If `MONGODB_DSN` and `MONGODB_DATABASE` are set, jooclient will use them as fallback.

### 3. Config File

The `config/jooclient.php` file has been published and configured with:

- **Logging enabled**: `true` (default)
- **Driver**: `mongodb` (default)
- **MongoDB enabled**: `true` (default)
- **Collection**: `client_request_logs`

## MongoDB Setup

### 1. Install MongoDB Extension

Ensure PHP MongoDB extension is installed:

```bash
# Ubuntu/Debian
sudo apt-get install php-mongodb

# macOS (via Homebrew)
brew install php-mongodb

# Check if installed
php -m | grep mongodb
```

### 2. Create MongoDB Database

```bash
# Connect to MongoDB
mongosh

# Create database
use jooclient

# Create collection (optional, will be created automatically)
db.createCollection("client_request_logs")
```

### 3. Verify Configuration

```bash
# Clear config cache
php artisan config:clear

# Test MongoDB connection (if you have MongoDB client)
mongosh "mongodb://127.0.0.1:27017/jooclient"
```

## Log Data Structure

Each HTTP request is logged with the following structure:

```json
{
  "method": "GET",
  "uri": "https://example.com/api/posts",
  "status_code": 200,
  "request_headers": {},
  "request_body": null,
  "response_headers": {},
  "response_body": {},
  "duration_ms": 150,
  "created_at": "2025-01-22T10:00:00Z",
  "performance_metrics": {
    "memory_usage": 1048576,
    "duration": 0.15
  }
}
```

## Features

### Automatic Retry Logging
jooclient automatically logs retry attempts and error responses.

### Data Sanitization
Sensitive data (passwords, API keys) is automatically sanitized before logging.

### Performance Metrics
Request duration, memory usage are automatically tracked.

### Error Logging
If MongoDB write fails, errors are logged to file:
- `storage/logs/mongodb_errors.log` (default)

## Testing

After configuration, test logging:

```php
// In a controller or service
use Modules\Core\Services\WordPress\Contracts\SdkContract;

// Make a WordPress API call
$sdk = app(SdkContract::class);
$categories = $sdk->categories();

// Check MongoDB
mongosh
use jooclient
db.client_request_logs.find().sort({created_at: -1}).limit(1).pretty()
```

## Troubleshooting

### MongoDB Not Connected

**Error**: `MongoDB connection failed`

**Solution**:
1. Verify MongoDB is running: `mongosh "mongodb://127.0.0.1:27017"`
2. Check DSN in `.env`: `JOOCLIENT_MONGODB_DSN=mongodb://127.0.0.1:27017`
3. Check MongoDB extension: `php -m | grep mongodb`

### Logs Not Appearing

**Solution**:
1. Check logging enabled: `JOOCLIENT_LOGGING_ENABLED=true`
2. Check MongoDB logging enabled: `JOOCLIENT_MONGODB_LOGGING=true`
3. Check driver: `JOOCLIENT_LOGGING_DRIVER=mongodb`
4. Clear config cache: `php artisan config:clear`
5. Check error logs: `storage/logs/mongodb_errors.log`

### Permission Issues

**Solution**:
1. Ensure MongoDB user has write permissions
2. Check collection permissions in MongoDB
3. Check file permissions for error logs directory

## Related Documentation

- **jooclient MongoDB Guide**: `vendor/jooservices/jooclient/docs/guides/MONGODB_CONFIG_GUIDE.md`
- **jooclient Integration Guide**: `vendor/jooservices/jooclient/docs/guides/JOOWP_INTEGRATION.md`
- **jooclient Usage Guide**: `vendor/jooservices/jooclient/docs/guides/USAGE_GUIDE.md`

# News Aggregator API

A Laravel-based news aggregation system that fetches articles from multiple sources (Guardian, NewsAPI, NY Times) and provides a unified API for searching and filtering news articles.

## Features

- **Multi-source news aggregation** from Guardian, NewsAPI, and NY Times
- **RESTful API** for listing and filtering articles
- **Automated synchronization** with configurable scheduling (every 12 hours by default)
- **Advanced filtering** by source, author, publish date, and search queries
- **Repository pattern** with clean architecture
- **Docker support** for easy deployment
- **Performance optimized** with batch operations and intelligent duplicate handling

## Getting Started with Docker Compose

### Prerequisites

- Docker and Docker Compose installed
- Git

### Installation Steps

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd news_aggregator
   ```

2. **Copy environment configuration**:
   ```bash
   cp .env.example .env
   ```

3. **Configure environment variables** in `.env`:
   ```env
   # Database Configuration
   DB_CONNECTION=pgsql
   DB_HOST=db
   DB_PORT=5432
   DB_DATABASE=news_aggregator
   DB_USERNAME=postgres
   DB_PASSWORD=secret

   # News API Keys (required for news sources)
   NEWS_API_KEY=your_newsapi_key_here
   GUARDIAN_KEY=your_guardian_key_here
   NY_TIMES_KEY=your_nytimes_key_here

   # News Sync Configuration
   NEWS_SYNC_ENABLED=true
   NEWS_SYNC_SCHEDULE="0 */12 * * *"
   ```

4. **Start the application**:
   ```bash
   cd docker
   docker-compose up -d
   ```

   This will start the following services:
   - **app**: Main Laravel application (port 8000)
   - **db**: PostgreSQL database (port 5432)
   - **redis**: Redis for caching and queues (port 6379)
   - **queue**: Background job worker
   - **scheduler**: Automated task scheduler for news sync

5. **The application setup is automatic** - Docker compose will:
   - Build the Laravel application
   - Install dependencies
   - Run database migrations
   - Start the scheduler for automated news sync

The application will be available at `http://localhost:8000`

## API Endpoints

### Base URL: `http://localhost:8000/api/v1`

### 1. List Articles

**Endpoint**: `GET /v1/articles`

**Description**: Retrieve a paginated list of articles with optional filtering.

**Query Parameters**:
- `source` (string, optional): Filter by news source (`guardian`, `news_api`, `ny_times`)
- `author` (string, optional): Filter by author name (minimum 2 characters)
- `publish_date` (date, optional): Filter by specific publication date (YYYY-MM-DD)
- `search` (string, optional): Search in article titles and author names (minimum 2 characters)

**Request Example**:
```bash
GET /api/v1/articles?source=guardian&search=climate&publish_date=2026-02-15
```

**Response Example**:
```json
{
  "data": [
    {
      "id": 1,
      "external_id": "abc123",
      "title": "Climate Change Impact on Global Economy",
      "description": "A comprehensive analysis of climate change effects...",
      "content": null,
      "url": "https://www.theguardian.com/article-url",
      "image_url": "https://image-url.jpg",
      "author_name": "John Smith",
      "published_at": "2026-02-15T10:30:00.000000Z",
      "source": "guardian",
      "created_at": "2026-02-15T12:00:00.000000Z",
      "updated_at": "2026-02-15T12:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "has_more": true
  }
}
```

### 2. List Available Sources

**Endpoint**: `GET /v1/sources`

**Description**: Get all available news sources using the Sources enum.

**Request Example**:
```bash
GET /api/v1/sources
```

**Response Example**:
```json
{
  "data": [
    "guardian",
    "news_api",
    "ny_times"
  ]
}
```

### 3. List Available Authors

**Endpoint**: `GET /v1/authors`

**Description**: Get all distinct authors from the database.

**Request Example**:
```bash
GET /api/v1/authors
```

**Response Example**:
```json
{
  "data": [
    {
      "name": "John Smith",
      "value": "John Smith"
    },
    {
      "name": "Jane Doe",
      "value": "Jane Doe"
    }
  ]
}
```

## Docker Services

The application runs with the following Docker services:

### Core Services
- **app**: Main Laravel application with PHP 8.3-FPM
- **db**: PostgreSQL 16 database with automatic health checks
- **redis**: Redis 7 for caching and queue management

### Background Services
- **queue**: Handles background job processing with 3 retry attempts and 300-second timeout
- **scheduler**: Runs the Laravel scheduler for automated news synchronization

### Ports
- **8000**: Laravel application
- **5432**: PostgreSQL database
- **6379**: Redis server

### Volumes
- Application code is mounted to `/var/www`
- PostgreSQL data persisted in `pgdata` volume
- Redis data persisted in `redisdata` volume

## Console Commands

### News Sync Command

The main command for synchronizing articles from news sources.

**Command**: `php artisan news:sync`

#### Usage Options:

1. **Sync all configured sources**:
   ```bash
   docker-compose exec app php artisan news:sync
   ```

2. **Sync specific source only**:
   ```bash
   docker-compose exec app php artisan news:sync --source=guardian
   docker-compose exec app php artisan news:sync --source=news_api
   docker-compose exec app php artisan news:sync --source=ny_times
   ```

3. **View sync statistics**:
   ```bash
   docker-compose exec app php artisan news:sync --stats
   ```

#### Example Output:
```bash
🚀 Starting news sync for all sources...
📡 Syncing guardian...
📡 Syncing news_api...
📡 Syncing ny_times...
✅ News sync completed for all sources!

📊 Sync Results:
   guardian: Created 45, Skipped 155
   news_api: Created 38, Skipped 162
   ny_times: Created 52, Skipped 148
```

### Schedule Configuration Test

The console command includes a `--stats` option to view synchronization statistics.

**Command**: `php artisan news:sync --stats`

**Description**: Display recent sync statistics and performance data.

**Example Usage**:
```bash
docker-compose exec app php artisan news:sync --stats
```

**Example Output**:
```bash
📊 Recent Sync Statistics (Last 7 days)

📡 guardian
   Total runs: 14
   Successful: 13
   Failed: 1
   Articles created: 450
   Articles skipped: 1250
   Last sync: 2026-02-15 10:30:00
   Status: completed

📡 news_api
   Total runs: 14
   Successful: 14
   Failed: 0
   Articles created: 380
   Articles skipped: 1320
   Last sync: 2026-02-15 10:35:00
   Status: completed

⚠️ Recent Failures (Last 24 hours):
   guardian at 2026-02-14 22:00:00: API rate limit exceeded
```

## Scheduled Kernel (Automatic Synchronization)

The news aggregator includes an automated scheduling system that runs the sync command every 12 hours by default through a dedicated Docker service.


### Docker Scheduler Service

The `scheduler` service in Docker Compose automatically runs `php artisan schedule:work`, which:
- Monitors the schedule configuration
- Executes the news sync command based on the cron expression
- Runs continuously in the background
- Starts automatically when you run `docker-compose up`

### Environment Variables

Control the scheduling behavior with these `.env` variables:

```env
# Enable/disable automatic synchronization
NEWS_SYNC_ENABLED=true

# Cron expression for scheduling (every 12 hours by default)
NEWS_SYNC_SCHEDULE="0 */12 * * *"
```

### Schedule Examples

- **Every 6 hours**: `NEWS_SYNC_SCHEDULE="0 */6 * * *"`
- **Daily at 8 AM and 8 PM**: `NEWS_SYNC_SCHEDULE="0 8,20 * * *"`
- **Every hour**: `NEWS_SYNC_SCHEDULE="0 * * * *"`
- **Weekly (Sunday at midnight)**: `NEWS_SYNC_SCHEDULE="0 0 * * 0"`

## Environment Configuration

### Required API Keys

To fetch articles from news sources, you need API keys:

1. **NewsAPI**: Get key from [newsapi.org](https://newsapi.org)
2. **Guardian**: Get key from [Guardian Open Platform](https://open-platform.theguardian.com)
3. **NY Times**: Get key from [NY Times Developer](https://developer.nytimes.com)

### Docker Environment

The application uses these default database credentials in Docker:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=news_aggregator
DB_USERNAME=postgres
DB_PASSWORD=secret
```

## Testing

The application includes minimal test coverage for core functionality using a **dedicated PostgreSQL testing database** for realistic testing conditions.

### Running Tests
```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test files
docker-compose exec app php artisan test tests/Unit/GatewayTest.php
docker-compose exec app php artisan test tests/Feature/ArticleApiTest.php
docker-compose exec app php artisan test tests/Unit/ScheduleTest.php

# Verbose output
docker-compose exec app php artisan test --verbose
```

### Test Coverage
- **Gateway Tests**: Mock API responses for all three news sources (Guardian, NewsAPI, NY Times)
- **API Endpoint Tests**: Test filtering by source, author, date, and search functionality
- **Schedule Tests**: Verify scheduling configuration and command existence

### Database Configuration
Tests use a **dedicated PostgreSQL testing database** (`news_aggregator_testing`) for:
- 🎯 **Realistic testing** - Same database engine as production
- 🔍 **Full-text search testing** - PostgreSQL-specific features work properly
- 🏗️ **Migration testing** - Ensure migrations work correctly
- 🔄 **RefreshDatabase** - Clean slate for each test

The testing database is automatically created by the PostgreSQL init script and uses the same extensions as the production database.

See [TESTING.md](TESTING.md) for detailed test documentation.

## Troubleshooting

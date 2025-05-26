# Content Scheduler

A powerful content scheduling and management system built with Laravel and Vue.js that allows users to schedule and publish content across multiple platforms.

## Features

- Multi-platform content scheduling
- User management and authentication with Laravel Sanctum
- Platform integration management
- Automated content publishing through job queues
- RESTful API architecture with L5-Swagger documentation
- Modern Vue.js frontend with Pinia, Vue Router, and Chart.js

## Requirements

- PHP >= 8.2
- Composer >= 2.0
- Node.js >= 18.x
- npm >= 8.x
- MySQL >= 5.7 or compatible database
- Redis (optional, for caching/queue)
- Memcached (optional, for caching)
- Laravel 12.x requirements (see Laravel documentation)

## Installation

1. Clone the repository:

    ```bash
    git clone https://github.com/Ahmed-Ale/Content-Scheduler.git
    cd content-scheduler
    ```

2. Install PHP dependencies:

    ```bash
    composer install
    ```

3. Install JavaScript dependencies:

    ```bash
    cd frontend/vue
    npm install
    cd ../..
    ```

4. Configure environment:

   On Linux/macOS:
   ```bash
    cp .env.example .env
    ```

   On Windows Command Prompt:
    ```bash
    copy .env.example .env
    ```

   Generate the app key
    ```bash
    php artisan key:generate
    ```

5. Configure your database in `.env` file:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=content_scheduler
    DB_USERNAME=root
    DB_PASSWORD=root
    ```

6. Run migrations:

    ```bash
    php artisan migrate
    ```

7. Start the frontend development server:

   Open a new terminal and run:
    ```bash
    cd frontend/vue
    npm run dev
    ```
   This will start the Vue development server, usually on http://localhost:5173.

8. Start the Laravel backend server::

   In your original terminal, run:
    ```bash
    php artisan serve
    ```

## Scheduler & Queue Setup

To enable automated scheduled publishing, you need to run the Laravel scheduler **and** a queue worker process.

### On Windows (Development)

You have two options:

Run the scheduler as a long-running process (recommended for dev):

```bash
php artisan schedule:work
```

Run the queue worker separately in another terminal:

```bash
php artisan queue:work --queue=publishing
```

### On Linux / Production

- Use cron to run the scheduler every minute by adding this line to your crontab:
```cron
* * * * * php /path-to-project/artisan schedule:run >> /dev/null 2>&1
```
- Use Supervisor to keep the queue worker running and automatically restart it if it fails. Example Supervisor config:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-project/artisan queue:work --queue=publishing --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-project/storage/logs/worker.log
```


## Project Structure

- `app/` - Contains the core code of the application
    - `Console/Commands/` - Custom Artisan commands
    - `Http/Controllers/` - Application controllers
    - `Models/` - Eloquent models
    - `Jobs/` - Queue jobs for automated publishing
- `database/` - Migrations, factories, and seeders
- `frontend/vue/` - Vue.js frontend application
- `routes/` - Application route definitions
    - `api.php` - API routes
    - `web.php` - Web routes
- `tests/` - PHPUnit test suite

## Testing

Run the test suite:

```bash
composer run test
```

## API Documentation

The API documentation is available via L5-Swagger at:

http://localhost:8000/api/documentation

Make sure `L5_SWAGGER_CONST_HOST` in `.env` matches your `APP_URL`.

## Approach & Notes

This project was built as part of the Backend Developer Coding Challenge: Content Scheduler.
It includes:
- A Laravel backend with Sanctum-based auth and API routes
- A Vue.js frontend with a post editor, dashboard, and settings
- Job queues to process scheduled posts
- Mock publishing logic and basic platform-specific validation

## Trade-offs & Assumptions

- The publishing process is mocked and logged instead of integrated with actual APIs.

- Rate limiting (10 posts/day) is enforced via Laravel validation.

- Platform requirements like character limits are hardcoded but can be expanded per platform type.

## Demo Video
[Link to your Google Drive demo video]
# Content Scheduler

A powerful content scheduling and management system built with Laravel and Vue.js that allows users to schedule and publish content across multiple platforms.

## Features

- Multi-platform content scheduling
- User management and authentication
- Platform integration management
- Automated content publishing through job queues
- RESTful API architecture
- Modern Vue.js frontend

## Requirements

- PHP >= 8.0
- Composer
- Node.js & npm
- MySQL/PostgreSQL
- Laravel requirements

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd Content-Scheduler
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure your database in `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

7. Build frontend assets:
```bash
npm run build
```

8. Start the development server:
```bash
php artisan serve
```

## Project Structure

- `app/` - Contains the core code of the application
  - `Console/Commands/` - Custom Artisan commands
  - `Http/Controllers/` - Application controllers
  - `Models/` - Eloquent models
  - `Jobs/` - Queue jobs including PublishPostJob
- `database/` - Database migrations and seeders
- `frontend/vue/` - Vue.js frontend application
- `routes/` - Application routes
  - `api.php` - API routes
  - `web.php` - Web routes

## Queue Worker

To process the scheduled posts, make sure to run the queue worker:

```bash
php artisan queue:work
```

## Testing

Run the test suite using:

```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

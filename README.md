# Theatergruppe Freispiel - Website

Welcome to the official website of Theater Group "Freispiel"! This is a Symfony 7.2-based web application featuring a contact form with spam protection and visitor information.

## 🎭 Features

- **Welcome Page**: Warm welcome message for visitors
- **Contact Form**: Allows visitors to send messages to the theater group
- **Spam Protection**: Honeypot-based spam protection (privacy-friendly, no external tracking)
- **Database Storage**: All contact form submissions are stored in MariaDB
- **Bootstrap Styling**: Modern, responsive design using Bootstrap 5
- **Docker Support**: Easy local development setup with Docker and Docker Compose

## 📋 Requirements

### For Docker Setup (Recommended)
- Docker Desktop (macOS) or Docker Engine + Docker Compose (Ubuntu/Linux)
- Docker Compose v2.0 or higher

### For Local Setup (Without Docker)
- PHP 8.2 or higher
- Composer
- MariaDB 11.5 or MySQL 8.0
- PHP Extensions: `pdo_mysql`, `mbstring`, `xml`, `zip`, `intl`

## 🚀 Getting Started with Docker (macOS & Ubuntu)

### 1. Install Docker

#### macOS
1. Download [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop)
2. Install and start Docker Desktop
3. Verify installation:
   ```bash
   docker --version
   docker-compose --version
   ```

#### Ubuntu
```bash
# Update package index
sudo apt-get update

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add your user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
sudo apt-get install docker-compose-plugin

# Verify installation
docker --version
docker compose version
```

### 2. Clone the Repository
```bash
git clone https://github.com/net-idea/tg-freispiel.git
cd tg-freispiel
```

### 3. Configure Environment Variables
```bash
# Copy the environment file
cp .env .env.local

# Edit .env.local if you need to customize settings (optional)
```

The application uses honeypot-based spam protection by default, so no external API keys are required.

### 4. Start the Application
```bash
# Build and start containers
docker-compose up -d

# Install dependencies (if not already installed in Dockerfile)
docker-compose exec web composer install

# Create database schema
docker-compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Access the Application
- **Website**: http://localhost:8000
- **Database**: localhost:3306
  - User: `app`
  - Password: `!ChangeMe!`
  - Database: `app`

### 6. Stop the Application
```bash
docker-compose down

# To remove volumes as well (⚠️ WARNING: This will delete all data!)
docker-compose down -v
```

## 🛠️ Local Development (Without Docker)

### 1. Install Dependencies
```bash
# Install PHP dependencies
composer install
```

### 2. Configure Database
```bash
# Create .env.local file
cp .env .env.local

# Edit .env.local and configure your database
# DATABASE_URL="mysql://username:password@127.0.0.1:3306/database_name?serverVersion=mariadb-11.5.0&charset=utf8mb4"
```

### 3. Create Database
```bash
# Create the database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate
```

### 5. Start Development Server
```bash
# Start Symfony server
php -S localhost:8000 -t public

# Or use Symfony CLI if installed
symfony server:start
```

### 6. Access the Application
Open your browser and navigate to: http://localhost:8000

## 📝 Project Structure

```
tg-freispiel/
├── config/              # Configuration files
│   └── packages/        # Bundle configurations
├── migrations/          # Database migrations
├── public/              # Web root directory
│   └── index.php        # Application entry point
├── src/
│   ├── Controller/      # Controllers
│   ├── Entity/          # Doctrine entities
│   ├── Form/            # Form types
│   └── Repository/      # Entity repositories
├── templates/           # Twig templates
│   ├── base.html.twig   # Base layout
│   └── home/            # Homepage templates
├── var/                 # Cache and logs (not in git)
├── vendor/              # Composer dependencies (not in git)
├── .env                 # Environment variables (committed)
├── .env.local           # Local overrides (not in git)
├── compose.yaml         # Docker Compose configuration
├── Dockerfile           # Docker image definition
└── README.md            # This file
```

## 🗄️ Database Schema

### Contact Table
Stores all contact form submissions:

| Column    | Type         | Description                    |
|-----------|--------------|--------------------------------|
| id        | INT          | Primary key (auto-increment)   |
| name      | VARCHAR(255) | Visitor's name                 |
| email     | VARCHAR(255) | Visitor's email address        |
| message   | TEXT         | Contact message                |
| created_at| DATETIME     | Timestamp of submission        |

## 🧪 Testing

```bash
# Run all tests
php bin/phpunit

# Run tests in Docker
docker-compose exec web php bin/phpunit
```

## 🔒 Security

- **Honeypot Spam Protection**: Privacy-friendly spam protection without external tracking
- **CSRF Protection**: Built-in Symfony CSRF protection on all forms
- **Input Validation**: All form inputs are validated server-side
- **SQL Injection Protection**: Doctrine ORM prevents SQL injection attacks

### How Honeypot Protection Works

The contact form includes a hidden field that is invisible to human users but typically filled by spam bots:
- Hidden field is styled with CSS (`visually-hidden`)
- Field has `tabindex="-1"` and `autocomplete="off"`
- If the field is filled, the submission is silently rejected
- No external API calls or user tracking required

### Security Best Practices

⚠️ **Important**: Before deploying to production:

1. **Update Dependencies**: Run `composer update` regularly to get security patches
2. **Environment Variables**: Never commit `.env.local` or production secrets to git
3. **Database Credentials**: Change default passwords in production
4. **HTTPS**: Always use HTTPS in production to protect user data
5. **Security Audit**: Run `composer audit` regularly to check for known vulnerabilities

```bash
# Check for security vulnerabilities
composer audit

# Update dependencies to get security fixes
composer update
```

## 📦 Key Dependencies

- **Symfony 7.2**: PHP framework (latest stable LTS)
- **Doctrine ORM**: Database abstraction layer
- **Bootstrap 5**: Frontend framework
- **MariaDB 11.5**: Database

## 🐛 Troubleshooting

### Port Already in Use
If port 8000 or 3306 is already in use:
```bash
# Edit compose.override.yaml and change the port mapping
# For web: "8080:8000" instead of "8000:8000"
# For database: "3307:3306" instead of "3306:3306"
```

### Permission Issues (Linux/Ubuntu)
```bash
# Fix permissions
sudo chown -R $USER:$USER var/
chmod -R 775 var/
```

### Database Connection Errors
1. Ensure MariaDB container is running: `docker-compose ps`
2. Check database credentials in `.env.local`
3. Verify DATABASE_URL format matches your setup

### Spam Getting Through
The honeypot protection is effective against most bots, but sophisticated bots may bypass it. Consider:
1. Adding JavaScript-based validation (bots often don't execute JS)
2. Implementing rate limiting for form submissions
3. Adding time-based validation (bots submit forms too quickly)

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Support

For questions or issues, please use the contact form on the website or create an issue in the GitHub repository.

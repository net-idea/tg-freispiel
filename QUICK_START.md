# Quick Start Guide - Theatergruppe Freispiel Website

This guide will help you get the website up and running in under 5 minutes!

## 🚀 Fastest Way: Using Docker

### Prerequisites
- Docker Desktop (macOS) or Docker Engine (Ubuntu/Linux)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/net-idea/tg-freispiel.git
   cd tg-freispiel
   ```

2. **Configure environment (optional)**
   ```bash
   cp .env .env.local
   # Edit .env.local if you need to customize database settings
   # For testing with defaults, you can skip this step
   ```

3. **Start the application**
   ```bash
   docker-compose up -d
   ```

4. **Initialize the database**
   ```bash
   docker-compose exec web php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. **Access the website**
   - Open your browser: http://localhost:8000
   - Visit the contact form: http://localhost:8000/contact

### Stop the application
```bash
docker-compose down
```

## 🔧 Alternative: Without Docker

### Prerequisites
- PHP 8.1+
- Composer
- MariaDB or MySQL

### Steps

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Configure database**
   ```bash
   cp .env .env.local
   # Edit .env.local and set your DATABASE_URL
   ```

3. **Create database and schema**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Start PHP server**
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Access the website**
   - Open your browser: http://localhost:8000

## 🎯 What You Get

- **Homepage** (`/`): Welcome page with theater group information
- **Contact Form** (`/contact`): Form to send messages to the theater group
- **Admin Panel**: Not included (can be added with EasyAdmin bundle)

## 🛡️ Spam Protection

The contact form uses **honeypot-based spam protection**:
- A hidden field that bots typically fill out
- No external API calls or tracking
- Privacy-friendly and GDPR-compliant
- Works automatically without configuration

## 📊 View Contact Submissions

Contact form submissions are stored in the `contact` table in MariaDB.

To view them via command line:
```bash
# With Docker
docker-compose exec database mysql -uapp -p'!ChangeMe!' app -e "SELECT * FROM contact;"

# Without Docker (adjust credentials)
mysql -u username -p database_name -e "SELECT * FROM contact;"
```

## 🐛 Troubleshooting

### Port Already in Use
Edit `compose.override.yaml` and change the ports:
```yaml
services:
  database:
    ports:
      - "3307:3306"  # Changed from 3306:3306
```

### Permission Errors
```bash
chmod -R 777 var/
```

### Database Connection Issues
Verify your `DATABASE_URL` in `.env.local` is correct.

## 📚 Need More Info?

See the main [README.md](README.md) for comprehensive documentation.

## 🆘 Getting Help

- Check the [README.md](README.md) for detailed information
- Review the Symfony documentation: https://symfony.com/doc
- Open an issue in the GitHub repository

# WebGNIS Installation Guide

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Prerequisites](#prerequisites)
3. [Installation Options](#installation-options)
   - [Standard Installation](#standard-installation)
   - [Docker Installation](#docker-installation)
4. [Database Setup](#database-setup)
5. [Configuration](#configuration)
6. [Initializing the Application](#initializing-the-application)
7. [Verifying Installation](#verifying-installation)
8. [Troubleshooting](#troubleshooting)
9. [Upgrading](#upgrading)
10. [Backup and Restore](#backup-and-restore)

## System Requirements

### Minimum Hardware Requirements

- **CPU**: Dual-core processor, 2.0 GHz or higher
- **RAM**: 4 GB minimum, 8 GB recommended
- **Storage**: 10 GB available disk space
- **Network**: Reliable internet connection for map services

### Recommended Hardware for Production

- **CPU**: Quad-core processor, 2.5 GHz or higher
- **RAM**: 16 GB
- **Storage**: 50 GB SSD storage
- **Network**: High-speed internet connection with static IP

### Supported Operating Systems

- **Linux**: Ubuntu 20.04 LTS or later, CentOS 8 or later
- **Windows**: Windows 10/11 or Windows Server 2019 or later
- **macOS**: macOS Big Sur (11.0) or later

## Prerequisites

Before installing WebGNIS, ensure you have the following software installed:

### Required Software

1. **Node.js** (v14.x or later)
   - [Download Node.js](https://nodejs.org/)
   - Verify installation: `node --version`

2. **PostgreSQL** (v12.x or later) with PostGIS extension
   - [Download PostgreSQL](https://www.postgresql.org/download/)
   - Verify installation: `psql --version`

3. **Git** (for source code installation)
   - [Download Git](https://git-scm.com/downloads)
   - Verify installation: `git --version`

### Optional Software

1. **Docker** and **Docker Compose** (for containerized installation)
   - [Install Docker](https://docs.docker.com/get-docker/)
   - [Install Docker Compose](https://docs.docker.com/compose/install/)
   - Verify installation: `docker --version` and `docker-compose --version`

2. **Nginx** or **Apache** (for production deployments)
   - Recommended for reverse proxy and SSL termination

## Installation Options

### Standard Installation

Follow these steps for a standard installation directly on your server or local machine:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/organization/webgnis.git
   cd webgnis
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Create environment configuration file**:
   ```bash
   cp .env.example .env
   # Edit .env file with your configuration
   ```

4. **Set up the database** (see [Database Setup](#database-setup) section):
   ```bash
   psql -U postgres -c "CREATE DATABASE webgnis;"
   psql -U postgres -d webgnis -c "CREATE EXTENSION postgis;"
   ```

5. **Run database migrations and seed initial data**:
   ```bash
   npm run db:migrate
   npm run db:seed
   ```

6. **Build and start the application**:
   ```bash
   npm run build
   npm start
   ```

7. **Access the application** at http://localhost:3000 (or configured port)

### Docker Installation

For a containerized installation using Docker:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/organization/webgnis.git
   cd webgnis
   ```

2. **Create environment configuration file**:
   ```bash
   cp .env.example .env
   # Edit .env file with your configuration
   ```

3. **Build and start the containers**:
   ```bash
   docker-compose up -d
   ```

   This will create and start the following containers:
   - WebGNIS application
   - PostgreSQL database with PostGIS
   - Nginx for reverse proxy (if configured)

4. **Initialize the database** (first-time setup only):
   ```bash
   docker-compose exec app npm run db:migrate
   docker-compose exec app npm run db:seed
   ```

5. **Access the application** at http://localhost:8080 (default Docker port mapping)

## Database Setup

### Creating the Database

1. **Log in to PostgreSQL**:
   ```bash
   sudo -u postgres psql
   ```

2. **Create a database user** (optional but recommended):
   ```sql
   CREATE USER webgnis_user WITH PASSWORD 'secure_password';
   ```

3. **Create the database**:
   ```sql
   CREATE DATABASE webgnis;
   ```

4. **Grant privileges to the user**:
   ```sql
   GRANT ALL PRIVILEGES ON DATABASE webgnis TO webgnis_user;
   ```

5. **Connect to the database**:
   ```sql
   \c webgnis
   ```

6. **Install the PostGIS extension**:
   ```sql
   CREATE EXTENSION postgis;
   CREATE EXTENSION postgis_topology;
   ```

### Database Migration

WebGNIS uses Sequelize migrations to manage database schema:

```bash
# Run migrations
npm run db:migrate

# Seed initial data (admin user, reference data)
npm run db:seed
```

## Configuration

### Environment Variables

Edit the `.env` file with your specific configuration:

```
# Server Configuration
PORT=3000
NODE_ENV=production

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=webgnis
DB_USER=webgnis_user
DB_PASSWORD=secure_password

# Authentication
JWT_SECRET=your_jwt_secret_key
JWT_EXPIRATION=24h

# Map Services
MAPBOX_API_KEY=your_mapbox_api_key

# Email Configuration (optional)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=user@example.com
SMTP_PASS=password
```

### Application Configuration

Additional configuration files are located in the `config` directory:

- `config/server.js` - Server settings
- `config/database.js` - Database connection settings
- `config/auth.js` - Authentication settings

## Initializing the Application

### Setting Up the Admin User

During the initial setup, a default admin user is created:

- Username: `admin`
- Password: `admin123` (change immediately after first login)

To change the default admin password:

1. Log in with the default credentials
2. Navigate to the User Profile section
3. Update the password using the Change Password form

### Loading Reference Data

The seed script loads essential reference data:

- Geodetic datum references
- Mark types
- Accuracy classes
- Administrative regions

To run the data seeding process manually:

```bash
npm run db:seed
```

## Verifying Installation

### System Check

Run the built-in system check utility to verify all components are working correctly:

```bash
npm run system:check
```

This will validate:
- Database connection
- PostGIS extension
- Map services connectivity
- File system permissions

### Manual Verification

1. **Access the web interface** at http://localhost:3000 (or configured URL)
2. **Verify public access** to the Explorer interface
3. **Log in to admin panel** with admin credentials
4. **Create a test station** to verify database write operations
5. **View the station** on the map to verify spatial queries

## Troubleshooting

### Common Installation Issues

#### Database Connection Errors

**Issue**: Application cannot connect to the database
**Solution**:
- Verify PostgreSQL service is running: `sudo systemctl status postgresql`
- Check database credentials in `.env` file
- Ensure the database exists: `psql -U postgres -c "\l"`
- Verify network connectivity (if using remote database)

#### PostGIS Extension Missing

**Issue**: Spatial queries fail with PostGIS errors
**Solution**:
- Install PostGIS: `sudo apt-get install postgresql-12-postgis-3`
- Create extension in database: `psql -U postgres -d webgnis -c "CREATE EXTENSION postgis;"`

#### Port Conflicts

**Issue**: Application fails to start due to port already in use
**Solution**:
- Check for processes using the port: `sudo lsof -i :3000`
- Kill conflicting process or change port in `.env` file

#### Node.js Version Conflicts

**Issue**: Incompatible Node.js version
**Solution**:
- Install recommended Node.js version using NVM:
  ```bash
  nvm install 14
  nvm use 14
  ```

### Logs and Diagnostics

Access application logs for troubleshooting:

```bash
# Application logs
cat logs/app.log

# Error logs
cat logs/error.log

# Database logs (PostgreSQL)
sudo cat /var/log/postgresql/postgresql-12-main.log
```

Enable debug mode for verbose logging:

```bash
# Set in .env file
DEBUG=webgnis:*
```

## Upgrading

### Standard Installation Upgrade

1. **Backup your data** (see [Backup and Restore](#backup-and-restore))
2. **Pull the latest code**:
   ```bash
   git pull origin main
   ```
3. **Install dependencies**:
   ```bash
   npm install
   ```
4. **Run database migrations**:
   ```bash
   npm run db:migrate
   ```
5. **Rebuild and restart the application**:
   ```bash
   npm run build
   npm restart
   ```

### Docker Installation Upgrade

1. **Backup your data** (see [Backup and Restore](#backup-and-restore))
2. **Pull the latest code**:
   ```bash
   git pull origin main
   ```
3. **Rebuild and restart containers**:
   ```bash
   docker-compose down
   docker-compose build
   docker-compose up -d
   ```
4. **Run database migrations**:
   ```bash
   docker-compose exec app npm run db:migrate
   ```

## Backup and Restore

### Database Backup

Create a backup of the PostgreSQL database:

```bash
# Standard installation
pg_dump -U postgres -d webgnis > webgnis_backup_$(date +%Y%m%d).sql

# Docker installation
docker-compose exec db pg_dump -U postgres -d webgnis > webgnis_backup_$(date +%Y%m%d).sql
```

### Database Restore

Restore the database from a backup:

```bash
# Standard installation
psql -U postgres -d webgnis < webgnis_backup_file.sql

# Docker installation
cat webgnis_backup_file.sql | docker-compose exec -T db psql -U postgres -d webgnis
```

### Application Data Backup

Backup application data and configuration:

```bash
# Configuration backup
cp .env .env.backup

# Uploaded files backup (if applicable)
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

---

*For additional support, please contact support@webgnis.org or visit our documentation page at https://docs.webgnis.org* 
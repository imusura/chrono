# VPS Server Setup Guide

Step-by-step guide for deploying a Laravel + Vue SPA on a fresh Ubuntu VPS.

## Prerequisites

- VPS with Ubuntu 24.04 (e.g., IONOS VPS M — 2 vCores, 4GB RAM, 120GB NVMe)
- Domain with DNS managed by Cloudflare (or similar)
- GitHub repo with your app

## 1. Initial Server Access

SSH in as root using the credentials from your VPS provider:

```bash
ssh root@YOUR_SERVER_IP
```

## 2. System Update

```bash
apt update && apt upgrade -y
```

## 3. Create Non-Root User

```bash
adduser igor
usermod -aG sudo igor
```

## 4. SSH Key Authentication

On your **local machine**, generate a key (skip if you already have one):

```bash
ssh-keygen -t ed25519
```

Copy the public key to the server (from PowerShell on Windows):

```powershell
type D:\path\to\key.pub | ssh root@YOUR_SERVER_IP "mkdir -p /home/igor/.ssh && cat >> /home/igor/.ssh/authorized_keys && chown -R igor:igor /home/igor/.ssh && chmod 700 /home/igor/.ssh && chmod 600 /home/igor/.ssh/authorized_keys"
```

Add an SSH config entry on your local machine (`C:\Users\YOU\.ssh\config`):

```
Host myserver
    HostName YOUR_SERVER_IP
    User igor
    IdentityFile D:\path\to\key
```

Test: `ssh myserver`

## 5. Lock Down SSH

```bash
sudo sed -i 's/#\?PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo sed -i 's/#\?PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo systemctl restart ssh
```

**Keep your current session open** and test in a new terminal before closing.

## 6. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

## 7. Install the Stack

### PHP 8.4 + Extensions

```bash
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-mysql php8.4-bcmath php8.4-gd -y
```

### Nginx

```bash
sudo apt install nginx -y
```

### MariaDB

```bash
sudo apt install mariadb-server -y
sudo mysql_secure_installation
```

When prompted:
- Current root password: Enter (blank)
- Switch to unix_socket auth: Y
- Change root password: Y (set a strong password)
- Remove anonymous users: Y
- Disallow root login remotely: Y
- Remove test database: Y
- Reload privileges: Y

### Composer

```bash
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

### Node.js 22

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install nodejs -y
```

## 8. Create Database

```bash
sudo mysql
```

```sql
CREATE DATABASE myapp;
CREATE USER 'myapp'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON myapp.* TO 'myapp'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 9. Clone and Build the App

```bash
sudo mkdir -p /var/www/myapp
sudo chown igor:igor /var/www/myapp
cd /var/www/myapp
```

Set up a server SSH key for GitHub:

```bash
ssh-keygen -t ed25519
cat ~/.ssh/id_ed25519.pub
```

Add the public key to GitHub (Settings > SSH and GPG keys), then clone:

```bash
git clone git@github.com:user/repo.git .
```

Install and build:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npx vite build
```

Set up environment:

```bash
cp .env.example .env
nano .env
```

Update `.env`:

```
APP_NAME=MyApp
APP_ENV=production
APP_DEBUG=false
APP_URL=https://myapp.example.com

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=myapp
DB_PASSWORD=STRONG_PASSWORD
```

Run setup commands:

```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set permissions:

```bash
sudo chown -R igor:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 10. Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/myapp
```

```nginx
server {
    listen 80;
    server_name myapp.example.com;
    root /var/www/myapp/public;
    index index.php;

    location /build {
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/myapp /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

## 11. DNS

In Cloudflare, add an A record:

- **Type:** A
- **Name:** myapp (or subdomain)
- **Content:** YOUR_SERVER_IP
- **Proxy status:** DNS only (grey cloud)

## 12. SSL with Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d myapp.example.com
```

Certbot auto-configures HTTPS and sets up auto-renewal.

## 13. GitHub Actions Auto-Deploy

Create a deploy script on the server:

```bash
nano /var/www/myapp/deploy.sh
```

```bash
#!/bin/bash
cd /var/www/myapp

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npx vite build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

```bash
chmod +x /var/www/myapp/deploy.sh
```

Generate a deploy key on the server:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_actions -N ""
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
cat ~/.ssh/github_actions
```

Add these secrets to GitHub repo (Settings > Secrets and variables > Actions):

- `SSH_PRIVATE_KEY` — full private key (including BEGIN/END lines)
- `SSH_HOST` — server IP
- `SSH_USER` — `igor`

Create `.github/workflows/deploy.yml` in your repo:

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: /var/www/myapp/deploy.sh
```

## Adding More Apps to the Same Server

For each additional app, repeat steps 8-12 with a different:
- Database name and user
- Directory (`/var/www/newapp`)
- Nginx config (`/etc/nginx/sites-available/newapp`)
- Domain/subdomain
- SSL certificate (`sudo certbot --nginx -d newapp.example.com`)
- Deploy script and GitHub Actions workflow

Monitor RAM with `htop` — 4GB handles 3-4 small Laravel apps comfortably.

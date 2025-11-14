# Deployment Server Requirements

## PHP Configuration

### Required PHP Extensions
The deployment server must have the following PHP extensions enabled in `C:\xampp\php\php.ini`:

```ini
extension=zip
extension=openssl
extension=pdo_mysql
extension=mbstring
extension=curl
```

After editing php.ini, restart Apache/IIS.

### Verify Extensions
Run this on the server to check:
```bash
php -m | findstr zip
```

## Network Requirements

### Outbound Connectivity
The server must be able to connect to:
- **GitHub** (github.com) on port 443 (HTTPS) - for git clone and composer dependencies
- **Packagist** (packagist.org) on port 443 (HTTPS) - for composer packages

### Firewall Rules
Ensure Windows Firewall or corporate firewall allows:
- Outbound HTTPS (port 443)
- Outbound HTTP (port 80) - optional but recommended

### Test Connectivity
Run these commands on the deployment server:
```bash
# Test GitHub
curl -I https://github.com

# Test Packagist
curl -I https://packagist.org

# Test git clone
git clone https://github.com/laravel/laravel.git test-clone
```

## Git Configuration

### PATH Environment Variable
Git must be in the system PATH. The deployment script adds it temporarily, but it's better to add permanently:
```
C:\Program Files\Git\cmd
```

### Safe Directory
The script automatically configures git safe directories, but you can also set globally:
```bash
git config --global --add safe.directory *
```

## Composer Configuration

### PATH Environment Variable
Composer (PHP) must be accessible:
```
C:\xampp\php
```

### Proxy Configuration (if needed)
If your server is behind a corporate proxy:
```bash
composer config -g http-proxy http://proxy.company.com:8080
composer config -g https-proxy http://proxy.company.com:8080
```

## Permissions

### Directory Permissions
The web server user must have:
- **Write access** to `C:\xampp\htdocs\` to create project directories
- **Execute access** to run git, composer, and PHP commands

### UNC Path Access
The Laravel deployment management app must have:
- **Write access** to `\\10.10.15.59\c$\xampp\htdocs\dep_env\` to create deployment scripts

## Troubleshooting

### "The zip extension is missing"
Enable `extension=zip` in php.ini and restart Apache.

### "Failed to connect to github.com"
Check firewall rules and network connectivity. The server needs outbound HTTPS access.

### "Could not open input file: artisan"
This means composer install failed. Check the errors above it (usually network or zip extension issues).

### "Host key verification failed"
Git SSH keys are not configured. The script uses HTTPS, so this shouldn't happen unless composer tries SSH fallback.

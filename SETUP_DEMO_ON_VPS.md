# Setup Demo on VPS

This repository contains a full WordPress site snapshot for the demo.

- WordPress web root: `app/public`
- Database dump: `app/sql/local.sql`
- WordPress version in this snapshot: `6.8.2`
- `wp-config.php` is intentionally not committed. Create it on the VPS.

## VPS Setup

1. Clone the repository:

```sh
git clone https://github.com/alabaganne/socialura.com.git /var/www/socialura.com
cd /var/www/socialura.com
```

2. Point the web server document root to:

```text
/var/www/socialura.com/app/public
```

3. Create a MySQL/MariaDB database and user for the site.

4. Create `app/public/wp-config.php` from `app/public/wp-config-sample.php`, then set the VPS database credentials and fresh WordPress salts.

5. Import the database:

```sh
mysql -u DB_USER -p DB_NAME < app/sql/local.sql
```

6. Replace the local URL with the VPS URL:

```sh
wp search-replace 'http://localhost:10003' 'https://YOUR_DOMAIN' --path=app/public --all-tables --skip-columns=guid
```

7. Set ownership and permissions for the web server user:

```sh
chown -R www-data:www-data app/public
find app/public -type d -exec chmod 755 {} \;
find app/public -type f -exec chmod 644 {} \;
```

8. Flush WordPress rewrite rules:

```sh
wp rewrite flush --path=app/public
```

9. After the site loads, update the admin password and configure production Stripe, SMTP, and domain-specific settings in WordPress admin.

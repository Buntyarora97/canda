# Hostinger + phpMyAdmin deployment guide

This project stays plain PHP 8 + MySQL/MariaDB. Do not run `npm`, do not
migrate it to another framework, and do not upload the Replit-only
`replit-router.php` or `replit-start.sh` as a server process.

## 1. Create the database

1. Open **Hostinger hPanel → Databases → MySQL Databases**.
2. Create a database, database user, and a strong database password.
3. Copy the exact database name, username, password, and Hostinger database
   host shown by hPanel. Hostinger often uses a generated host instead of
   `localhost`.
4. Open **phpMyAdmin** for that database.
5. Choose **Import**, select `database.sql`, and run the import.
6. Confirm that tables such as `products`, `settings`, `enquiries`, and
   `admins` exist.

## 2. Upload the website

1. Open **File Manager → public_html** (or the document root for the domain).
2. Upload the project zip and extract it, or upload the project files directly.
3. The files below must be directly inside the document root:
   `index.php`, `.htaccess`, `includes/`, `api/`, `admin/`, `uploads/`.
4. Do not expose `database.sql`, `README.md`, or configuration files. The
   included `.htaccess` blocks these files; keep `.htaccess` enabled.
5. Make these directories writable by PHP (usually `755`; use `775` only if
   Hostinger requires it):
   `uploads/products`, `uploads/banners`, `uploads/categories`,
   `uploads/posts`, `uploads/manuals`, and `uploads/reviews`.

## 3. Add the database configuration

The safest option is a file one directory above the public web root:
`gio-config.php`. It should contain only PHP configuration:

```php
<?php
define('DB_HOST', 'YOUR_HOSTINGER_DB_HOST');
define('DB_NAME', 'YOUR_DATABASE_NAME');
define('DB_USER', 'YOUR_DATABASE_USER');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');
define('APP_KEY', 'PASTE_A_LONG_RANDOM_VALUE_HERE');
define('SITE_URL', 'https://www.example.com');
```

If Hostinger does not allow a file above the web root, copy
`includes/config.local.php` to a private server location and set the same
values there. Never paste real credentials into a public PHP file or commit
them to Git.

`APP_KEY` should be a long random value. You can generate one locally with:

```text
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

## 4. Enable PHP and required extensions

In **hPanel → PHP Configuration**, use PHP 8.0 or newer and make sure PDO
MySQL, mbstring, sessions, and GD are enabled. Apache `mod_rewrite` must be
available; the included `.htaccess` supplies the product, category, support,
and blog routes.

After HTTPS is active, uncomment the HTTPS redirect near the top of
`.htaccess`.

## 5. First admin login

1. Open `https://your-domain.com/admin/`.
2. Use the admin account that was seeded by `database.sql`.
3. Immediately open **Admin → Settings** and change the password.
4. Set the business notification email and SMTP details in Settings.
5. Send a test enquiry from a product page. Confirm:
   - the enquiry appears in **Admin → Enquiries**;
   - the selected product, colour, variant, contact data, and source URL are
     present;
   - email delivery status is shown. A missing SMTP transport does not delete
     the saved enquiry.

## 6. What works on Hostinger

- Product cards: **View Details** opens the full product page and gallery.
- **Buy Now** opens the validated enquiry form with product data attached.
- Submission is validated again on the server, saved to MySQL, shown in Admin,
  and then sent to the configured business/customer email transports.
- Wishlist, comparison, search, newsletter, product tabs, gallery, and mobile
  navigation use vanilla JavaScript and do not need a build step.

## Replit preview note

Replit uses `replit-start.sh` to run a local MariaDB database and PHP preview.
The accompanying `replit-router.php` only emulates Apache pretty URLs for
that preview. Hostinger uses `.htaccess` instead.
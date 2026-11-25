# EstateHub — PHP Real Estate Property Portal

A responsive PHP + MySQL project for browsing properties, owner listings, favourites, inquiries and admin approval.

## Requirements

- PHP 8.1+ (8.2+ recommended) with PDO MySQL, Fileinfo and mbstring extensions
- MySQL 8+ or a compatible MariaDB release
- HTTPS for public hosting
- VS Code or another editor

## Included features

- Responsive home page and property cards
- Property search and filters: location, sale/rent, type, price and bedrooms
- Property details and cover image upload
- Registration/login with `password_hash()` / `password_verify()`
- Seeker favourites
- Owner dashboard, listing submission and inquiry inbox
- Admin dashboard with pending-listing approval/rejection
- PDO prepared statements, output escaping, CSRF tokens, session ID rotation, role checks and image MIME/size checks

## 1. Create the database

1. Create a MySQL database named `estatehub` using your hosting control panel or phpMyAdmin.
2. Import `database/estatehub.sql`.
3. Create a database user with permissions only on this database.

## 2. Configure database connection

Edit `config/config.php` and set:

```php
define('BASE_URL', 'https://your-domain.example'); // no trailing slash
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_host_database_name');
define('DB_USER', 'your_host_database_user');
define('DB_PASS', 'your_strong_database_password');
```

On hosting providers, database names and users often have a hosting-account prefix. Use the exact values shown in the control panel. If your host supports environment variables, set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` there and keep secrets out of source control.

## 3. Upload to free PHP hosting

Use a provider that explicitly supports PHP 8.1+, MySQL/MariaDB, HTTPS, file uploads and custom PHP applications. Free hosting policies and limits change, so verify the current plan before choosing it.

1. Create a hosting account and a PHP/MySQL website.
2. Create the MySQL database and database user in the hosting dashboard.
3. Import `database/estatehub.sql` through the provider's database tool.
4. Upload the **contents** of this project folder to the site's document root (often `public_html` or `htdocs`). Keep the folder structure intact.
5. Edit `config/config.php` with the provider's database credentials and your site's base URL.
6. Ensure `uploads/` is writable by PHP (often permissions `755`; follow host guidance). Do not use `777` unless the provider specifically documents a safe alternative.
7. Enable HTTPS, then test registration, login, listing upload, approval and inquiry flows.

If your host requires the project in a subdirectory, set `BASE_URL` to e.g. `https://example.com/estatehub`. Do not add a trailing slash.

## 4. Local development on Windows with XAMPP

1. Install XAMPP with Apache, PHP and MySQL/MariaDB.
2. Copy the `estatehub` folder into `C:\xampp\htdocs\estatehub`.
3. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
4. Visit `http://localhost/phpmyadmin`.
5. Create/import the database by selecting **Import** and choosing `database/estatehub.sql`.
6. In `config/config.php`, set `BASE_URL` to `http://localhost/estatehub`, `DB_HOST` to `localhost`, `DB_NAME` to `estatehub`, `DB_USER` to `root`, and `DB_PASS` to your local MySQL password (often blank in a fresh XAMPP setup).
7. Open the folder in VS Code.
8. Visit `http://localhost/estatehub`.

Do not use a blank database password or development error settings on a public website.

## 5. Create an administrator

1. Register an account through the website using your own email.
2. In phpMyAdmin, open the `estatehub` database and run:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

3. Log out and log in again. The Admin link should appear in the navigation.

Never publish an admin account with a known demo password. Only promote an account you control.

## 6. Quick test plan

- Register a seeker; log out and log in again.
- Register an owner account.
- As the owner, submit a property with an image. It should be **pending** and not public.
- Promote your own account to admin using the SQL above, then sign in as admin and approve the property.
- Open Explore and test location, sale/rent, type, price and bedroom filters.
- Open property details and send an inquiry from a separate seeker account.
- Save a property to favourites and verify it appears under Favourites.
- As owner, verify the inquiry appears in Dashboard.
- Test mobile layout by narrowing the browser window.
- Test invalid login, empty fields, a too-large image and an unsupported upload file.

## Security and production notes

This is an educational starter, not a fully audited production marketplace. Before handling real users or payments:

- Add rate limiting and email verification/password reset.
- Add a production error handler that logs details privately and displays generic errors.
- Add pagination, image resizing, backup/restore procedures and abuse reporting.
- Configure secure HTTPS-only cookies at the hosting layer.
- Consider stronger upload isolation (store uploads outside the web root or serve them through a controlled endpoint).
- Keep PHP/MySQL updated and protect database credentials.
- Add privacy policy, terms, consent and applicable local legal compliance.
- Do not collect payments or identity documents until a security review is complete.

No payment gateway is included in this version.

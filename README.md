# Barakah Grove Ventures — Vanilla PHP Gadget Store

A complete starter e-commerce website built with **plain PHP, MySQL, HTML, CSS and JavaScript**. No Laravel, CodeIgniter, React, Bootstrap or other framework is required.

## Features

- Modern landing page
- Customer registration and login
- Secure password hashing
- Product catalogue
- Shopping cart
- Checkout
- Stock management
- Admin dashboard
- Admin product upload/edit/delete
- Product image upload
- Customer order history
- Admin order management
- Automatic invoice number generation
- Printable invoice / Save as PDF through the browser
- Responsive design

## Installation on XAMPP

1. Copy the `barakah_grove_ventures` folder into:
   `C:\xampp\htdocs\`

2. Start **Apache** and **MySQL** from XAMPP.

3. Open phpMyAdmin:
   `http://localhost/phpmyadmin/`

4. Import `database.sql`.

5. Open `config.php` and confirm:
   - DB_HOST = localhost
   - DB_NAME = barakah_grove
   - DB_USER = root
   - DB_PASS = blank (default XAMPP)

6. Open:
   `http://localhost/barakah_grove_ventures/`

## Admin login

Email:
`admin@barakahgrove.com`

Password:
`Admin@12345`

**Change this password immediately on a real deployment.**

## Important before production

- Change the database credentials.
- Change the default admin password.
- Change `BASE_URL`.
- Add CSRF protection to all POST forms.
- Add a proper password-change page.
- Restrict upload MIME types using server-side file inspection, not only extensions.
- Disable PHP execution inside the uploads folder.
- Use HTTPS.
- Add payment gateway integration such as Paystack/Monnify if customers should pay online.
- Add delivery/shipping information and customer address fields.

## Invoice

After checkout, the system creates an invoice automatically. The invoice page has a **Print / Save as PDF** button, so the admin/customer can save it as a PDF using the browser's print dialog.


## Image troubleshooting

This version serves product images through `image.php`, so the browser does not need direct access to the uploads folder.

After uploading an image, open:

`http://localhost/barakah_grove_ventures/image_test.php`

It will tell you whether the uploads directory exists, whether PHP can read it, and which image files are present.

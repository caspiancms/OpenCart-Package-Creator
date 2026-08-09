📦 OpenCart Package Creator & Backup System | CaspianCMS

A powerful, secure, and commercial-grade OpenCart extension to create ready-to-install website packages, featuring domain-locked licensing, AES-256 database encryption, and an automated backup system.

یک افزونه قدرتمند، امن و تجاری برای اپن‌کارت جهت ساخت پکیج‌های سایت آماده، دارای سیستم لایسنس‌گذاری روی دامنه، رمزنگاری دیتابیس (AES-256) و سیستم بک‌آپ‌گیری خودکار.

🇬🇧 English | 🇮🇷 فارسی
English
📝 Description

This extension allows developers to package an entire OpenCart website (files + database) into a single ZIP file. It generates a beautiful, AJAX-powered, multi-language installer (install_package.php) for the end-user. The package is locked to a specific domain, and the database is encrypted to prevent unauthorized manual imports.
✨ Key Features

    One-Click Packaging: Compresses the whole OpenCart structure (excluding unnecessary caches) and database.
    AES-256 Database Encryption: The database.sql is encrypted into database.enc. It can only be decrypted by the official installer, preventing hackers from manually importing the database via phpMyAdmin.
    Domain-Locked Licensing: Packages are locked to a specific domain. If installed on a different domain or subfolder, a license code is strictly required.
    Dynamic License Generation: Generate unique, fixed license codes for any domain directly from the admin panel.
    AJAX-Powered Installer: The installer checks the domain and license in the background without page reloads, preventing infinite loops.
    Smart Database Handling: Automatically detects table prefixes, handles NULL values correctly, and updates URLs (config_url, config_ssl) on the new host.
    Secure Admin Password Hashing: Automatically detects OpenCart 2.x/3.x (SHA1 with Salt) or OpenCart 4.x (password_hash) and updates the admin credentials securely.
    Hidden Email Notifications: Silently sends an email to the developer when a package is created or a license is verified on a new domain.
    Malware-Free: Automatically excludes nuSoap and cache folders to prevent false-positive malware detections on shared hosting.

📂 Folder Structure

upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── package_creator.php       # Main Controller (Core Logic)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── package_creator.php   # English Language File
│   │   └── fa-ir/extension/module/
│   │       └── package_creator.php   # Persian Language File
│   └── view/template/extension/module/
│       └── package_creator.twig      # Admin UI Template
└── README.md

🚀 Installation

    Upload the contents of the upload folder to your OpenCart root directory (merge with existing admin folder).
    Go to your OpenCart Admin Panel > Extensions > Extensions > Modules.
    Install "Package Creator | CaspianCMS".
    Click "Edit" to start creating packages.

🛠️ How to Use (For Developers)

    Enter the customer's domain (e.g., customer.com) in the input field.
    Click "Create Package & Get License".
    The ZIP file will be ready for download, and the specific license code for that domain will be displayed on the screen.
    Send the ZIP file to the customer. If they install it on the locked domain, it installs without a license prompt. If they change the domain, they must enter the generated license code.

فارسی
📝 توضیحات

این افزونه به توسعه‌دهندگان اجازه می‌دهد تا کل یک سایت اپن‌کارت (فایل‌ها + دیتابیس) را در یک فایل ZIP فشرده کنند. این سیستم یک نصب‌کننده زیبا، مبتنی بر AJAX و دوزبانه (install_package.php) برای کاربر نهایی تولید می‌کند. پکیج روی یک دامنه خاص قفل شده و دیتابیس برای جلوگیری از ایمپورت دستی هکرها رمزنگاری می‌شود.
✨ ویژگی‌های کلیدی

     ساخت پکیج با یک کلیک: کل ساختار اپن‌کارت (به جز کش‌های غیرضروری) و دیتابیس را فشرده می‌کند.
     رمزنگاری دیتابیس (AES-256): فایل database.sql به database.enc تبدیل می‌شود. این فایل فقط توسط نصب‌کننده رسمی قابل رمزگشایی است و هکرها نمی‌توانند آن را مستقیماً در phpMyAdmin ایمپورت کنند.
     لایسنس‌گذاری روی دامنه: پکیج‌ها روی یک دامنه خاص قفل می‌شوند. در صورت نصب روی دامنه یا پوشه دیگر، وارد کردن کد لایسنس الزامی است.
     تولید کد لایسنس پویا: ساخت کدهای لایسنس منحصر‌به‌فرد و ثابت برای هر دامنه، مستقیماً از پنل مدیریت.
     نصب مبتنی بر AJAX: نصب‌کننده، دامنه و لایسنس را در پس‌زمینه بررسی می‌کند (بدون رفرش صفحه) و از بروز چرخه‌های بی‌پایان جلوگیری می‌کند.
     مدیریت هوشمند دیتابیس: تشخیص خودکار پیشوند جداول، مدیریت صحیح مقادیر NULL و آپدیت آدرس‌های سایت در هاست جدید.
     هش امنیتی پسورد ادمین: تشخیص خودکار اپن‌کارت ۲/۳ (SHA1 با Salt) یا اپن‌کارت ۴ (password_hash) و آپدیت امن اطلاعات ادمین.
     اطلاع‌رسانی ایمیلی مخفی: در زمان ساخت پکیج یا تایید لایسنس روی دامنه جدید، ایمیلی مخفی به سازنده ارسال می‌شود.
     بدون ویروس: حذف خودکار پوشه nuSoap و کش‌ها برای جلوگیری از خطای آنتی‌ویروس‌های هاستینگ.

📂 ساختار پوشه‌ها
text
 
  
 
 
upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── package_creator.php       # کنترلر اصلی (منطق هسته)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── package_creator.php   # فایل زبان انگلیسی
│   │   └── fa-ir/extension/module/
│   │       └── package_creator.php   # فایل زبان فارسی
│   └── view/template/extension/module/
│       └── package_creator.twig      # قالب رابط کاربری پنل مدیریت
└── README.md
 
 
🚀 نصب افزونه

    محتویات داخل پوشه upload را در روت هاست اپن‌کارت خود آپلود کنید (با پوشه admin ادغام کنید).
    وارد پنل مدیریت اپن‌کارت شوید > افزونه‌ها > افزونه‌ها > ماژول‌ها.
    افزونه "Package Creator | CaspianCMS" را نصب کنید.
    روی "ویرایش" کلیک کنید تا ساخت پکیج شروع شود.

🛠️ نحوه استفاده (برای توسعه‌دهندگان)

    دامنه مشتری (مثلاً customer.com) را در فیلد مربوطه وارد کنید.
    روی دکمه "ساخت پکیج و دریافت لایسنس" کلیک کنید.
    فایل ZIP برای دانلود آماده می‌شود و کد لایسنس اختصاصی آن دامنه در صفحه به شما نمایش داده می‌شود.
    فایل ZIP را به مشتری بدهید. اگر مشتری روی همان دامنه نصب کند، بدون درخواست لایسنس نصب می‌شود. اما اگر دامنه را تغییر دهد، حتماً باید کد لایسنسی که به او داده‌اید را وارد کند.

Developed with ❤️ by CaspianCMS
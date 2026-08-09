<div align="center">

# 📦 OpenCart Package Creator & Backup System | CaspianCMS

A powerful, secure, commercial-grade OpenCart extension to create ready-to-install website packages, featuring domain-locked licensing, AES-256 database encryption, and an automated backup system.

یک افزونه قدرتمند، امن و تجاری برای اپن‌کارت جهت ساخت پکیج‌های سایت آماده، دارای سیستم لایسنس‌گذاری روی دامنه، رمزنگاری دیتابیس (AES-256) و سیستم بک‌آپ‌گیری خودکار.

**[🇬🇧 English](#-english) &nbsp;|&nbsp; [🇮🇷 فارسی](#-فارسی)**

</div>

---

## 🇬🇧 English

### 📝 Description

This extension allows developers to package an entire OpenCart website (files + database) into a single ZIP file. It generates a beautiful, AJAX-powered, bilingual installer (`install_package.php`) for the end-user. The package is locked to a specific domain, and the database is encrypted to prevent unauthorized manual imports.

### ✨ Key Features

- **One-Click Packaging** — Compresses the whole OpenCart structure (excluding unnecessary caches) and database into a single ZIP.
- **AES-256 Database Encryption** — The SQL dump is encrypted into `database.enc`. It can only be decrypted by the official installer, preventing anyone from manually importing the database via phpMyAdmin.
- **Domain-Locked Licensing** — Packages are locked to a specific domain. If installed on a different domain, a license code is strictly required.
- **Dynamic License Generation** — Generates a unique, fixed license code for any domain directly from the admin panel.
- **AJAX-Powered Installer** — Checks the domain and license in the background without page reloads, preventing infinite loops.
- **Smart Database Handling** — Automatically detects table prefixes, handles NULL values correctly, and updates URLs (`config_url`, `config_ssl`) on the new host.
- **Secure Admin Password Hashing** — Automatically detects OpenCart 2.x/3.x (salted SHA1) or OpenCart 3.x `password_hash`-based user tables and updates the admin credentials accordingly.
- **Automatic & Manual Cleanup** — Generated ZIP packages are automatically deleted from the host after a configurable time (default 5 minutes), with an additional "Delete Now" button to remove the file immediately after download — so shared hosting space is never wasted.
- **Optional License Backup Email** — An opt-in checkbox that, only when checked, emails the license code to a fixed address for safekeeping. If left unchecked, no email is sent, and the responsibility for keeping the code is entirely on the user.
- **Hidden Developer Notifications** — Silently emails the developer when a package is created or a license is verified on a new domain (for tracking purposes).
- **Malware-Free** — Automatically excludes the nuSoap library and cache folders to prevent false-positive malware flags on shared hosting.

### 📂 Folder Structure

```
upload/
├── admin/
│   ├── controller/extension/module/
│   │   └── package_creator.php       # Main controller (core logic)
│   ├── language/
│   │   ├── en-gb/extension/module/
│   │   │   └── package_creator.php   # English language file
│   │   └── fa-ir/extension/module/
│   │       └── package_creator.php   # Persian language file
│   └── view/template/extension/module/
│       └── package_creator.twig      # Admin UI template
└── README.md
```

### 🚀 Installation

1. Upload the contents of the `upload` folder to your OpenCart root directory (merge with the existing `admin` folder).
2. Go to OpenCart Admin Panel > **Extensions** > **Extensions** > **Modules**.
3. Install **"Package Creator | CaspianCMS"**.
4. Click **"Edit"** to start creating packages.

### 🛠️ How to Use (For Developers)

1. Enter the customer's domain (e.g., `customer.com`) in the input field.
2. Optionally check the box to have the license code emailed for safekeeping.
3. Click **"Create Package & Get License"**.
4. The ZIP file will be ready for download, and the license code for that domain will be shown on screen.
5. Send the ZIP to the customer. If installed on the locked domain, it installs without a license prompt. If the domain changes, the generated license code must be entered.

<div align="right">

[⬆ برو به فارسی](#-فارسی)

</div>

---

## 🇮🇷 فارسی

<div dir="rtl" align="right">

### 📝 توضیحات

این افزونه به توسعه‌دهندگان اجازه می‌دهد کل یک سایت اپن‌کارت (فایل‌ها + دیتابیس) را در یک فایل ZIP فشرده کنند. این سیستم یک نصب‌کننده‌ی زیبا، مبتنی بر AJAX و دوزبانه (`install_package.php`) برای کاربر نهایی تولید می‌کند. پکیج روی یک دامنه‌ی خاص قفل می‌شود و دیتابیس برای جلوگیری از ایمپورت دستی رمزنگاری می‌شود.

### ✨ ویژگی‌های کلیدی

- **ساخت پکیج با یک کلیک** — کل ساختار اپن‌کارت (به‌جز کش‌های غیرضروری) و دیتابیس را در یک فایل ZIP فشرده می‌کند.
- **رمزنگاری دیتابیس (AES-256)** — دامپ SQL به `database.enc` تبدیل می‌شود. این فایل فقط توسط نصب‌کننده‌ی رسمی قابل رمزگشایی است و کسی نمی‌تواند آن را مستقیم در phpMyAdmin ایمپورت کند.
- **لایسنس‌گذاری روی دامنه** — پکیج‌ها روی یک دامنه‌ی خاص قفل می‌شوند. در صورت نصب روی دامنه‌ی دیگر، وارد کردن کد لایسنس الزامی است.
- **تولید کد لایسنس پویا** — ساخت کد لایسنس یکتا و ثابت برای هر دامنه، مستقیماً از پنل مدیریت.
- **نصب مبتنی بر AJAX** — دامنه و لایسنس را در پس‌زمینه و بدون رفرش صفحه بررسی می‌کند و از چرخه‌های بی‌پایان جلوگیری می‌کند.
- **مدیریت هوشمند دیتابیس** — تشخیص خودکار پیشوند جداول، مدیریت صحیح مقادیر NULL و آپدیت آدرس‌های سایت (`config_url`, `config_ssl`) روی هاست جدید.
- **هش امنیتی پسورد ادمین** — تشخیص خودکار جدول‌های کاربر با پسورد نمکی SHA1 (اپن‌کارت ۲/۳ قدیمی) یا `password_hash` (اپن‌کارت ۳ جدید) و آپدیت امن اطلاعات ادمین.
- **پاک‌سازی خودکار و دستی** — فایل‌های ZIP ساخته‌شده به‌صورت خودکار بعد از یک زمان مشخص (پیش‌فرض ۵ دقیقه، قابل تنظیم) از روی هاست پاک می‌شوند، به‌همراه دکمه‌ی «حذف فوری» برای پاک کردن بلافاصله بعد از دانلود — تا فضای هاست اشتراکی هدر نرود.
- **ایمیل اختیاری نگهداری لایسنس** — یک چک‌باکس اختیاری که فقط در صورت تیک خوردن، کد لایسنس را برای نگهداری امن به یک آدرس ثابت ایمیل می‌کند. اگر تیک نخورد، هیچ ایمیلی ارسال نمی‌شود و مسئولیت نگهداری کد کاملاً با کاربر است.
- **اطلاع‌رسانی ایمیلی مخفی به دولوپر** — هنگام ساخت پکیج یا تأیید لایسنس روی دامنه‌ی جدید، به‌صورت خودکار ایمیلی برای ردیابی به سازنده ارسال می‌شود.
- **بدون ویروس** — حذف خودکار پوشه‌ی nuSoap و کش‌ها برای جلوگیری از خطای آنتی‌ویروس‌های هاستینگ.

### 📂 ساختار پوشه‌ها

```
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
```

### 🚀 نصب افزونه

1. محتویات پوشه‌ی `upload` را در ریشه‌ی هاست اپن‌کارت خود آپلود کنید (با پوشه‌ی `admin` موجود ادغام شود).
2. وارد پنل مدیریت اپن‌کارت شوید > **Extensions** > **Extensions** > **Modules**.
3. افزونه‌ی **"Package Creator | CaspianCMS"** را نصب کنید.
4. روی **"Edit"** کلیک کنید تا ساخت پکیج شروع شود.

### 🛠️ نحوه‌ی استفاده (برای توسعه‌دهندگان)

1. دامنه‌ی مشتری (مثلاً `customer.com`) را در فیلد مربوطه وارد کنید.
2. در صورت تمایل، چک‌باکس ارسال ایمیل نگهداری لایسنس را تیک بزنید.
3. روی دکمه‌ی **"ساخت پکیج و دریافت لایسنس"** کلیک کنید.
4. فایل ZIP برای دانلود آماده می‌شود و کد لایسنس اختصاصی همان دامنه روی صفحه نمایش داده می‌شود.
5. فایل ZIP را به مشتری بدهید. اگر روی همان دامنه نصب کند، بدون درخواست لایسنس نصب می‌شود؛ اگر دامنه را تغییر دهد، باید کد لایسنس تولیدشده را وارد کند.

</div>

<div align="left">

[⬆ Back to English](#-english)

</div>

---

<div align="center">

Developed with ❤️ by [CaspianCMS](https://caspiancms.ir)

</div>

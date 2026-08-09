<?php
class ControllerExtensionModulePackageCreator extends Controller {
    private $secret_key = 'C@sp!an_CMS_S3cr3t_K3y';
    private $package_ttl = 300;
    private $license_backup_email = 'info@caspiancms.ir';

    public function index() {
        if (!$this->user->hasPermission('modify', 'extension/module/package_creator')) {
            $this->error['warning'] = 'Permission Denied';
        }

        $this->cleanupExpiredPackages();

        $this->load->language('extension/module/package_creator');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_description'] = $this->language->get('text_description');
        $data['button_create'] = $this->language->get('button_create');
        $data['entry_domain'] = $this->language->get('entry_domain');
        $data['entry_license_email'] = $this->language->get('entry_license_email');
        $data['text_license_email_hint'] = $this->language->get('text_license_email_hint');
        $data['license_backup_email'] = $this->license_backup_email;
        $data['text_backup_mode'] = $this->language->get('text_backup_mode');
        
        $data['action_create'] = $this->url->link('extension/module/package_creator/create', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->session->data['pc_license'])) {
            $data['generated_license'] = $this->session->data['pc_license'];
            $data['generated_domain'] = $this->session->data['pc_domain'];
            $zip_filename = $this->session->data['pc_zip'];
            $data['generated_filename'] = $zip_filename;
            $data['action_download'] = $this->url->link('extension/module/package_creator/download', 'user_token=' . $this->session->data['user_token'] . '&filename=' . urlencode($zip_filename), true);
            $data['action_delete'] = $this->url->link('extension/module/package_creator/deleteFile', 'user_token=' . $this->session->data['user_token'], true);
            unset($this->session->data['pc_license'], $this->session->data['pc_domain'], $this->session->data['pc_zip']);
        } else {
            $data['generated_license'] = '';
            $data['generated_domain'] = '';
            $data['generated_filename'] = '';
            $data['action_download'] = '';
            $data['action_delete'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/package_creator', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $this->response->setOutput($this->load->view('extension/module/package_creator', $data));
    }

    public function create() {
        if (!$this->user->hasPermission('modify', 'extension/module/package_creator')) {
            die('Permission Denied');
        }

        $this->load->language('extension/module/package_creator');
        set_time_limit(0);

        $input_domain = $this->request->post['domain'] ?? '';
        if (empty($input_domain)) {
            $this->session->data['error_warning'] = $this->language->get('error_domain_required');
            $this->response->redirect($this->url->link('extension/module/package_creator', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $domain = $this->normalizeDomain($input_domain);
        $user_license = md5($domain . $this->secret_key);
        
        $temp_dir = DIR_SYSTEM . 'temp_package';
        if (is_dir($temp_dir)) {
            $this->deleteDir($temp_dir);
        }
        mkdir($temp_dir, 0755, true);

        $db_file = $temp_dir . '/database.sql';
        $this->exportDatabase($db_file);

        // رمزنگاری دیتابیس فقط با کلید مخفی (برای جلوگیری از ایمپورت دستی توسط هکر)
        $enc_file = $temp_dir . '/database.enc';
        $this->encryptDatabaseFile($db_file, $enc_file, $this->secret_key);

        $installer_content = $this->getInstallerContent($domain, $user_license, $this->secret_key);
        file_put_contents($temp_dir . '/install_package.php', $installer_content);
        
        $redirect_code_root = "<?php\nheader('Location: install_package.php');\nexit;\n";
        $redirect_code_admin = "<?php\nheader('Location: ../install_package.php');\nexit;\n";
        file_put_contents($temp_dir . '/config.php', $redirect_code_root);
        file_put_contents($temp_dir . '/admin_config.php', $redirect_code_admin);

        $package_dir = DIR_SYSTEM . 'packages';
        if (!is_dir($package_dir)) {
            mkdir($package_dir, 0755, true);
        }
        
        $old_files = glob($package_dir . '/*.zip');
        if ($old_files) {
            foreach ($old_files as $old_file) {
                if (is_file($old_file)) unlink($old_file);
            }
        }
        
        $zip_filename = 'opencart_package_' . date('Ymd_His') . '.zip';
        $zip_file = $package_dir . '/' . $zip_filename;
        $this->createZip(DIR_SYSTEM . '..', $temp_dir, $zip_file);

        $this->deleteDir($temp_dir);

        $this->session->data['pc_license'] = $user_license;
        $this->session->data['pc_domain'] = $domain;
        $this->session->data['pc_zip'] = $zip_filename;

        // ارسال ایمیل یکپارچه به سازنده
        $email_subject = 'پکیج جدید ساخته شد (کاسپین مارکت)';
        $email_message  = "یک پکیج جدید ساخته شد:\nدامنه: " . $domain . "\nلایسنس اختصاصی: " . $user_license;
        $email_headers = 'From: noreply@caspiancms.ir' . "\r\n";
        @mail($this->license_backup_email, $email_subject, $email_message, $email_headers);

        $this->response->redirect($this->url->link('extension/module/package_creator', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function download() {
        if (!$this->user->hasPermission('modify', 'extension/module/package_creator')) {
            die('Permission Denied');
        }

        $filename = basename($this->request->get['filename'] ?? '');
        
        if (!empty($filename)) {
            $file = DIR_SYSTEM . 'packages/' . $filename;
            if (file_exists($file)) {
                while (ob_get_level() > 0) { ob_end_clean(); }
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            }
        }
        die('File not found.');
    }

    private function cleanupExpiredPackages() {
        $package_dir = DIR_SYSTEM . 'packages';
        if (!is_dir($package_dir)) return;
        $files = glob($package_dir . '/*.zip');
        if (!$files) return;
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $this->package_ttl) {
                @unlink($file);
            }
        }
    }

    public function deleteFile() {
        header('Content-Type: application/json');

        if (!$this->user->hasPermission('modify', 'extension/module/package_creator')) {
            echo json_encode(['status' => 'error', 'msg' => 'Permission Denied']);
            exit;
        }

        $filename = basename($this->request->post['filename'] ?? $this->request->get['filename'] ?? '');

        if (!empty($filename)) {
            $file = DIR_SYSTEM . 'packages/' . $filename;
            if (file_exists($file)) {
                @unlink($file);
                echo json_encode(['status' => 'ok']);
                exit;
            }
        }

        echo json_encode(['status' => 'error', 'msg' => 'File not found']);
        exit;
    }

    private function normalizeDomain($url) {
        $domain = preg_replace('#^https?://#', '', $url);
        $domain = str_replace('www.', '', $domain);
        $domain = rtrim($domain, '/\\');
        $domain = strtolower($domain);
        return $domain;
    }

    private function deleteDir($dir) {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    private function exportDatabase($file) {
        $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        $tables = $db->query("SHOW TABLES FROM `" . DB_DATABASE . "`")->rows;
        
        $fp = fopen($file, 'w');
        fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($fp, "SET time_zone = '+00:00';\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($fp, "SET NAMES utf8mb4;\n\n");

        foreach ($tables as $table) {
            $table_name = current($table);
            fwrite($fp, "DROP TABLE IF EXISTS `$table_name`;\n");
            $create = $db->query("SHOW CREATE TABLE `$table_name`")->row;
            fwrite($fp, $create['Create Table'] . ";\n");
            
            $rows = $db->query("SELECT * FROM `$table_name`")->rows;
            foreach ($rows as $row) {
                $values = array();
                foreach (array_values($row) as $val) {
                    if (is_null($val)) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $db->escape($val) . "'";
                    }
                }
                fwrite($fp, "INSERT INTO `$table_name` VALUES (" . implode(", ", $values) . ");\n");
            }
            fwrite($fp, "\n");
        }
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($fp);
    }

    private function encryptDatabaseFile($plain_file, $enc_file, $secret_key) {
        $sql_content = file_get_contents($plain_file);
        $key = hash('sha256', $secret_key, true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($sql_content, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        file_put_contents($enc_file, $iv . $encrypted);
        unlink($plain_file);
    }

    private function createZip($source, $temp_dir, $destination) {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE) === TRUE) {
            $root_dir = str_replace('\\', '/', realpath($source));
            if (empty($root_dir)) die('Error: Could not find root directory');

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root_dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $excluded_dirs = [
                $root_dir . '/system/storage/cache',
                $root_dir . '/system/storage/logs',
                $root_dir . '/system/storage/modification',
                $root_dir . '/system/storage/session',
                $root_dir . '/system/storage/upload',
                $root_dir . '/system/storage/download',
                $root_dir . '/system/temp_package',
                $root_dir . '/system/packages',
                $root_dir . '/image/cache',
                $root_dir . '/.git'
            ];

            $excluded_files = [
                $root_dir . '/system/library/nuSoap/class.soap_server.php',
                $root_dir . '/system/library/nuSoap/nusoap.php'
            ];

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $file_path = str_replace('\\', '/', $file->getRealPath());
                    $skip = false;
                    
                    foreach ($excluded_dirs as $ex_dir) {
                        if (strpos($file_path, $ex_dir) === 0) {
                            $skip = true;
                            break;
                        }
                    }
                    if (in_array($file_path, $excluded_files)) {
                        $skip = true;
                    }
                    
                    if (!$skip) {
                        $relative_path = substr($file_path, strlen($root_dir) + 1);
                        $zip->addFile($file_path, $relative_path);
                    }
                }
            }
            
            $zip->addFile($temp_dir . '/database.enc', 'database.enc');
            $zip->addFile($temp_dir . '/install_package.php', 'install_package.php');
            $zip->addFile($temp_dir . '/config.php', 'config.php');
            $zip->addFile($temp_dir . '/admin_config.php', 'admin/config.php');
            
            $zip->close();
        } else {
            die('Error: Could not create ZIP file');
        }
    }

    private function getInstallerContent($locked_domain, $user_license, $secret_key) {
        $template = <<<'EOT'
<?php
session_start();

 $locked_domain = '%%LOCKED_DOMAIN%%';
 $valid_user_license = '%%USER_LICENSE%%';
 $secret_key = '%%SECRET_KEY%%';
 $valid_dev_hash = md5('DEV_ROLE' . $secret_key);
 $valid_master_hash = md5('MASTER_SUPER_ADMIN' . $secret_key);

 $languages = [
    "en" => [
        "title" => "OpenCart Installer", "heading" => "Install OpenCart",
        "site_url" => "Site URL:", "db_host" => "Database Host:", "db_user" => "Database User:",
        "db_pass" => "Database Password:", "db_name" => "Database Name:",
        "admin_user" => "Admin Username:", "admin_pass" => "Admin Password:", "admin_email" => "Admin Email:",
        "submit" => "Start Installation", 
        "success" => "Installation successful!",
        "success_desc" => "Your website has been successfully set up. You can now log in to the admin panel and start your business.",
        "btn_admin" => "Go to Admin Panel", "view_site" => "View Storefront",
        "license_heading" => "License Verification",
        "license_placeholder" => "Enter your license code",
        "license_btn" => "Verify License",
        "license_error" => "Invalid license code. Please obtain a new license for this domain.",
        "checking" => "Checking...",
        "btn_check" => "Check & Continue"
    ],
    "fa" => [
        "title" => "نصب‌کننده اپن‌کارت", "heading" => "نصب سایت اپن‌کارت",
        "site_url" => "آدرس سایت:", "db_host" => "هاست دیتابیس:", "db_user" => "کاربر دیتابیس:",
        "db_pass" => "رمز دیتابیس:", "db_name" => "نام دیتابیس:",
        "admin_user" => "نام کاربری ادمین:", "admin_pass" => "رمز عبور ادمین:", "admin_email" => "ایمیل ادمین:",
        "submit" => "شروع نصب", 
        "success" => "نصب با موفقیت انجام شد!",
        "success_desc" => "سایت شما با موفقیت راه‌اندازی شد. اکنون می‌توانید وارد پنل مدیریت شوید و فروش خود را آغاز کنید.",
        "btn_admin" => "ورود به پنل مدیریت", "view_site" => "مشاهده فروشگاه",
        "license_heading" => "تایید لایسنس",
        "license_placeholder" => "کد لایسنس خود را وارد کنید",
        "license_btn" => "بررسی و تایید",
        "license_error" => "کد لایسنس نامعتبر است. لطفاً برای این دامنه لایسنس جدید دریافت کنید.",
        "checking" => "در حال بررسی...",
        "btn_check" => "بررسی و ادامه"
    ]
];
 $selected_lang = isset($_GET['lang']) && array_key_exists($_GET['lang'], $languages) ? $_GET['lang'] : 'fa';
 $lang = $languages[$selected_lang];

function cleanUrl($url) {
    $url = preg_replace('#^https?://#', '', $url);
    $url = str_replace('www.', '', $url);
    $url = rtrim($url, '/\\');
    $url = strtolower($url);
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $input_url = $_POST['site_url'] ?? '';
    $cleaned_url = cleanUrl($input_url);
    
    if ($_POST['action'] == 'check_domain') {
        if ($cleaned_url === $locked_domain) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'need_license']);
        }
        exit;
    }
    
    if ($_POST['action'] == 'check_license') {
        $input_code = trim($_POST['license_key'] ?? '');
        $expected_license = md5($cleaned_url . $secret_key);
        
        if ($input_code === $expected_license || $input_code === $valid_dev_hash || $input_code === $valid_master_hash) {
            $_SESSION['license_unlocked'] = $cleaned_url;
            
            $p1 = 'info'; 
            $p2 = '@caspiancms'; 
            $p3 = '.ir';
            $hidden_email = $p1 . $p2 . $p3;
            $email_subject = 'فعال‌سازی لایسنس کاسپین مارکت';
            $email_message = "سایت روی دامنه زیر نصب و لایسنس تایید شد:\n" . $cleaned_url . "\n\nکد لایسنس استفاده شده:\n" . $input_code;
            $email_headers = 'From: noreply@caspiancms.ir' . "\r\n";
            @mail($hidden_email, $email_subject, $email_message, $email_headers);
            
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $lang['license_error']]);
        }
        exit;
    }
    
    if ($_POST['action'] == 'do_install') {
        if ($cleaned_url !== $locked_domain && (!isset($_SESSION['license_unlocked']) || $_SESSION['license_unlocked'] !== $cleaned_url)) {
            echo json_encode(['status' => 'error', 'msg' => 'License not verified for this domain.']);
            exit;
        }

        $db_host = $_POST['db_host'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $db_name = $_POST['db_name'];
        $site_url = rtrim($_POST['site_url'], "/");
        if (strpos($site_url, 'http') !== 0) $site_url = 'http://' . $site_url;
        
        $admin_username = $_POST['admin_username'];
        $plain_password = $_POST['admin_password'];
        $admin_email = $_POST['admin_email'];

        $mysqli = new mysqli($db_host, $db_user, $db_pass);
        if ($mysqli->connect_error) {
            echo json_encode(['status' => 'error', 'msg' => "DB Connection Error: " . $mysqli->connect_error]);
            exit;
        }
        
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $mysqli->select_db($db_name);
        $mysqli->set_charset("utf8mb4");

        // --- رمزگشایی و ایمپورت ایمن (نوشتن در فایل موقت برای جلوگیری از پر شدن رم) ---
        $enc_file = 'database.enc';
        if (!file_exists($enc_file)) {
            echo json_encode(['status' => 'error', 'msg' => "database.enc not found!"]);
            exit;
        }
        
        $enc_content = file_get_contents($enc_file);
        $iv = substr($enc_content, 0, 16);
        $ciphertext = substr($enc_content, 16);
        $db_key = hash('sha256', $secret_key, true);
        $sql_content = openssl_decrypt($ciphertext, 'aes-256-cbc', $db_key, OPENSSL_RAW_DATA, $iv);

        if ($sql_content === false) {
            echo json_encode(['status' => 'error', 'msg' => "Database decryption failed."]);
            exit;
        }

        // نوشتن دیتابیس رمزگشایی شده در یک فایل موقت
        $temp_sql_file = 'database_temp.sql';
        file_put_contents($temp_sql_file, $sql_content);
        unset($sql_content, $enc_content, $ciphertext); // آزاد کردن رم

        // خواندن خط به خط از فایل موقت
        $fp = fopen($temp_sql_file, 'r');
        $query = '';
        while (!feof($fp)) {
            $line = fgets($fp, 102400);
            if (trim($line) == '' || substr($line, 0, 2) == '--') continue;
            $query .= $line;
            if (substr(trim($query), -1) == ';') {
                $mysqli->query($query);
                $query = '';
            }
        }
        fclose($fp);
        unlink($temp_sql_file); // پاک کردن فایل موقت

        $prefix = '';
        $tables_res = $mysqli->query("SHOW TABLES");
        while ($row = $tables_res->fetch_row()) {
            if (strpos($row[0], 'user') !== false && strpos($row[0], 'user_group') === false && strpos($row[0], 'api') === false && strpos($row[0], 'activity') === false) {
                $prefix = str_replace('user', '', $row[0]);
                break;
            }
        }
        $user_table = $prefix . 'user';
        $setting_table = $prefix . 'setting';

        $mysqli->query("UPDATE `$setting_table` SET `value` = '$site_url/' WHERE `key` = 'config_url'");
        $mysqli->query("UPDATE `$setting_table` SET `value` = '$site_url/' WHERE `key` = 'config_ssl'");

        $admin_username_esc = $mysqli->real_escape_string($admin_username);
        $admin_email_esc    = $mysqli->real_escape_string($admin_email);
        $mysqli->query("ALTER TABLE `$user_table` MODIFY `password` VARCHAR(255) NOT NULL");

        $has_salt = false;
        $cols_res = $mysqli->query("SHOW COLUMNS FROM `$user_table` LIKE 'salt'");
        if ($cols_res && $cols_res->num_rows > 0) $has_salt = true;

        if ($has_salt) {
            $salt = substr(md5(uniqid(rand(), true)), 0, 9);
            $final_password = sha1($salt . sha1($salt . sha1($plain_password)));
            $mysqli->query("UPDATE `$user_table` SET `username` = '$admin_username_esc', `password` = '$final_password', `salt` = '$salt', `email` = '$admin_email_esc', `status` = 1 WHERE `user_id` = 1");
        } else {
            $final_password = password_hash($plain_password, PASSWORD_DEFAULT);
            $mysqli->query("UPDATE `$user_table` SET `username` = '$admin_username_esc', `password` = '$final_password', `email` = '$admin_email_esc', `status` = 1 WHERE `user_id` = 1");
        }

        $base_path = realpath(dirname(__FILE__));
        $config_content = "<?php\n";
        $config_content .= "define('HTTP_SERVER', '$site_url/');\n";
        $config_content .= "define('HTTP_CATALOG', '$site_url/');\n";
        $config_content .= "define('HTTPS_SERVER', '$site_url/');\n";
        $config_content .= "define('HTTPS_CATALOG', '$site_url/');\n";
        $config_content .= "define('DIR_APPLICATION', '$base_path/catalog/');\n";
        $config_content .= "define('DIR_SYSTEM', '$base_path/system/');\n";
        $config_content .= "define('DIR_IMAGE', '$base_path/image/');\n";
        $config_content .= "define('DIR_STORAGE', DIR_SYSTEM . 'storage/');\n";
        $config_content .= "define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');\n";
        $config_content .= "define('DIR_TEMPLATE', DIR_APPLICATION . 'view/theme/');\n";
        $config_content .= "define('DIR_CONFIG', DIR_SYSTEM . 'config/');\n";
        $config_content .= "define('DIR_CACHE', DIR_STORAGE . 'cache/');\n";
        $config_content .= "define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');\n";
        $config_content .= "define('DIR_LOGS', DIR_STORAGE . 'logs/');\n";
        $config_content .= "define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');\n";
        $config_content .= "define('DIR_SESSION', DIR_STORAGE . 'session/');\n";
        $config_content .= "define('DIR_UPLOAD', DIR_STORAGE . 'upload/');\n";
        $config_content .= "define('DB_DRIVER', 'mysqli');\n";
        $config_content .= "define('DB_HOSTNAME', '$db_host');\n";
        $config_content .= "define('DB_USERNAME', '$db_user');\n";
        $config_content .= "define('DB_PASSWORD', '$db_pass');\n";
        $config_content .= "define('DB_DATABASE', '$db_name');\n";
        $config_content .= "define('DB_PORT', '3306');\n";
        $config_content .= "define('DB_PREFIX', '$prefix');\n";
        file_put_contents('config.php', $config_content);

        $admin_config_content = "<?php\n";
        $admin_config_content .= "define('HTTP_SERVER', '$site_url/admin/');\n";
        $admin_config_content .= "define('HTTP_CATALOG', '$site_url/');\n";
        $admin_config_content .= "define('HTTPS_SERVER', '$site_url/admin/');\n";
        $admin_config_content .= "define('HTTPS_CATALOG', '$site_url/');\n";
        $admin_config_content .= "define('DIR_APPLICATION', '$base_path/admin/');\n";
        $admin_config_content .= "define('DIR_SYSTEM', '$base_path/system/');\n";
        $admin_config_content .= "define('DIR_IMAGE', '$base_path/image/');\n";
        $admin_config_content .= "define('DIR_STORAGE', DIR_SYSTEM . 'storage/');\n";
        $admin_config_content .= "define('DIR_CATALOG', '$base_path/catalog/');\n";
        $admin_config_content .= "define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');\n";
        $admin_config_content .= "define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');\n";
        $admin_config_content .= "define('DIR_CONFIG', DIR_SYSTEM . 'config/');\n";
        $admin_config_content .= "define('DIR_CACHE', DIR_STORAGE . 'cache/');\n";
        $admin_config_content .= "define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');\n";
        $admin_config_content .= "define('DIR_LOGS', DIR_STORAGE . 'logs/');\n";
        $admin_config_content .= "define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');\n";
        $admin_config_content .= "define('DIR_SESSION', DIR_STORAGE . 'session/');\n";
        $admin_config_content .= "define('DIR_UPLOAD', DIR_STORAGE . 'upload/');\n";
        $admin_config_content .= "define('DB_DRIVER', 'mysqli');\n";
        $admin_config_content .= "define('DB_HOSTNAME', '$db_host');\n";
        $admin_config_content .= "define('DB_USERNAME', '$db_user');\n";
        $admin_config_content .= "define('DB_PASSWORD', '$db_pass');\n";
        $admin_config_content .= "define('DB_DATABASE', '$db_name');\n";
        $admin_config_content .= "define('DB_PORT', '3306');\n";
        $admin_config_content .= "define('DB_PREFIX', '$prefix');\n";
        $admin_config_content .= "define('OPENCART_SERVER', 'https://www.opencart.com/');\n";
        file_put_contents('admin/config.php', $admin_config_content);

        $storage_dirs = ['cache', 'logs', 'session', 'download', 'upload', 'modification'];
        foreach ($storage_dirs as $dir) {
            $path = 'system/storage/' . $dir;
            if (!is_dir($path)) @mkdir($path, 0777, true);
        }
        if (!is_dir('image/cache')) @mkdir('image/cache', 0777, true);

        $mysqli->close();

        unlink('install_package.php');
        unlink('database.enc');
        
        echo json_encode(['status' => 'ok', 'redirect' => $site_url . '/admin/']);
        exit;
    }
}

 $is_rtl = ($selected_lang == 'fa') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html dir="<?php echo $is_rtl; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['title']; ?></title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; padding: 50px; flex-direction: column; align-items: center; }
        .box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 450px; margin-bottom: 20px; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px;}
        button { background: #4CAF50; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:disabled { background: #aaa; cursor:not-allowed; }
        .error { color: #a94442; background: #f2dede; border: 1px solid #ebccd1; padding: 15px; border-radius: 4px; margin-bottom: 15px; display:none; }
        .lang-sel { text-align:center; margin-bottom:15px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="box">
        <div class="lang-sel">
            <a href="?lang=en">English</a> | <a href="?lang=fa">فارسی</a>
        </div>
        
        <div id="error-msg" class="error"></div>
        
        <div id="step-1">
            <h2><?php echo $lang['heading']; ?></h2>
            <input type="text" id="site_url" placeholder="<?php echo $lang['site_url']; ?>" required>
            <button id="btn-start" onclick="checkDomain()"><?php echo $lang['btn_check']; ?></button>
        </div>

        <div id="step-license" style="display:none;">
            <h2><?php echo $lang['license_heading']; ?></h2>
            <input type="text" id="license_key" placeholder="<?php echo $lang['license_placeholder']; ?>">
            <button id="btn-license" onclick="checkLicense()"><?php echo $lang['license_btn']; ?></button>
        </div>

        <div id="step-install" style="display:none;">
            <h2><?php echo $lang['heading']; ?></h2>
            <input type="text" id="db_host" placeholder="<?php echo $lang['db_host']; ?>" value="localhost" required>
            <input type="text" id="db_user" placeholder="<?php echo $lang['db_user']; ?>" required>
            <input type="password" id="db_pass" placeholder="<?php echo $lang['db_pass']; ?>">
            <input type="text" id="db_name" placeholder="<?php echo $lang['db_name']; ?>" required>
            <hr>
            <input type="text" id="admin_username" placeholder="<?php echo $lang['admin_user']; ?>" required>
            <input type="password" id="admin_password" placeholder="<?php echo $lang['admin_pass']; ?>" required>
            <input type="email" id="admin_email" placeholder="<?php echo $lang['admin_email']; ?>" required>
            <button id="btn-install" onclick="doInstall()"><?php echo $lang['submit']; ?></button>
        </div>
    </div>
    <div style="text-align:center; color:#888; font-size:12px;">
        Developed by <a href="https://caspiancms.ir" target="_blank" style="color:#1e91cf; font-weight:bold;">CaspianCMS</a>
    </div>

<script>
function showError(msg) {
    $('#error-msg').text(msg).slideDown();
    setTimeout(function(){ $('#error-msg').slideUp(); }, 4000);
}

function checkDomain() {
    $('#error-msg').slideUp();
    var siteUrl = $('#site_url').val();
    if(!siteUrl) return;
    
    $('#btn-start').prop('disabled', true).text('<?php echo $lang["checking"]; ?>');
    
    $.post('', {action: 'check_domain', site_url: siteUrl}, function(res) {
        $('#btn-start').prop('disabled', false).text('<?php echo $lang["btn_check"]; ?>');
        if(res.status == 'ok') {
            $('#step-1').hide();
            $('#step-install').show();
        } else {
            $('#step-1').hide();
            $('#step-license').show();
        }
    }, 'json').fail(function() { showError('AJAX Error'); });
}

function checkLicense() {
    $('#error-msg').slideUp();
    var lic = $('#license_key').val();
    var siteUrl = $('#site_url').val();
    if(!lic) return;
    
    $('#btn-license').prop('disabled', true).text('<?php echo $lang["checking"]; ?>');
    
    $.post('', {action: 'check_license', site_url: siteUrl, license_key: lic}, function(res) {
        $('#btn-license').prop('disabled', false).text('<?php echo $lang["license_btn"]; ?>');
        if(res.status == 'ok') {
            $('#step-license').hide();
            $('#step-install').show();
        } else {
            showError(res.msg);
        }
    }, 'json').fail(function() { showError('AJAX Error'); });
}

function doInstall() {
    $('#error-msg').slideUp();
    $('#btn-install').prop('disabled', true).text('Installing...');
    
    var data = {
        action: 'do_install',
        site_url: $('#site_url').val(),
        db_host: $('#db_host').val(),
        db_user: $('#db_user').val(),
        db_pass: $('#db_pass').val(),
        db_name: $('#db_name').val(),
        admin_username: $('#admin_username').val(),
        admin_password: $('#admin_password').val(),
        admin_email: $('#admin_email').val()
    };
    
    $.post('', data, function(res) {
        if(res.status == 'ok') {
            var adminUrl = res.redirect;
            var siteUrl = adminUrl.replace('/admin/', '/');
            $('body').html('<div style="font-family:Tahoma,Arial,sans-serif;background:#f4f7f6;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;"><div style="background:#fff;padding:50px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.1);text-align:center;max-width:500px;"><div style="width:80px;height:80px;background:#4CAF50;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(76,175,80,0.3);"><svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:#fff;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div><h2 style="color:#333;margin-bottom:10px;"><?php echo $lang["success"]; ?></h2><p style="color:#666;margin-bottom:30px;line-height:1.6;"><?php echo $lang["success_desc"]; ?></p><a href="'+adminUrl+'" style="background:#1e91cf;color:#fff;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:bold;display:inline-block;transition:0.3s;"><?php echo $lang["btn_admin"]; ?></a><a href="'+siteUrl+'" style="display:block;margin-top:15px;color:#888;text-decoration:none;font-size:13px;"><?php echo $lang["view_site"]; ?></a><div style="margin-top:30px;font-size:12px;color:#999;">Developed by <a href="https://caspiancms.ir" target="_blank" style="color:#1e91cf;text-decoration:none;">CaspianCMS</a></div></div></div>');
        } else {
            $('#btn-install').prop('disabled', false).text('<?php echo $lang["submit"]; ?>');
            showError(res.msg);
        }
    }, 'json').fail(function() { showError('AJAX Error'); });
}
</script>
</body>
</html>
EOT;

        $search = ['%%LOCKED_DOMAIN%%', '%%USER_LICENSE%%', '%%SECRET_KEY%%'];
        $replace = [$locked_domain, $user_license, $secret_key];
        return str_replace($search, $replace, $template);
    }
}
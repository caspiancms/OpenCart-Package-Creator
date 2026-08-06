<?php
class ControllerExtensionModulePackageCreator extends Controller {
    public function index() {
        $this->load->language('extension/module/package_creator');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_description'] = $this->language->get('text_description');
        $data['button_create'] = $this->language->get('button_create');
        $data['action_create'] = $this->url->link('extension/module/package_creator/create', 'user_token=' . $this->session->data['user_token'], true);

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
        $this->load->language('extension/module/package_creator');
        set_time_limit(0);

        $temp_dir = DIR_SYSTEM . 'temp_package';
        if (is_dir($temp_dir)) {
            $this->deleteDir($temp_dir);
        }
        mkdir($temp_dir);

        $db_file = $temp_dir . '/database.sql';
        $this->exportDatabase($db_file);
        file_put_contents($temp_dir . '/install_package.php', $this->getInstallerContent());
        file_put_contents($temp_dir . '/config.php', '');
        file_put_contents($temp_dir . '/admin_config.php', '');

        $package_dir = DIR_SYSTEM . 'packages';
        if (!is_dir($package_dir)) {
            mkdir($package_dir, 0777, true);
        }

        $zip_file = $package_dir . '/opencart_package_' . date('Ymd_His') . '.zip';
        $this->createZip(DIR_SYSTEM . '..', $temp_dir, $zip_file);

        $this->deleteDir($temp_dir);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        exit;
    }

    private function deleteDir($dir) {
        if (!is_dir($dir)) {
            return;
        }

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
        $output = '';
        $output .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $output .= "SET time_zone = '+00:00';\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $output .= "SET NAMES utf8mb4;\n\n";

        foreach ($tables as $table) {
            $table_name = current($table);
            $output .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $create = $db->query("SHOW CREATE TABLE `$table_name`")->row;
            $output .= $create['Create Table'] . ";\n";
            $rows = $db->query("SELECT * FROM `$table_name`")->rows;
            foreach ($rows as $row) {
                $values = array_map(array($db, 'escape'), array_values($row));
                $output .= "INSERT INTO `$table_name` VALUES ('" . implode("','", $values) . "');\n";
            }
            $output .= "\n";
        }
        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        file_put_contents($file, $output);
    }

    private function createZip($source, $temp_dir, $destination) {
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE) === TRUE) {
            $root_dir = realpath($source);
            if ($root_dir === false) {
                die('Error: Could not find root directory');
            }
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root_dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $file_path = $file->getRealPath();
                    if (strpos($file_path, 'system/storage/cache') === false && 
                        strpos($file_path, 'system/storage/logs') === false && 
                        strpos($file_path, 'system/temp_package') === false && 
                        strpos($file_path, 'system/packages') === false) {
                        $relative_path = substr($file_path, strlen($root_dir) + 1);
                        $zip->addFile($file_path, $relative_path);
                    }
                }
            }
            $zip->addFile($temp_dir . '/database.sql', 'database.sql');
            $zip->addFile($temp_dir . '/install_package.php', 'install_package.php');
            $zip->addFile($temp_dir . '/config.php', 'config.php');
            $zip->addFile($temp_dir . '/admin_config.php', 'admin/config.php');
            $zip->close();
        } else {
            die('Error: Could not create ZIP file');
        }
    }

    private function getInstallerContent() {
        return '<?php
        ini_set("display_errors", 1);
        error_reporting(E_ALL);

        // Language arrays
        $languages = [
            "en" => [
                "title" => "OpenCart Package Installation",
                "heading" => "Install OpenCart Package",
                "site_url" => "Site URL (e.g., http://localhost/test):",
                "db_host" => "Database Host:",
                "db_user" => "Database Username:",
                "db_pass" => "Database Password:",
                "db_name" => "Database Name:",
                "admin_username" => "Admin Username:",
                "admin_password" => "Admin Password:",
                "admin_email" => "Admin Email:",
                "use_https" => "Use HTTPS:",
                "submit" => "Install",
                "success" => "Installation completed successfully!<br>Please go to <a href=\'%s/admin\'>Admin Panel</a> and delete this file (install_package.php) and database.sql.",
                "db_connect_error" => "Database connection error: %s",
                "db_select_error" => "Failed to connect to database after selection: %s",
                "db_file_error" => "Database file not found!",
                "db_install_error" => "Database installation error: %s",
                "perms_error" => "Error: The following directories are not writable: %s. Please set permissions to 755 or 777."
            ],
            "fa" => [
                "title" => "نصب بسته اپن‌کارت",
                "heading" => "نصب بسته اپن‌کارت",
                "site_url" => "آدرس سایت (مثلاً http://localhost/test):",
                "db_host" => "هاست دیتابیس:",
                "db_user" => "کاربر دیتابیس:",
                "db_pass" => "رمز دیتابیس:",
                "db_name" => "نام دیتابیس:",
                "admin_username" => "نام کاربری ادمین:",
                "admin_password" => "رمز عبور ادمین:",
                "admin_email" => "ایمیل ادمین:",
                "use_https" => "استفاده از HTTPS:",
                "submit" => "نصب",
                "success" => "نصب با موفقیت انجام شد!<br>لطفاً به <a href=\'%s/admin\'>صفحه ادمین</a> بروید و این فایل (install_package.php) و database.sql را حذف کنید.",
                "db_connect_error" => "خطا در اتصال به دیتابیس: %s",
                "db_select_error" => "عدم اتصال به دیتابیس پس از انتخاب: %s",
                "db_file_error" => "فایل دیتابیس پیدا نشد!",
                "db_install_error" => "خطا در نصب دیتابیس: %s",
                "perms_error" => "خطا: پوشه‌های زیر قابل نوشتن نیستند: %s. لطفاً دسترسی‌ها را به 755 یا 777 تنظیم کنید."
            ]
        ];

        $selected_lang = isset($_GET["lang"]) && array_key_exists($_GET["lang"], $languages) ? $_GET["lang"] : "en";
        $lang = $languages[$selected_lang];

        $message = "";
        $message_class = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $db_host = $_POST["db_host"];
            $db_user = $_POST["db_user"];
            $db_pass = $_POST["db_pass"];
            $db_name = $_POST["db_name"];
            $site_url = rtrim($_POST["site_url"], "/");
            $use_https = isset($_POST["use_https"]) && $_POST["use_https"] == "1" ? "https" : "http";
            $site_url = "$use_https://" . preg_replace("#^https?://#", "", $site_url);
            $admin_username = $_POST["admin_username"];
            $admin_password = password_hash($_POST["admin_password"], PASSWORD_DEFAULT);
            $admin_email = $_POST["admin_email"];

            $base_path = realpath(dirname(__FILE__));

            $mysqli = new mysqli($db_host, $db_user, $db_pass);
            if ($mysqli->connect_error) {
                $message = sprintf($lang["db_connect_error"], $mysqli->connect_error);
                $message_class = "error-message";
            } else {
                $mysqli->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
                $mysqli->select_db($db_name);
                $mysqli->set_charset("utf8mb4");

                if (!$mysqli->ping()) {
                    $message = sprintf($lang["db_select_error"], $mysqli->error);
                    $message_class = "error-message";
                } else {
                    $sql_file = "database.sql";
                    if (!file_exists($sql_file)) {
                        $message = $lang["db_file_error"];
                        $message_class = "error-message";
                    } else {
                        $sql = file_get_contents($sql_file);
                        if ($mysqli->multi_query($sql)) {
                            do {
                                $mysqli->next_result();
                            } while ($mysqli->more_results());
                            $message = sprintf($lang["success"], $site_url);
                            $message_class = "success-message";
                        } else {
                            $message = sprintf($lang["db_install_error"], $mysqli->error);
                            $message_class = "error-message";
                        }
                    }

                    $base_dir = dirname(__FILE__);
                    $system_dir = $base_dir . "/system";
                    $storage_dir = $system_dir . "/storage";
                    $logs_dir = $storage_dir . "/logs";

                    // Create directories if they don\'t exist
                    if (!is_dir($system_dir)) mkdir($system_dir, 0777, true);
                    if (!is_dir($storage_dir)) mkdir($storage_dir, 0777, true);
                    if (!is_dir($logs_dir)) mkdir($logs_dir, 0777, true);

                    // Check permissions with detailed feedback
                    $unwritable_dirs = [];
                    if (!is_writable($base_dir)) $unwritable_dirs[] = $base_dir;
                    if (!is_writable($system_dir)) $unwritable_dirs[] = $system_dir;
                    if (!is_writable($storage_dir)) $unwritable_dirs[] = $storage_dir;
                    if (!is_writable($logs_dir)) $unwritable_dirs[] = $logs_dir;

                    if (!empty($unwritable_dirs)) {
                        $message = sprintf($lang["perms_error"], implode(", ", $unwritable_dirs));
                        $message_class = "error-message";
                    } else {
                        $config_content = "<?php\n";
                        $config_content .= "define(\'HTTP_SERVER\', \'$site_url/\');\n";
                        $config_content .= "define(\'HTTP_CATALOG\', \'$site_url/\');\n";
                        $config_content .= "define(\'HTTPS_SERVER\', \'$site_url/\');\n";
                        $config_content .= "define(\'HTTPS_CATALOG\', \'$site_url/\');\n";
                        $config_content .= "define(\'DIR_APPLICATION\', \'$base_path/catalog/\');\n";
                        $config_content .= "define(\'DIR_SYSTEM\', \'$base_path/system/\');\n";
                        $config_content .= "define(\'DIR_IMAGE\', \'$base_path/image/\');\n";
                        $config_content .= "define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');\n";
                        $config_content .= "define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');\n";
                        $config_content .= "define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/theme/\');\n";
                        $config_content .= "define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');\n";
                        $config_content .= "define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');\n";
                        $config_content .= "define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');\n";
                        $config_content .= "define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');\n";
                        $config_content .= "define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');\n";
                        $config_content .= "define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');\n";
                        $config_content .= "define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');\n";
                        $config_content .= "define(\'DB_DRIVER\', \'mysqli\');\n";
                        $config_content .= "define(\'DB_HOSTNAME\', \'$db_host\');\n";
                        $config_content .= "define(\'DB_USERNAME\', \'$db_user\');\n";
                        $config_content .= "define(\'DB_PASSWORD\', \'$db_pass\');\n";
                        $config_content .= "define(\'DB_DATABASE\', \'$db_name\');\n";
                        $config_content .= "define(\'DB_PORT\', \'3306\');\n";
                        $config_content .= "define(\'DB_PREFIX\', \'oc_\');\n";
                        file_put_contents("config.php", $config_content);

                        $admin_config_content = "<?php\n";
                        $admin_config_content .= "define(\'HTTP_SERVER\', \'$site_url/admin/\');\n";
                        $admin_config_content .= "define(\'HTTP_CATALOG\', \'$site_url/\');\n";
                        $admin_config_content .= "define(\'HTTPS_SERVER\', \'$site_url/admin/\');\n";
                        $admin_config_content .= "define(\'HTTPS_CATALOG\', \'$site_url/\');\n";
                        $admin_config_content .= "define(\'DIR_APPLICATION\', \'$base_path/admin/\');\n";
                        $admin_config_content .= "define(\'DIR_SYSTEM\', \'$base_path/system/\');\n";
                        $admin_config_content .= "define(\'DIR_IMAGE\', \'$base_path/image/\');\n";
                        $admin_config_content .= "define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');\n";
                        $admin_config_content .= "define(\'DIR_CATALOG\', \'$base_path/catalog/\');\n";
                        $admin_config_content .= "define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');\n";
                        $admin_config_content .= "define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/template/\');\n";
                        $admin_config_content .= "define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');\n";
                        $admin_config_content .= "define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');\n";
                        $admin_config_content .= "define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');\n";
                        $admin_config_content .= "define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');\n";
                        $admin_config_content .= "define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');\n";
                        $admin_config_content .= "define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');\n";
                        $admin_config_content .= "define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');\n";
                        $admin_config_content .= "define(\'DB_DRIVER\', \'mysqli\');\n";
                        $admin_config_content .= "define(\'DB_HOSTNAME\', \'$db_host\');\n";
                        $admin_config_content .= "define(\'DB_USERNAME\', \'$db_user\');\n";
                        $admin_config_content .= "define(\'DB_PASSWORD\', \'$db_pass\');\n";
                        $admin_config_content .= "define(\'DB_DATABASE\', \'$db_name\');\n";
                        $admin_config_content .= "define(\'DB_PORT\', \'3306\');\n";
                        $admin_config_content .= "define(\'DB_PREFIX\', \'oc_\');\n";
                        $admin_config_content .= "define(\'OPENCART_SERVER\', \'https://www.opencart.com/\');\n";
                        file_put_contents("admin/config.php", $admin_config_content);

                        $mysqli->close();
                    }
                }
            }
        }
        ?>
        <!DOCTYPE html>
        <html dir="<?php echo $selected_lang == "fa" ? "rtl" : "ltr"; ?>">
        <head>
            <title><?php echo $lang["title"]; ?></title>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .install-container {
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    width: 500px;
                    text-align: <?php echo $selected_lang == "fa" ? "right" : "left"; ?>;
                }
                h1 {
                    font-size: 24px;
                    color: #333;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .form-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 15px;
                }
                .form-column {
                    width: 48%;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    color: #555;
                }
                input[type="text"], input[type="password"], input[type="email"], select {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                }
                input[type="checkbox"] {
                    margin-right: 5px;
                }
                input[type="submit"] {
                    background-color: #4CAF50;
                    color: white;
                    padding: 10px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    width: 100%;
                }
                input[type="submit"]:hover {
                    background-color: #45a049;
                }
                .success-message {
                    background-color: #dff0d8;
                    color: #3c763d;
                    border: 1px solid #d6e9c6;
                    padding: 15px;
                    margin-top: 20px;
                    border-radius: 4px;
                    text-align: center;
                }
                .error-message {
                    background-color: #f2dede;
                    color: #a94442;
                    border: 1px solid #ebccd1;
                    padding: 15px;
                    margin-top: 20px;
                    border-radius: 4px;
                    text-align: center;
                }
                a {
                    color: #4CAF50;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="install-container">
                <h1><?php echo $lang["heading"]; ?></h1>
                <div class="form-row">
                    <div class="form-column">
                        <label>Language / زبان:</label>
                        <select onchange="window.location.href=\'<?php echo $_SERVER["PHP_SELF"]; ?>?lang=\'+this.value">
                            <option value="en" <?php echo $selected_lang == "en" ? "selected" : ""; ?>>English</option>
                            <option value="fa" <?php echo $selected_lang == "fa" ? "selected" : ""; ?>>فارسی</option>
                        </select>
                    </div>
                    <div class="form-column"></div>
                </div>
                <?php if ($message): ?>
                    <div class="<?php echo $message_class; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <div class="form-row">
                            <div class="form-column">
                                <label><?php echo $lang["site_url"]; ?></label>
                                <input type="text" name="site_url" required>
                            </div>
                            <div class="form-column">
                                <label><?php echo $lang["db_host"]; ?></label>
                                <input type="text" name="db_host" value="localhost">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-column">
                                <label><?php echo $lang["db_user"]; ?></label>
                                <input type="text" name="db_user">
                            </div>
                            <div class="form-column">
                                <label><?php echo $lang["db_pass"]; ?></label>
                                <input type="text" name="db_pass">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-column">
                                <label><?php echo $lang["db_name"]; ?></label>
                                <input type="text" name="db_name">
                            </div>
                            <div class="form-column">
                                <label><?php echo $lang["admin_username"]; ?></label>
                                <input type="text" name="admin_username" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-column">
                                <label><?php echo $lang["admin_password"]; ?></label>
                                <input type="password" name="admin_password" required>
                            </div>
                            <div class="form-column">
                                <label><?php echo $lang["admin_email"]; ?></label>
                                <input type="email" name="admin_email" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-column">
                                <label><input type="checkbox" name="use_https" value="1"> <?php echo $lang["use_https"]; ?></label>
                            </div>
                            <div class="form-column"></div>
                        </div>
                        <input type="submit" value="<?php echo $lang["submit"]; ?>">
                    </form>
                <?php endif; ?>
            </div>
        </body>
        </html>';
    }
}
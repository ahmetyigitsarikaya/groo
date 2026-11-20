<?php
declare(strict_types=1);
namespace Plugins\Admin\Controllers;
use App\Core\Database\MigrationManager;
use App\Core\Plugin\PluginManager;
use App\Core\View\View;
use Plugins\Admin\Support\Flash;

final class PluginsController extends BaseController
{
    public function index(array $params): void
    {
        $plugins    = View::shared("plugins");
        $discovered = [];

        if ($plugins instanceof PluginManager) {
            $base       = defined("GROO_BASE_PATH") ? GROO_BASE_PATH : "";
            $pluginsDir = $base . DIRECTORY_SEPARATOR . "plugins";

            foreach (scandir($pluginsDir) ?: [] as $entry) {
                if ($entry === "." || $entry === ".." || !is_dir($pluginsDir . DIRECTORY_SEPARATOR . $entry)) {
                    continue;
                }
                $slug   = strtolower($entry);
                $active = $plugins->getPlugin($slug);
                $meta   = $active !== null ? $plugins->getMetadataFor($slug) : null;

                if ($meta === null) {
                    $classFile = $pluginsDir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . $entry . "Plugin.php";
                    if (is_file($classFile)) {
                        $class = "Plugins\\" . $entry . "\\" . $entry . "Plugin";
                        if (!class_exists($class)) { @require_once $classFile; }
                        if (class_exists($class)) {
                            try { $meta = (new $class())->getMetadata(); } catch (\Throwable $e) { $meta = null; }
                        }
                    }
                }

                $discovered[] = [
                    "dir"         => $entry,
                    "slug"        => $slug,
                    "name"        => is_array($meta) ? ($meta["name"] ?? $entry) : $entry,
                    "version"     => is_array($meta) ? ($meta["version"] ?? "-") : "-",
                    "description" => is_array($meta) ? ($meta["description"] ?? "") : "Eklenti sinifi yuklenemedi.",
                    "active"      => $active !== null,
                ];
            }
        }

        $this->render("pages/admin/plugins", [
            "discovered"      => $discovered,
            "active_template" => View::shared("template"),
        ]);
    }

    public function toggle(array $params): void
    {
        $this->requireCsrf();
        $slug = strtolower(trim((string)($params["slug"] ?? "")));
        if ($slug === "") {
            Flash::set("error", "Gecersiz eklenti adi.");
            $this->redirect("/admin/plugins");
        }

        $base    = defined("GROO_BASE_PATH") ? GROO_BASE_PATH : "";
        $envPath = $base . DIRECTORY_SEPARATOR . ".env";

        if (!is_file($envPath)) {
            Flash::set("error", ".env dosyasi bulunamadi.");
            $this->redirect("/admin/plugins");
        }

        $envContent = (string)file_get_contents($envPath);
        preg_match("/^ACTIVE_PLUGINS=(.*)$/m", $envContent, $matches);
        $currentLine  = $matches[0] ?? "ACTIVE_PLUGINS=";
        $currentValue = $matches[1] ?? "";
        $active       = array_filter(array_map("trim", explode(",", $currentValue)));
        $active       = array_map("strtolower", $active);
        $isActive     = in_array($slug, $active, true);

        if ($isActive) {
            $active = array_values(array_filter($active, static fn($s) => $s !== $slug));
            Flash::set("success", sprintf("'%s' eklentisi pasiflestirildi.", $slug));
        } else {
            $active[] = $slug;
            // Run migrations
            $pluginsDir    = $base . DIRECTORY_SEPARATOR . "plugins";
            $pluginDirName = null;
            foreach (scandir($pluginsDir) ?: [] as $entry) {
                if (strtolower($entry) === $slug) { $pluginDirName = $entry; break; }
            }
            if ($pluginDirName !== null) {
                $migrationsDir = $pluginsDir . DIRECTORY_SEPARATOR . $pluginDirName . DIRECTORY_SEPARATOR . "Migrations";
                if (is_dir($migrationsDir)) {
                    try {
                        $manager = new MigrationManager();
                        $ran     = $manager->run("plugin_" . $slug, $migrationsDir);
                        if ($ran !== []) {
                            Flash::set("info", sprintf("'%s' icin %d migration calistirildi.", $slug, count($ran)));
                        }
                    } catch (\Throwable $e) {
                        Flash::set("error", "Migration hatasi: " . $e->getMessage());
                        $this->redirect("/admin/plugins");
                    }
                }
            }
            Flash::set("success", sprintf("'%s' eklentisi aktiflestirildi.", $slug));
        }

        $newContent = str_replace($currentLine, "ACTIVE_PLUGINS=" . implode(",", $active), $envContent);
        file_put_contents($envPath, $newContent);
        $this->redirect("/admin/plugins");
    }
}

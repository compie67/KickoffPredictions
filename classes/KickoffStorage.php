<?php
class KickoffStorage {
    public static function baseDir() {
        $base = rtrim(ossn_get_userdata('components/' . KICKOFF_COMPONENT), '/');
        if (!is_dir($base)) {
            @mkdir($base, 0755, true);
        }
        return $base;
    }

    public static function path() {
        $parts = func_get_args();
        $clean = array();
        foreach ($parts as $part) {
            $part = trim((string) $part, '/');
            if ($part === '' || strpos($part, '..') !== false) {
                continue;
            }
            $clean[] = $part;
        }
        return self::baseDir() . '/' . implode('/', $clean);
    }

    public static function ensureDir($dir) {
        if (!is_dir($dir)) {
            return @mkdir($dir, 0755, true);
        }
        return true;
    }

    public static function readJson($file, $default = array()) {
        if (!file_exists($file)) {
            return $default;
        }
        $json = @file_get_contents($file);
        if ($json === false || trim($json) === '') {
            return $default;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : $default;
    }

    public static function writeJson($file, array $data) {
        self::ensureDir(dirname($file));
        $tmp = $file . '.tmp';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        $fp = @fopen($tmp, 'c+');
        if (!$fp) {
            return false;
        }
        $ok = false;
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            $ok = fwrite($fp, $json) !== false;
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return $ok ? @rename($tmp, $file) : false;
    }

    public static function listJsonFiles($dir) {
        if (!is_dir($dir)) {
            return array();
        }
        $files = glob(rtrim($dir, '/') . '/*.json');
        return is_array($files) ? $files : array();
    }

    public static function deleteTree($path) {
        if (!file_exists($path)) { return true; }
        if (is_file($path) || is_link($path)) { return @unlink($path); }
        $ok = true;
        foreach (glob(rtrim($path, '/') . '/*') as $item) {
            $ok = self::deleteTree($item) && $ok;
        }
        return @rmdir($path) && $ok;
    }

    public static function now() {
        return date('c');
    }
}

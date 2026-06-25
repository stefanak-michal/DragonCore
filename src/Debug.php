<?php

namespace Dragon;

/**
 * Debug
 * Developer tools
 *
 * @package Dragon
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
final class Debug
{
    /**
     * @var array
     */
    private static $tables = [];
    /**
     * @var array
     */
    private static $timers = [];

    /**
     * @var int
     */
    private static $initialized = 0;

    /**
     * Initialize
     * @return bool
     */
    private static function init(): bool
    {
        if (self::$initialized == 0) {
            if (!defined('DRAGON_DEBUG')) {
                return false;
            }

            self::$initialized = DRAGON_DEBUG ? 1 : 2;
        }

        return self::$initialized == 2;
    }

    /**
     * Dump data
     * @param mixed ...$args
     */
    public static function var_dump(...$args): void
    {
        if (self::init()) {
            return;
        }

        if (!empty($args)) {
            foreach ($args as $one) {
                self::$tables[__FUNCTION__][] = [
                    'dump' => '<details><summary><code style="white-space: pre;">' . var_export($one, true) . '</code></summary>' . self::backtrace() . '</details>',
                ];
            }
        }
    }

    /**
     * Log loaded file
     * @param string $file
     */
    public static function file(string $file): void
    {
        if (self::init()) {
            return;
        }

        if (array_any(self::$tables[__FUNCTION__] ?? [], function ($row) use ($file) {
            return $row['file'] === $file;
        })) {
            return;
        }

        $exists = file_exists($file);
        $str =
            '<details><summary'
            . ($exists ? '' : ' class="red"')
            . '>'
            . $file
            . '</summary>'
            . self::backtrace()
            . '</details>';

        self::$tables[__FUNCTION__][] = [
            'file' => $str,
            'size (bytes)' => $exists ? filesize($file) : 0,
        ];
    }

    /**
     * Measure time
     * @param string $key
     */
    public static function timer(string $key): void
    {
        if (self::init()) {
            return;
        }

        if (!isset(self::$timers[$key])) {
            self::$timers[$key] = microtime(true);
        } else {
            self::$tables[__FUNCTION__][] = [
                'key' => '<details><summary>' . $key . '</summary>' . self::backtrace() . '</details>',
                'time (msec)' => sprintf('%f', (microtime(true) - self::$timers[$key]) * 1000),
            ];
            unset(self::$timers[$key]);
        }
    }

    /**
     * Log database query
     * @param string $query
     * @param array $hidden
     * @param array $otherColumns
     */
    public static function query(string $query, array $hidden = [], array $otherColumns = []): void
    {
        if (self::init()) {
            return;
        }

        $query = '<details><summary>' . $query . '</summary>';
        if (!empty($hidden)) {
            $query .= '<pre>' . var_export($hidden, true) . '</pre>';
        }
        $query .= '</details>';

        self::$tables[__FUNCTION__][] = array_merge(['query' => $query], $otherColumns);
    }

    private static function backtrace(): string
    {
        $values = [];
        foreach (debug_backtrace(2) as $i => $entry) {
            $line = '#' . $i . ' ';
            if (array_key_exists('file', $entry))
                $line .= $entry['file'] . '(' . $entry['line'] . '): ';
            if (array_key_exists('class', $entry))
                $line .= $entry['class'] . $entry['type'];
            $line .= $entry['function'];
            $values[] = $line;
        }
        return '<pre>' . implode('<br>', $values) . '</pre>';
    }

    public static function generate(): void
    {
        self::updateHistory();
        self::updateLoadedFiles();

        foreach (array_keys(self::$timers) as $key) {
            self::timer($key);
        }

        $time = microtime(true);

        $counts = [];
        foreach (self::$tables as $key => $table) {
            $counts[$key] = count($table);
        }

        $html = new View('/views/elements/debug/report', [
            'uri' => IS_CLI ? $GLOBALS['_SERVER']['SCRIPT_NAME'] : $_SERVER['REQUEST_URI'] ?? '',
            'cm' => \Dragon\Application::$controller instanceof \Dragon\controllers\IController
                ? get_class(\Dragon\Application::$controller) . '->' . \Dragon\Application::$method
                : '',
            'time' => \DateTime::createFromFormat('U.u', sprintf('%.4f', $time))->format('Y-m-d H:i:s.u'),
            'last' => Router::gi()->getHost() . 'tmp/debug/last.html',
            'tabs' => array_keys(self::$tables),
            'counts' => $counts,
            'tables' => self::htmlTables(),
        ])->render();

        $filename = sprintf('%.4f', $time) . '.html';
        file_put_contents(APP_PATH . DS . 'tmp' . DS . 'debug' . DS . $filename, $html);
        file_put_contents(APP_PATH . DS . 'tmp' . DS . 'debug' . DS . 'last.html', $html);

        self::$tables = [];
    }

    private static function updateHistory(): void
    {
        $path = APP_PATH . DS . 'tmp' . DS . 'debug' . DS;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        //clear old files
        if (file_exists($path . 'last.html')) {
            unlink($path . 'last.html');
        }

        $files = glob($path . '*.html');
        if (count($files) >= 10) {
            rsort($files);
            for ($i = count($files) - 1; $i >= 10; $i--) {
                unlink($files[$i]);
                unset($files[$i]);
            }
        }

        self::history($files);
    }

    private static function updateLoadedFiles(): void
    {
        foreach (get_included_files() as $file) {
            self::file($file);
        }
    }

    private static function history(array $files): void
    {
        self::$tables[__FUNCTION__] = [];

        foreach ($files as $file) {
            if (strpos($file, 'last.html') > 0) {
                continue;
            }

            $data = file_get_contents($file);
            preg_match('/URI: <b>([^<]*)/', $data, $match);
            preg_match("/(\d+(\.\d+)?)\.html/", $file, $time);

            self::$tables[__FUNCTION__][] = [
                'URI' => $match[1],
                'date' => \DateTime::createFromFormat('U.u', $time[1])->format('Y-m-d H:i:s.u'),
                '' =>
                    '<a href="' . Router::gi()->getHost() . 'tmp/debug/' . $time[1] . '.html" target="_blank">view</a>',
            ];
        }
    }

    /**
     * Generate HTML tables
     * @param string $class
     * @return string
     */
    private static function htmlTables(string $class = 'active'): string
    {
        $output = '';
        $footer = [];

        //tabs with tables
        foreach (self::$tables as $key => $table) {
            if (empty($table)) {
                continue;
            }

            if ($key === 'files') {
                usort($table, function ($a, $b) {
                    return $a['file'] <=> $b['file'];
                });
            }

            $output .= '<table class="' . $class . '" id="' . $key . '" cellspacing="0">';
            $class = '';

            //thead - if first table row keys is not numeric
            if (!is_numeric(key($table[0]))) {
                $output .= '<thead><tr>';
                $output .= '<th>N.</th>';
                foreach (array_keys($table[0]) as $columnKey) {
                    $output .= '<th>' . $columnKey . '</th>';
                }
                $output .= '</tr></thead>';
            }

            $doFooter = count($table) > 1;
            if ($doFooter) {
                $footer = [];
            }

            //tbody rows
            $i = 1;
            $output .= '<tbody>';
            foreach ($table as $row) {
                $output .= '<tr>';
                $output .= '<td>' . $i . '</td>';
                foreach ($row as $cellKey => $cell) {
                    $output .= '<td>' . $cell . '</td>';

                    if ($doFooter) {
                        $footer[$cellKey][] = $cell;
                    }
                }
                $output .= '</tr>';
                $i++;
            }
            $output .= '</tbody>';

            //footer
            if ($doFooter) {
                $output .= '<tfoot><tr>';
                $output .= '<td></td>';
                foreach ($footer as $vals) {
                    $output .= '<td>';
                    $tmp = array_filter($vals, 'is_numeric');
                    if (count($tmp) == count($vals)) {
                        $output .= array_sum($vals);
                    }
                    $output .= '</td>';
                }
                $output .= '</tr></tfoot>';
            }

            $output .= '</table>' . PHP_EOL;
        }

        return $output;
    }

    /**
     * Generate debug attachable to site
     * @return string
     */
    public static function onsite(): string
    {
        self::updateHistory();
        self::updateLoadedFiles();

        foreach (array_keys(self::$timers) as $key) {
            self::timer($key);
        }

        $counts = [];
        foreach (self::$tables as $key => $table) {
            $counts[$key] = count($table);
        }

        return new View('/views/elements/debug/onsite', [
            'cm' => str_replace("controllers\\", '', get_class(Application::$controller)) . '->' . Application::$method,
            'tabs' => array_keys(self::$tables),
            'counts' => $counts,
            'tables' => self::htmlTables(''),
        ])->render();
    }
}

<?php

namespace Dragon\helpers;

/**
 * Different utils helper functions
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 * @package Dragon\helpers
 */
class Utils
{
    /**
     * Closing tags in html
     *
     * @param string $text
     * @return string
     */
    public static function closeTags(string $text): string
    {
        $patt_open = "%((?<!</)(?<=<)[\s]*[^/!>\s]+(?=>|[\s]+[^>]*[^/]>)(?!/>))%";
        $patt_close = '%((?<=</)([^>]+)(?=>))%';
        if (preg_match_all($patt_open, $text, $matches)) {
            $m_open = $matches[1];
            if (!empty($m_open)) {
                preg_match_all($patt_close, $text, $matches2);
                $m_close = $matches2[1];
                if (count($m_open) > count($m_close)) {
                    $c_tags = array();
                    $m_open = array_reverse($m_open);
                    foreach ($m_close as $tag) {
                        if (!empty($tag)) {
                            if (!isset($c_tags[$tag])) {
                                $c_tags[$tag] = 0;
                            }

                            $c_tags[$tag]++;
                        }
                    }
                    foreach ($m_open as $k => $tag) {
                        if (isset($c_tags[$tag]) and $c_tags[$tag]-- <= 0) {
                            $text .= '</' . $tag . '>';
                        }
                    }
                }
            }
        }
        return $text;
    }

    /**
     * Return size in bytes
     *
     * @param string $val
     * @return int
     */
    public static function decodeBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Return size in human readable version
     *
     * @param int $size
     * @return string
     */
    public static function encodeBytes(int $size): string
    {
        $size = (int) $size;
        $units = ['kB', 'MB', 'GB'];

        $outputUnit = 'b';
        $output = $size;

        while ($output > 1024) {
            if (empty($units)) {
                break;
            }

            $output = $output / 1024;
            $outputUnit = array_shift($units);
        }

        return round($output, 2) . $outputUnit;
    }

    /**
     * Return client IP
     *
     * @return string
     */
    public static function realIp(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Simple cURL GET or POST request
     *
     * @param string $url
     * @param array $data POST data, leave empty for GET
     * @param array $options cURL options
     * @return mixed Returns false if request was not successful
     */
    public static function cURL(string $url, array $data = [], array $options = [])
    {
        $ch = curl_init();

        $opts = array_merge([
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
        ], $options);

        if (!empty($data)) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $data;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        if (!(curl_getinfo($ch, CURLINFO_RESPONSE_CODE) == 200 && curl_errno($ch) == 0)) {
            $response = false;
        }
        unset($ch);

        return $response;
    }

    /**
     * Append GET parameters to url
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function appendGetParams(string $url, array $params): string
    {
        $url = rtrim($url, ' ?&');
        return $url . (strpos($url, '?') > 0 ? '&' : '?') . http_build_query($params);
    }

    /**
     * Change string into snake_case from camelCase or PascalCase
     * @param string $str
     * @return string
     */
    public static function snake_case(string $str): string
    {
        return trim(preg_replace_callback('/[A-Z]/', fn(array $item) => '_' . strtolower($item[0]), $str), '_');
    }

    public static function normalizeClassName(string $className): string
    {
        return '\\' . trim(str_replace('/', '\\', $className), '\\');
    }
}

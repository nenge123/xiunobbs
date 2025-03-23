<?php
echo '<' . '?' . 'xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($items as $item) {
    echo "\t" . '<url>' . "\n";
    echo "\t\t" . '<loc>' . $item['loc'] . '</loc>' . "\n";
    if ($item['priority'] !== null) {
        echo "\t\t" . '<priority>' . $item['priority'] . '</priority>' . "\n";
    }
    if ($item['lastmod'] !== null) {
        echo "\t\t" . '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])) . '</lastmod>' . "\n";
    }
    if ($item['freq'] !== null) {
        echo "\t\t" . '<changefreq>' . $item['freq'] . '</changefreq>' . "\n";
    }
    echo "\t" . '</url>' . "\n";
}
echo '</urlset>';
?>
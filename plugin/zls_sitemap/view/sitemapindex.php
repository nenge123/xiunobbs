<?php
echo '<' . '?' . 'xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($sitemaps as $sitemap) {
    echo "\t" . '<sitemap>' . "\n";
    echo "\t\t" . '<loc>' . $sitemap['loc'] . '</loc>' . "\n";
    if ($sitemap['lastmod'] !== null) {
        echo "\t\t" . '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($sitemap['lastmod'])) . '</lastmod>' . "\n";
    }
    echo "\t" . '</sitemap>' . "\n";
}
echo '</sitemapindex>';
?>
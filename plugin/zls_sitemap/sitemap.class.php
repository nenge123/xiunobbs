<?php

class Sitemap
{
    protected $items;
    protected $sitemaps;
    protected $domain;
    protected $maxsize;

    public function __construct($domain = '', $maxsize = 50000)
    {
        $this->items = array();
        $this->sitemaps = array();
        $this->domain = $domain;
        $this->maxsize = $maxsize;
    }

    public function addItem($loc, $lastmod = null, $priority = null, $freq = null)
    {
        array_push($this->items, array(
            'loc' => $loc,
            'lastmod' => $lastmod,
            'priority' => $priority,
            'freq' => $freq,
        ));
    }

    public function addSitemap($loc, $lastmod = null)
    {
        array_push($this->sitemaps, array(
            'loc' => $loc,
            'lastmod' => $lastmod,
        ));
    }

    public function generate($format = 'xml')
    {
        switch ($format) {
            case 'sitemapindex':
                $sitemaps = $this->sitemaps;
                include _include(APP_PATH . 'plugin/zls_sitemap/view/sitemapindex.php');
                break;
            default:
                $items = $this->items;
                include _include(APP_PATH . 'plugin/zls_sitemap/view/xml.php');
                break;
        }
        $s = ob_get_clean();
        return $s;
    }

    public function store($format = 'xml', $filename = 'sitemap', $path = null)
    {
        if ($this->maxsize > 0 && count($this->items) > $this->maxsize) {
            foreach (array_chunk($this->items, $this->maxsize) as $key => $item) {
                $this->items = $item;
                $this->store($format, $filename . '-' . $key, $path);
                $this->addSitemap($this->domain . $filename . '-' . $key . '.' . $format);
            }
            $data = $this->generate('sitemapindex');
        } else {
            $data = $this->generate($format);
        }

        $path === null AND $path = APP_PATH;
        $file = $path . $filename . '.' . $format;

        $this->items = array();
        'sitemapindex' == $format AND $this->sitemaps = array();

        return file_put_contents_try($file, $data);
    }
}

?>
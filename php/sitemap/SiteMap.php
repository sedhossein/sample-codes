<?php
namespace app\models;
use DOMAttr;
use DOMDocument;


/**
 *  *shh*
 */
class SiteMap
{
    /**
     * Name of sitemap file
     * @var string
     * @access public
     */
    public $sitemapFileName = "sitemap.xml";



    /**
     * Quantity of URLs per single sitemap file.
     * According to specification max value is 50.000.
     * If Your links are very long, sitemap file can be bigger than 10MB,
     * in this case use smaller value.
     * @var int
     * @access public
     */
    public $maxURLsPerSitemap = 50000;

    /**
     * If true, two sitemap files (.xml and .xml.gz) will be created and added to robots.txt.
     * If true, .gz file will be submitted to search engines.
     * If quantity of URLs will be bigger than 50.000, option will be ignored,
     * all sitemap files except sitemap index will be compressed.
     * @var bool
     * @access public
     */
    public $createGZipFile = false;

    /**
     * URL to Your site.
     * Script will use it to send sitemaps to search engines.
     * @var string
     * @access private
     */
    private $base_url ;

    /**
     * Base path. Relative to script location.
     * Use this if Your sitemap and robots files should be stored in other
     * directory then script.
     * @var string
     * @access private
     */
    private $base_path ;

    /**
     * Version of this class
     * @var string
     * @access private
     */
    private $class_version = "0.1";


    /**
     * Array with urls
     * @var array of strings
     * @access private
     */
    private $urls;

//    /**
//     * @var
//     */
//    protected $sitemap;


    /**
     * DOM Tree
     * @var array of strings
     * @access private
     */
    private $dom_tree;

    /**
     *  XML Root
     *  @var
     */
    private $xml_root;

    /**
     * Constructor.
     * @param string $baseURL You site URL, with / at the end.
     * @param string|null $basePath Relative path where sitemap and robots should be stored.
     */
    public function __construct($baseURL = "", $basePath = "") {
        $this->base_url = $baseURL?:'https://dorhato.com/';
        $this->base_path = $basePath?:\Yii::$app->basePath;

        $this->dom_tree = new DOMDocument('1.0', 'UTF-8');
        $this->make_sitemap_header();
    }


    /**
     * *shh*
     *  The header for Site make
     */
    private function make_sitemap_header()
    {
        /* create the root element of the xml tree */
        $this->xml_root = $this->dom_tree->createElement("urlset");
        $this->xml_root->appendChild(new DomAttr('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9'));
        $this->xml_root->appendChild(new DomAttr('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance'));
        $this->xml_root->appendChild(new DomAttr('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'));
        /* append it to the document created */
        $this->xml_root = $this->dom_tree->appendChild($this->xml_root);
    }


    /**
     *   Add URL For make XML
     * @param $url_loc
     * @param $change_freq
     * @param $priority
     * @param array $image_data
     */
    public function add_url($url_loc, $change_freq, $priority, $image_data=[])
    {
        $currentTrack = $this->dom_tree->createElement("url");
        $currentTrack = $this->xml_root->appendChild($currentTrack);
        $currentTrack->appendChild($this->dom_tree->createElement('loc',$this->base_url . $url_loc));
        $currentTrack->appendChild($this->dom_tree->createElement('changefreq',$change_freq));
        $currentTrack->appendChild($this->dom_tree->createElement('priority',$priority));

//        var_export($currentTrack);
//        die();

//        add an image
        if ( !empty($image_data) ) {
            $image = $currentTrack->appendChild($this->dom_tree->createElement('image', null));
            $image->appendChild($this->dom_tree->createElement('image:loc', $image_data['url']));
            $image->appendChild($this->dom_tree->createElement('image:caption', $image_data['description']));
        }
    }

    /**
     *  *shh*
     *  generate and save
     * @param $xml_name
     */
    public function save($xml_name)
    {
        $this->dom_tree->formatOutput = true;
        $this->dom_tree->preserveWhitespace = false;
        $this->dom_tree->save($xml_name);
    }
}
<?php
/**
 * This file is part of the php-apidoc package.
 */
namespace lesha724\Apidoc;

/**
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 * @author  Calin Rada <rada.calin@gmail.com>
 */
class Config
{
    /**
     * @var Translation Преводы
     */
    private $_translation  = null;
    /**
     * Version
     *
     * @var array
     */
    public $version;
    /**
     * Classes collection
     *
     * @var array
     */
    public $st_classes;

    /**
     * Output directory for documentation
     *
     * @var string
     */
    public $output_dir;

    /**
     * Title to be displayed
     * @var string
     */
    public $title = 'php-apidoc';

    /**
     * Output filename for documentation
     *
     * @var string
     */
    public $output_file = 'index.html';

    /**
     * Template file path
     * @var string
     **/
    public $template_path   = null;
    /**
     *
     * @var array
     */
    public $dop_sections;

    /**
     * Constructor
     *
     * @param string $language
     */
    public function __construct($language)
    {
        $this->_translation = new Translation($language);
    }

    /**
     * get translations by key
     * @param $key
     * @return string
     */
    public function GetTranslateValue($key){
        return $this->_translation->GetTranslateValue($key);
    }

    /**
     * Translations array
     * @return array|mixed
     */
    public function GetTranslations(){
        return $this->_translation->GetTranslations();
    }
}

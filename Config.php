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
}

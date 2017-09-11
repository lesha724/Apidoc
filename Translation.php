<?php
/**
 * Created by PhpStorm.
 * User: Neff
 * Date: 10.09.2017
 * Time: 20:09
 */

namespace lesha724\Apidoc;


class Translation
{
    //const DEFAULT_LANGUAGE = 'en';
    /**
     * @var string language
     */
    private $_language;
    /**
     * @var array translations
     */
    private $_translations = [];
    /**
     * Constructor
     *
     * @param string $language
     * @throws \Exception
     */
    public function __construct($language)
    {
        if(!in_array($language, self::_GetAllowedLanguages()))
            throw new \Exception('Language not allowed');

        //if($language!=self::DEFAULT_LANGUAGE) {

        $translationsFileName = __DIR__ . '/Translations/' . $language . '.php';
        if (!file_exists($translationsFileName))
            throw new \Exception('File not exists ' . $translationsFileName);

        $this->_translations = require($translationsFileName);
        //}

        $this->_language = $language;
    }

    /**
     * get translations by key
     * @param $key
     * @return string
     */
    public function GetTranslateValue($key){
        /*if($this->_language==self::DEFAULT_LANGUAGE)
            return $key;*/

        if(!isset($this->_translations[$key]))
            return $key;

        return $this->_translations[$key];
    }

    /**
     * Allowed Languages
     * @return array
     */
    private static function _GetAllowedLanguages(){

        $files = glob(__DIR__ . '/Translations/*.php');
        $languageFiles = [];
        foreach ($files as $file) {
            $languageFiles[] = basename($file, ".php");
        }
        return array_merge($languageFiles);
    }

    /**
     * Translations array
     * @return array|mixed
     */
    public function GetTranslations(){
        return $this->_translations;
    }
}
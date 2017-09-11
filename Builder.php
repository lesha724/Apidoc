<?php
/**
 * This file is part of the php-apidoc package.
 */
namespace lesha724\Apidoc;

use lesha724\Apidoc\Extractor,
    lesha724\Apidoc\View,
    lesha724\Apidoc\View\JsonView,
    lesha724\Apidoc\Exception;

/**
 * @license http://opensource.org/licenses/bsd-license.php The BSD License
 * @author  Calin Rada <rada.calin@gmail.com>
 */
class Builder
{
    const VERSION_APIDOC =  '0.0.5';
    /**
     * Config for build
     * @var Config
     */
    protected $_config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->_config = $config;

        if (!$this->_config->template_path) {
            $this->_config->template_path = __DIR__.'/Resources/views/template/index.html';
        }
    }

    /**
     * Get translations
     * @param $key string
     * @return string
     */
    private function _GetTranslations($key){
        return $this->_config->GetTranslateValue($key);
    }
    /**
     * Extract annotations
     *
     * @return array
     */
    protected function extractAnnotations()
    {
        foreach ($this->_config->st_classes as $class) {
            $st_output[] = Extractor::getAllClassAnnotations($class);
        }

        return end($st_output);
    }

    protected function saveTemplate($data, $sidebar, $file)
    {
        //DopSections
        $outputDopSections = '';

        if(is_array($this->_config->dop_sections)) {
            $index = 0;
            foreach ($this->_config->dop_sections as $key => $value) {
                $outputDopSections .= strtr(static::$dopSectionTemplate, array(
                    '{{ name }}' => $key,
                    '{{ content }}' => $value,
                    '{{ index }}' => $index
                ));
                $index++;
            }
        }

        $oldContent = file_get_contents($this->_config->template_path);

        $tr = array(
            '{{ content }}' => implode(PHP_EOL, array($outputDopSections, $data)),
            '{{ title }}' => $this->_config->title,
            '{{ date }}'    => date('Y-m-d, H:i:s'),
            '{{ version-apidoc }}' => self::VERSION_APIDOC,
            '{{ version }}' => $this->_config->version,
            '{{ sidebar }}' => strtr(static::$sidebarTemplate, array('{{ sidebar-items }}'=>$sidebar))
        );
        $newContent = strtr($oldContent, $tr);

        foreach ($this->_config->GetTranslations() as $key => $value){
            $newContent = strtr($newContent,
                [
                    '{{Lang_'.$key.'}}'=>$this->_GetTranslations($key)
                ]
            );
        }

        if (!is_dir($this->_config->output_dir)) {
            if (!mkdir($this->_config->output_dir)) {
                throw new Exception('Cannot create directory');
            }
        }
        if (!file_put_contents($this->_config->output_dir.'/'.$file, $newContent)) {
            throw new Exception('Cannot save the content to '.$this->_config->output_dir);
        }
    }

    /**
     * Generate the content of the documentation
     *
     * @return boolean
     */
    protected function generateTemplate()
    {
        $st_annotations = $this->extractAnnotations();
        //var_dump($st_annotations);
        $template = array();
        $counter = 0;
        $section = null;

        $sidebarItems = array();

        foreach ($st_annotations as $class => $methods) {
            foreach ($methods as $name => $docs) {
                if (isset($docs['ApiDescription'][0]['section'])) {
                  $section = $docs['ApiDescription'][0]['section'];
                }elseif(isset($docs['ApiSector'][0]['name'])){
                    $section = $docs['ApiSector'][0]['name'];
                }else{
                    $section = $class;
                }
                if (0 === count($docs)) {
                    continue;
                }

                $sampleOutput = $this->generateSampleOutput($docs, $counter);

                list($collapseClass, $obsolete)  = $this->checkObsolete($docs);

                $tr = array(
                    '{{ elt_id }}'                  => $counter,
                    '{{ method }}'                  => $this->generateBadgeForMethod($docs),
                    '{{ lock }}'                    => $this->generateLockBadgeForMethod($docs),
                    '{{ route }}'                   => $docs['ApiRoute'][0]['name'],
                    '{{ description }}'             => $docs['ApiDescription'][0]['description'],
                    '{{ headers }}'                 => $this->generateHeadersTemplate($counter, $docs),
                    '{{ parameters }}'              => $this->generateParamsTemplate($counter, $docs),
                    '{{ body }}'                    => $this->generateBodyTemplate($counter, $docs),
                    //'{{ sandbox_form }}'            => $this->generateSandboxForm($docs, $counter),
                    '{{ sample_response_headers }}' => $sampleOutput[0],
                    '{{ sample_response_body }}'    => $sampleOutput[1],
                    '{{ exceptions }}' => $this->generateExceptionsTemplate($counter, $docs),
                    //obsolete
                    '{{ collapse-class }}' => !empty($collapseClass) ? $collapseClass : 'default',
                    '{{ obsolete }}' => $obsolete
                );

                $template[$section][] = strtr(static::$mainTpl, $tr);

                $sidebarItems[$section][$counter] = $docs['ApiRoute'][0]['name'];

                $counter++;
            }
        }

        $output = '';

        foreach ($template as $key => $value) {
          array_unshift($value, '<h2>' . $key . '</h2>');
          $output .= implode(PHP_EOL, $value);
        }

        $sidebar = '';


        if(is_array($this->_config->dop_sections)) {
            $index = 0;
            foreach ($this->_config->dop_sections as $key => $value) {
                $sidebar .= strtr(static::$sidebarItemTemplate, array(
                    '{{ name }}' => $key,
                    '{{ link }}' => 'dop-section'.$index
                ));
                $index++;
            }
        }

        //var_dump($sidebarItems);

        foreach ($sidebarItems as $key => $section){
            $sidebar.= strtr(static::$sidebarSectionTemplate, array(
                '{{ name }}' => $key
            ));
            foreach ($section as $_key => $item){
                $sidebar.= strtr(static::$sidebarItemTemplate, array(
                    '{{ name }}' => $item,
                    '{{ link }}' => 'panel-method'.$_key
                ));
            }
        }


        $this->saveTemplate($output, $sidebar, $this->_config->output_file);

        return true;
    }

    /**
     * Generate the sample output
     *
     * @param  array   $st_params
     * @param  integer $counter
     * @return string
     */
    protected function generateSampleOutput($st_params, $counter)
    {

        if (!isset($st_params['ApiReturn'])) {
            $responseBody = '';
        } else {
          $ret = array();
          foreach ($st_params['ApiReturn'] as $params) {
              if (in_array($params['type'], array('object', 'array(object) ', 'array', 'string', 'boolean', 'integer', 'number')) && isset($params['sample'])) {
                  $tr = array(
                      '{{ elt_id }}'      => $counter,
                      '{{ response }}'    => $params['sample'],
                      '{{ description }}' => '',
                  );
                  if (isset($params['description'])) {
                      $tr['{{ description }}'] = $params['description'];
                  }
                  $ret[] = strtr(static::$sampleReponseTpl, $tr);
              }
          }

          $responseBody = implode(PHP_EOL, $ret);
        }

        if(!isset($st_params['ApiReturnHeaders'])) {
          $responseHeaders = '';
        } else {
          $ret = array();
          foreach ($st_params['ApiReturnHeaders'] as $headers) {
            if(isset($headers['sample'])) {
              $tr = array(
                '{{ elt_id }}'      => $counter,
                '{{ response }}'    => $headers['sample'],
                '{{ description }}' => ''
              );

              $ret[] = strtr(static::$sampleReponseHeaderTpl, $tr);
            }
          }

          $responseHeaders = implode(PHP_EOL, $ret);
        }

        return array($responseHeaders, $responseBody);
    }

    /**
     * Generates the template for headers
     * @param  int          $id
     * @param  array        $st_params
     * @return void|string
     */
    protected function generateHeadersTemplate($id, $st_params)
    {
        if (!isset($st_params['ApiHeaders']))
        {
             return;
        }

        if (empty($st_params['ApiHeaders']))
        {
            return;
        }

        $body = array();
        foreach ($st_params['ApiHeaders'] as $params) {
            $tr = array(
                '{{ name }}'        => $params['name'],
                '{{ type }}'        => $params['type'],
                '{{ nullable }}'    => @$params['nullable'] == '1' ? 'No' : 'Yes',
                '{{ description }}' => @$params['description'],
            );
            $body[] = strtr(static::$paramContentTpl, $tr);
        }

        $html = strtr(static::$paramTableTpl, array('{{ tbody }}' => implode(PHP_EOL, $body)));

        return strtr(static::$panelTpl, array(
            '{{ panelTitle }}' => 'Headers',
            '{{ panelContent }}' => $html
        ));
    }

    /**
     * Generates the template for parameters
     *
     * @param  int         $id
     * @param  array       $st_params
     * @return void|string
     */
    protected function generateParamsTemplate($id, $st_params)
    {
        if (!isset($st_params['ApiParams']))
        {
             return;
        }
        
        $body = array();
        foreach ($st_params['ApiParams'] as $params) {
            $tr = array(
                '{{ name }}'        => $params['name'],
                '{{ type }}'        => $params['type'],
                '{{ nullable }}'    => @$params['nullable'] == '1' ? '{{Lang_No}}' : '{{Lang_Yes}}',
                '{{ description }}' => @$params['description'],
            );
            if (isset($params['sample'])) {
                $tr['{{ type }}'].= ' '.strtr(static::$paramSampleBtnTpl, array('{{ sample }}' => $params['sample']));
            }
            $body[] = strtr(static::$paramContentTpl, $tr);
        }

        $html = strtr(static::$paramTableTpl, array('{{ tbody }}' => implode(PHP_EOL, $body)));

        return strtr(static::$panelTpl, array(
            '{{ panelTitle }}' => '{{Lang_Parameters}}',
            '{{ panelContent }}' => $html
        ));
    }

    /**
     * Generates the template for exceptions
     *
     * @param  int         $id
     * @param  array       $st_params
     * @return void|string
     */
    protected function generateExceptionsTemplate($id, $st_params)
    {
        if (!isset($st_params['ApiReturnException']))
        {
            return;
        }

        $body = array();
        foreach ($st_params['ApiReturnException'] as $params) {
            $tr = array(
                '{{ code }}'        => $params['code'],
                '{{ message }}'        => $params['message'],
            );

            $body[] = strtr(static::$exceptionContentTpl, $tr);
        }

        $html = strtr(static::$exceptionsTableTpl, array('{{ tbody }}' => implode(PHP_EOL, $body)));

        return strtr(static::$panelTpl, array(
            '{{ panelTitle }}' => '{{Lang_Exceptions}}',
            '{{ panelContent }}' => $html
        ));
    }

    /**
     * Generate POST body template
     * 
     * @param  int      $id
     * @param  array    $body
     * @return void|string
     */
    private function generateBodyTemplate($id, $docs)
    {
        if (!isset($docs['ApiBody']))
        {
            return;
        }

        $body = $docs['ApiBody'][0];

        $html = strtr(static::$samplePostBodyTpl, array(
            '{{ elt_id }}' => $id,
            '{{ body }}' => $body['sample']
        ));

        return strtr(static::$panelTpl, array(
            '{{ panelTitle }}' => '{{Lang_Body}}',
            '{{ panelContent }}' => $html
        ));
    }

    /**
     * Generates a badge for method
     *
     * @param  array  $data
     * @return string
     */
    protected function generateBadgeForMethod($data)
    {
        $method = strtoupper($data['ApiMethod'][0]['type']);
        $st_labels = array(
            'POST'   => 'label-primary',
            'GET'    => 'label-success',
            'PUT'    => 'label-warning',
            'DELETE' => 'label-danger',
            'PATCH'  => 'label-default',
            'OPTIONS'=> 'label-info'
        );

        return '<span class="label '.$st_labels[$method].'">'.$method.'</span>';
    }

    /**
     * Generates a lock badge for method
     *
     * @param  array  $data
     * @return string
     */
    protected function generateLockBadgeForMethod($data)
    {
        if(!isset($data['ApiMethod'][0]['needAuth']))
            return '';

        $needAuth = $data['ApiMethod'][0]['needAuth'];

        return $needAuth==true ?  '<div class="pull-right"><span class="label label-primary"><i class="glyphicon glyphicon-lock"></i> </span></div> ' : '';
    }

    /**
     * Generates a lock badge for method
     *
     * @param  array  $data
     * @return array()
     */
    protected function checkObsolete($data)
    {
        if(!isset($data['ApiObsolete'][0]))
            return array('', '');

        $message = $data['ApiObsolete'][0]['message'];

        $newMethod = $data['ApiObsolete'][0]['newMethod'];

        if(empty($message)){
            $message = '{{Lang_Obsolete}}';
        }

        $tempalte = '<span class="label label-danger" %s >%s</span>';

        return array( 'warning panel-obsolete',
            sprintf(
                $tempalte,
                !empty($newMethod)? 'data-toggle="tooltip" data-placement="top" title="{{Lang_New method}}: '.$newMethod.'"' : '',
                $message
            )
        );
    }

    /**
     * Output the annotations in json format
     *
     * @return json
     */
    public function renderJson()
    {
        $st_annotations = $this->extractAnnotations();

        $o_view = new JsonView();
        $o_view->set('annotations', $st_annotations);
        $o_view->render();
    }

    /**
     * Output the annotations in json format
     *
     * @return array
     */
    public function renderArray()
    {
        return $this->extractAnnotations();
    }

    /**
     * Build the docs
     */
    public function generate()
    {
        return $this->generateTemplate();
    }

    /**
     * Main method template
     * @var string
     */
    public static $mainTpl = <<<HTML
        <div id="panel-method{{ elt_id }}" class="panel panel-{{ collapse-class }}">
            <div class="panel-heading">
                <h4 class="panel-title">
                  {{ obsolete }}  {{ method }} <a data-toggle="collapse" data-parent="#accordion{{ elt_id }}" href="#collapseOne{{ elt_id }}"> {{ route }} </a> {{ lock }} 
                </h4>
            </div>
            <div id="collapseOne{{ elt_id }}" class="panel-collapse collapse">
                <div class="panel-body">
        
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="php-apidoctab{{ elt_id }}">
                        <li class="active"><a href="#info{{ elt_id }}" data-toggle="tab">{{Lang_Info}}</a></li>
                        <!--<li><a href="#sandbox{{ elt_id }}" data-toggle="tab">Sandbox</a></li>-->
                        <li><a href="#sample{{ elt_id }}" data-toggle="tab">{{Lang_Sample output}}</a></li>
                    </ul>
        
                    <!-- Tab panes -->
                    <div class="tab-content">
        
                        <div class="tab-pane active" id="info{{ elt_id }}">
                            <div class="well">
                            {{ description }}
                            </div>

                            {{ headers }}
                            {{ parameters }}
                            {{ body }}
                        </div><!-- #info -->
        
                        <div class="tab-pane" id="sample{{ elt_id }}">
                            <div class="row">
                                <div class="col-md-12">
                                    {{ sample_response_headers }}
                                    {{ sample_response_body }}
                                    <hr>
                                    {{ exceptions }}
                                </div>
                            </div>
                        </div><!-- #sample -->
        
                    </div><!-- .tab-content -->
                </div>
            </div>
        </div>
HTML;

    /**
     * @var string
     */
    static $samplePostBodyTpl = <<<HTML
        <pre id="sample_post_body{{ elt_id }}">{{ body }}</pre>
HTML;

    /**
     * Response template
     * @var string
     */
    static $sampleReponseTpl = <<<HTML
        {{ description }}
        <hr>
        <pre id="sample_response{{ elt_id }}">{{ response }}</pre>
HTML;

    /**
     * Response header template
     * @var string
     */
    static $sampleReponseHeaderTpl = <<<HTML
        <pre id="sample_resp_header{{ elt_id }}">{{ response }}</pre>
HTML;

    /**
     * Params template
     * @var string
     */
    static $paramTableTpl = <<<HTML
        <table class="table table-hover">
            <thead>
            <tr>
                <th>{{Lang_Name}}</th>
                <th>{{Lang_Type}}</th>
                <th>{{Lang_Required}}</th>
                <th>{{Lang_Description}}</th>
            </tr>
            </thead>
            <tbody>
                {{ tbody }}
            </tbody>
        </table>
HTML;

    /**
     * exceptions template
     * @var string
     */
    static $exceptionsTableTpl = <<<HTML
        <table class="table table-hover">
            <thead>
            <tr>
                <th>{{Lang_Code}}</th>
                <th>{{Lang_Message}}</th>
            </tr>
            </thead>
            <tbody>
                {{ tbody }}
            </tbody>
        </table>
HTML;

    /**
     * Param template
     * @var string
     */
    static $paramContentTpl = <<<HTML
        <tr>
            <td>{{ name }}</td>
            <td>{{ type }}</td>
            <td>{{ nullable }}</td>
            <td>{{ description }}</td>
        </tr>
HTML;

    /**
     * Param template
     * @var string
     */
    static $exceptionContentTpl = <<<HTML
        <tr>
            <td>{{ code }}</td>
            <td>{{ message }}</td>
        </tr>
HTML;

    static $paramSampleBtnTpl = <<<HTML
        <a href="javascript:void(0);" data-toggle="popover" data-trigger="focus" data-placement="bottom" title="Sample" data-content="{{ sample }}">
            <i class="btn glyphicon glyphicon-exclamation-sign"></i>
        </a>
HTML;

    /**
     * Panel tamplate
     * @var string
     */
    static $panelTpl = <<<HTML
        <div class="panel panel-default">
            <div class="panel-heading"><strong>{{ panelTitle }}</strong></div>
            <div class="panel-body">
            {{ panelContent }}
            </div>
        </div>
HTML;
    /**
     * sidebar template
     * @var string
     */
    static $sidebarTemplate = <<<HTML
            <nav id="scrollingNav">
                <ul class="sidenav nav nav-list">
                        {{ sidebar-items }}
                </ul>
            </nav>
HTML;
    /**
     * template for item with section name (header group items)
     * @var string
     */
    static $sidebarSectionTemplate = <<<HTML
        <li class="nav-header">{{ name }}</li>
HTML;
    /**
     * template for item
     * @var string
     */
    static $sidebarItemTemplate = <<<HTML
        <li><a href="#{{ link }}">{{ name }}</a>
HTML;

    /**
     * template for dop sections
     * @var string
     */
    static $dopSectionTemplate = <<<HTML
        <div id="dop-section{{ index }}">
            <h2>{{ name }}</h2>
            {{ content }}
        </div>
HTML;

}

<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Smarter Markdown, with PHP Markdown Extra and SmartyPants for extra spicy content goodness.
 *
 * @author          Stephen Lewis (github.com/experience)
 * @copyright       Experience Internet
 * @link            http://experienceinternet.co.uk/software/smartdown/
 * @package         SmartDown
 * @version         1.1.0
 */

require_once PATH_THIRD .'smartdown/markdown/markdown.php';
require_once PATH_THIRD .'smartdown/smartypants/smartypants.php';

// Basic plugin information (required).
$plugin_info = array(
    'pi_author'         => 'Stephen Lewis',
    'pi_author_url'     => 'http://experienceinternet.co.uk/',
    'pi_description'    => 'Smarter Markdown, with PHP Markdown Extra and SmartyPants for spicy content goodness.',
    'pi_name'           => 'SmartDown',
    'pi_usage'          => Smartdown::usage(),
    'pi_version'        => '1.1.0'
);


class Smartdown {
    
    public $return_data = '';
    
    
    /* --------------------------------------------------------------
     * PUBLIC METHODS
     * ------------------------------------------------------------ */
    
    /**
     * PHP 4 constructor. Still required, as of EE 2.1.3.
     *
     * @see     __construct
     * @access  public
     * @param   string      $tagdata    The tagdata to process.
     * @return  void
     */
    public function Smartdown($tagdata = '')
    {
        $this->__construct($tagdata);
    }
    
    
    /**
     * Constructor.
     *
     * @access  public
     * @param   string      $tagdata    The tagdata to process.
     * @return  void
     */
    public function __construct($tagdata = '')
    {
        $ee         =& get_instance();
        $config     = $ee->config;
        $functions  = $ee->functions;
        $tmpl       = $ee->TMPL;

        $this->return_data = '';

        /**
         * Establish the default settings, and override them with
         * any config settings.
         */

        $settings = array(
            'disable:markdown'      => $config->item('disable:markdown', 'smartdown') === TRUE ? TRUE : FALSE,
            'disable:smartypants'   => $config->item('disable:smartypants', 'smartdown') === TRUE ? TRUE : FALSE,
            'ee_tags:encode'        => $config->item('ee_tags:encode', 'smartdown') === TRUE ? TRUE : FALSE,
            'smart_quotes'          => $config->item('smart_quotes', 'smartdown') ? $config->item('smart_quotes', 'smartdown') : '2'
        );

        if ( ! $tagdata)
        {
            $tagdata = $tmpl->tagdata;

            /**
             * Override the settings with any tag parameters. There must be
             * a more elegant way of doing this, but my brain is failing me
             * right now.
             */

            $settings = array(
                'disable:markdown' => $tmpl->fetch_param('disable:markdown')
                    ? ($tmpl->fetch_param('disable:markdown') == 'yes')
                    : $settings['disable:markdown'],

                'disable:smartypants' => $tmpl->fetch_param('disable:smartypants')
                    ? ($tmpl->fetch_param('disable:smartypants') == 'yes')
                    : $settings['disable:smartypants'],
                    
                'ee_tags:encode' => $tmpl->fetch_param('ee_tags:encode')
                    ? ($tmpl->fetch_param('ee_tags:encode') == 'yes')
                    : $settings['ee_tags:encode'],
                    
                'smart_quotes' => $tmpl->fetch_param('smart_quotes')
                    ? $tmpl->fetch_param('smart_quotes')
                    : $settings['smart_quotes']
            );
        }

        // Encode EE tags.
        if ($settings['ee_tags:encode'])
        {
            $tagdata = $functions->encode_ee_tags($tagdata, TRUE);
        }

        // Pre-processing hook.
        if ($ee->extensions->active_hook('smartdown_parse_start') === TRUE)
        {
            $tagdata = $ee->extensions->call('smartdown_parse_start', $tagdata, $settings);
        }

        // Markdown.
        if ( ! $settings['disable:markdown'])
        {
            $tagdata = Markdown($tagdata);

            /**
             * ExpressionEngine automatically encodes any EE tags within
             * the tagdata, regardless of context.
             *
             * This is not what is required within <code> tags, so we
             * fix that problem here.
             */

            $tagdata = preg_replace_callback(
                '|' .preg_quote('<code>') .'(.*?)' .preg_quote('</code>') .'|s',
                array($this, '_fix_encoded_ee_code_samples'),
                $tagdata
            );

            // Fix {path=} URLs.
            // $tagdata = preg_replace('/&#123;(path=.*?)&#125;/i', '{$1}', $tagdata);

            // Play nicely with NSM Transplant and the {image_xx} technique.
            // $tagdata = preg_replace('/&#123;(image_[0-9]+)&#125;/i', '{$1}', $tagdata);
        }
        
        // SmartyPants.
        if ( ! $settings['disable:smartypants'])
        {
            $tagdata = SmartyPants($tagdata, $settings['smart_quotes']);
        }

        // Post-processing hook.
        if ($ee->extensions->active_hook('smartdown_parse_end') === TRUE)
        {
            $tagdata = $ee->extensions->call('smartdown_parse_end', $tagdata, $settings);
        }

        $this->return_data = $tagdata;
    }
    
    
    /**
     * Plugin usage
     *
     * @access  public
     */
    public function usage()
    {
        ob_start();
?>
## Overview ##
Formats the supplied text using Markdown Extra, and SmartyPants.

* [Markdown documentation][markdown]
* [Markdown Extra documentation][markdown_extra]
* [SmartyPants documentation][smartypants]

[markdown]: http://daringfireball.net/projects/markdown/syntax "Read the Markdown documentation"
[markdown_extra]: http://michelf.com/projects/php-markdown/extra/ "Read the Markdown Extra documentation"
[smartypants]: http://daringfireball.net/projects/smartypants/ "Read the SmartyPants documentation"

Example usage:

    {exp:smartdown}

        Stuff I really need to get done:
        
        * Finish SmartDown
        * Release SmartDown
        * Deal with flurry of SmartDown support requests
        * Question why I ever released SmartDown
        * Retire from public life

    {/exp:smartdown}

## Parameters ##
`disable:markdown`
: Set to `yes` to disable Markdown. Default is `no`.

`disable:smartypants`
: Set to `yes` to disable SmartyPants. Default is `no`.

`ee_tags:encode`
: Set to `yes`, to convert the curly braces for all EE tags and variables into entities. Default is `no`.

`smart_quotes`
: Fine-grained control over SmartyPants' handling of smart quotes. Will never be used by 99% of you.
Nosey types should take a look at the SmartyPants source code.

## Fieldtype parameters ##
The SmartDown fieldtype may be configured using `config.php`. This makes it possible to set any of the supported SmartDown parameters directly in the fieldtype, without the requirement to use the `{exp:smartdown}` template tag.

The SmartDown config settings should take the form of an associative array. All of the documented template parameters are supported, the only difference being that `TRUE` should be used instead of `yes`, and `FALSE` instead of `no`.

    $config['smartdown'] => array(
        'disable:markdown'      => TRUE,        // TRUE or FALSE. Default is FALSE.
        'disable:smartypants'   => TRUE,        // TRUE or FALSE. Default is FALSE.
        'ee_tags:encode'        => TRUE,        // TRUE or FALSE. Default is FALSE.
        'smart_quotes'          => 1            // Default is 2.
    );

<?php

        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
    


    /* --------------------------------------------------------------
     * PRIVATE METHODS
     * ------------------------------------------------------------ */
    
    /**
     * preg_replace callback function, used to parse EE-encoded code blocks.
     *
     * @access  private
     * @param   array       $matches        The regular expression matches.
     * @return  string
     */
    private function _fix_encoded_ee_code_samples($matches)
    {
        $parsed = str_replace(
            array('&amp;#123;&amp;#47;', '&amp;#123;', '&amp;#125;'),
            array('&#123;&#47;', '&#123;', '&#125;'),
            $matches[0]
        );

        return $parsed;
    }


}


/* End of file      : pi.smartdown.php */
/* File location    : third_party/smartdown/pi.smartdown.php */

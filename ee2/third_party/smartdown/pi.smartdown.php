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

        $default_smartquotes    = '2';
        $this->return_data      = '';

        if ($tagdata)
        {
            // Fieldtype.
            $encode         = $config->item('smartdown:ee_tags:encode') == 'yes';
            $fix_images     = !($config->item('smartdown:ee_tags:fix_transplanted_images') == 'no');
            $smart_quotes   = $config->item('smartdown:smart_quotes') ? $config->item('smartdown:smart_quotes') : $default_smartquotes;
        }
        else
        {
            // Template tag.
            $tagdata        = $tmpl->tagdata;
            $encode         = ($tmpl->fetch_param('ee_tags:encode') == 'yes') OR ($tmpl->fetch_param('encode_ee_tags') == 'yes');
            $fix_images     = !($tmpl->fetch_param('ee_tags:fix_transplanted_images') == 'no');
            $smart_quotes   = $tmpl->fetch_param('smart_quotes') ? $tmpl->fetch_param('smart_quotes') : $default_smartquotes;
        }

        if ($encode)
        {
            $tagdata = Markdown($functions->encode_ee_tags($tagdata, TRUE));

            // Fix EE code samples.
            $tagdata = preg_replace_callback(
                '|' .preg_quote('<code>') .'(.*?)' .preg_quote('</code>') .'|s',
                array($this, '_fix_encoded_ee_code_samples'),
                $tagdata
            );

            // Fix {path=} URLs.
            $tagdata = preg_replace('/&#123;(path=.*?)&#125;/i', '{$1}', $tagdata);

            // Play nicely with NSM Transplant and the {image_xx} technique.
            if ($fix_images)
            {
                $tagdata = preg_replace('/&#123;(image_[0-9]+)&#125;/i', '{$1}', $tagdata);
            }
        }
        else
        {
            $tagdata = Markdown($tagdata);
        }
        
        $this->return_data  = SmartyPants($tagdata, $smart_quotes);
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
`ee_tags:encode`
: Set to `yes`, to convert the curly braces for all EE tags and variables into entities. Default is `no`.

`ee_tags:fix_transplanted_images`
: Set to `no` to prevent `ee_tags:encode` from playing nicely with NSM Transplant and the `{image_xx}` technique. Default is `yes`.

`smart_quotes`
: Fine-grained control over SmartyPants' handling of smart quotes. Will never be used by 99% of you.
Nosey types should take a look at the SmartyPants source code.

## Fieldtype parameters ##
The SmartDown fieldtype may be configured using `config.php`. This makes it possible to set any of the supported SmartDown parameters directly in the fieldtype, without the requirement to use the `{exp:smartdown}` template tag.

`$config['smartdown:ee_tags:encode']`
: Mirrors the `ee_tags:encode` template tag.

`$config['smartdown:ee_tags:fix_transplanted_images']`
: Mirrors the `ee_tags:fix_transplanted_images` template tag.

`$config['smartdown:smart_quotes']`
: Mirrors the `smart_quotes` template tag.

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
            array('&amp;#123;&amp;#47;', '&amp;#123;', '&amp;#125;', '&amp;lt;', '&amp;gt;'),
            array('&#123;&#47;', '&#123;', '&#125;', '&lt;', '&gt;'),
            $matches[0]
        );

        return $parsed;
    }


}

/* End of file      : pi.smartdown.php */
/* File location    : third_party/smartdown/pi.smartdown.php */

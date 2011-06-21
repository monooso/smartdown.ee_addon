<?php if ( ! defined('PATH_PI')) exit('Invalid file request');

/**
 * Smarter Markdown, with PHP Markdown Extra and SmartyPants for extra spicy content goodness.
 *
 * @package     SmartDown
 * @author      Stephen Lewis <stephen@experienceinternet.co.uk>
 * @copyright   Copyright (c) 2010, Stephen Lewis
 * @license     http://creativecommons.org/licenses/by-nc-sa/3.0/ Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported
 * @link        http://experienceinternet.co.uk/software/smartdown/
 * @version     1.1.0
 */

require_once PATH_PI .'smartdown/markdown/markdown.php';
require_once PATH_PI .'smartdown/smartypants/smartypants.php';

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
     * PHP4 constructor. Plugin won't work without it.
     *
     * @see     __construct
     */
    public function Smartdown($tagdata = '')
    {
        $this->__construct($tagdata);
    }
    
    
    /**
     * Constructor.
     *
     * @access  public
     * @param   string      $tagdata        The tagdata to process.
     * @return  void
     */
    public function __construct($tagdata = '')
    {
        global $REGX, $TMPL;
        
        $this->return_data = '';
        
        if ( ! $tagdata)
        {
            $tagdata = $TMPL->tagdata;
        }
        
        if ($TMPL->fetch_param('encode_ee_tags') == 'yes')
        {
            $tagdata = Markdown($REGX->encode_ee_tags($tagdata, TRUE));
            
            $tagdata = preg_replace_callback(
                '|' .preg_quote('<code>') .'(.*?)' .preg_quote('</code>') .'|s',
                create_function(
                    '$matches',
                    'return str_replace(
                        array(
                            "&amp;#123;&amp;#47;",
                            "&amp;#123;",
                            "&amp;#125;"
                        ),
                        array(
                            "&#123;&#47;",
                            "&#123;",
                            "&#125;"
                        ),
                        $matches[0]
                    );'
                ),
                $tagdata
            );
        }
        else
        {
            $tagdata = Markdown($tagdata);
        }
        
        // Apply SmartyPants.
        $smart_quotes = $TMPL->fetch_param('smart_quotes') ? $TMPL->fetch_param('smart_quotes') : '2';
        $this->return_data = SmartyPants($tagdata, $smart_quotes);
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
            Formats the supplied text using Markdown Extra, and SmartyPants.
            
            Information about Markdown is available at http://daringfireball.net/projects/markdown/syntax.
            Information about Markdown Extra is available at http://michelf.com/projects/php-markdown/extra/.
            Information about SmartyPants is available at http://daringfireball.net/projects/smartypants/.

            Example usage:
            {exp:smartdown}
            
                # Tasks #
                
                Stuff I really need to get done:
                
                * Finish SmartDown
                * Release SmartDown
                * Deal with flurry of SmartDown support requests
                * Question why I ever released SmartDown
                * Retire from public life
            
            {/exp:smartdown}

            Parameters:
            
            encode_ee_tags
            Set to 'yes' to convert the curly braces for all EE tags and variables into entities. Default is no.
            
            smart_quotes
            Fine-grained control over SmartyPants' handling of smart quotes. Will never be used by 99% of you.
            Nosey types should take a look at the SmartyPants source code.

<?php

          $buffer = ob_get_contents();
          ob_end_clean();

            return $buffer;
        }
    
    
}

/* End of file      : pi.smartdown.php */
/* File location    : /system/plugins/pi.smartdown.php */

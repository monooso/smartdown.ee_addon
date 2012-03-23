<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Smarter Markdown, with PHP Markdown Extra and SmartyPants for extra spicy content goodness.
 *
 * @author          Stephen Lewis (github.com/experience)
 * @copyright       Experience Internet
 * @link            http://experienceinternet.co.uk/software/smartdown/
 * @package         Smartdown
 * @version         1.3.1
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
  'pi_version'        => '1.3.1'
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

    $default_quotes     = 2;
    $this->return_data  = '';

    /**
     * Establish the default settings, and override them with
     * any config settings.
     */

    $settings = array(
      'disable:markdown'
        => ($config->item('disable:markdown', 'smartdown') === TRUE),
      'disable:smartypants'
        => ($config->item('disable:smartypants', 'smartdown') === TRUE),
      'ee_tags:encode'
        => ($config->item('ee_tags:encode', 'smartdown') === TRUE),
      'ee_tags:encode_path'
        => ($config->item('ee_tags:encode_path', 'smartdown') === TRUE),
      'smart_quotes' => $config->item('smart_quotes', 'smartdown')
        ? $config->item('smart_quotes', 'smartdown') : $default_quotes
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
        'disable:markdown' => $tmpl->fetch_param('disable:markdown') == 'yes'
          ? TRUE : $settings['disable:markdown'],
        'disable:smartypants' => $tmpl->fetch_param('disable:smartypants') == 'yes'
          ? TRUE : $settings['disable:smartypants'],
        'ee_tags:encode' => $tmpl->fetch_param('ee_tags:encode') == 'yes'
          ? TRUE : $settings['ee_tags:encode'],
        'ee_tags:encode_path' => $tmpl->fetch_param('ee_tags:encode_path') == 'yes'
          ? TRUE : $settings['ee_tags:encode_path'],
        'smart_quotes' => $tmpl->fetch_param('smart_quotes', $settings['smart_quotes'])
      );
    }

    // smart_quotes must be a non-negative integer.
    $settings['smart_quotes'] = $this->_valid_int($settings['smart_quotes'], 0)
      ? (int) $settings['smart_quotes']
      : $default_quotes;

    // Encode EE tags.
    if ($settings['ee_tags:encode'])
    {
      $tagdata = $functions->encode_ee_tags($tagdata, TRUE);

      // Don't encode {path=} tags, unless we're explicitly told to do so.
      if ( ! $settings['ee_tags:encode_path'])
      {
        $tagdata = preg_replace('
          /&#123;path=([\'|"]?)([a-z0-9_\/\-]+)([\'|"]?)&#125;/',
          LD .'path=$1$2$3' .RD, $tagdata);
      }
    }

    // Pre-processing hook.
    if ($ee->extensions->active_hook('smartdown_parse_start') === TRUE)
    {
      $tagdata = $ee->extensions->call('smartdown_parse_start',
        $tagdata, $settings);
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
    }
    
    // SmartyPants.
    if ( ! $settings['disable:smartypants'])
    {
      $tagdata = SmartyPants($tagdata, $settings['smart_quotes']);
    }

    // Post-processing hook.
    if ($ee->extensions->active_hook('smartdown_parse_end') === TRUE)
    {
      $tagdata = $ee->extensions->call('smartdown_parse_end',
        $tagdata, $settings);
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
# Overview #
Formats the supplied text using Markdown Extra, and SmartyPants.

* [Markdown documentation][markdown]
* [Markdown Extra documentation][markdown_extra]
* [SmartyPants documentation][smartypants]

[markdown]: http://daringfireball.net/projects/markdown/syntax "Read the Markdown documentation"
[markdown_extra]: http://michelf.com/projects/php-markdown/extra/ "Read the Markdown Extra documentation"
[smartypants]: http://daringfireball.net/projects/smartypants/ "Read the SmartyPants documentation"

# Usage Instructions #
You can specify SmartDown as the default text formatting for a custom field, or 
use it directly in your templates.

## Option 1: Default text formatting ##
To specify SmartDown as the default text formatting for a custom field, select 
it from the "Default Text Formatting for This Field" drop-down on the "Create / 
Edit a Custom Field" page.

### How to add SmartDown to the text formatting drop-down ###
Unfortunately, there’s no way to include SmartDown in the default text 
formatting drop-down. Instead, you’ll need to create your new custom field, save 
it, then edit it and click the "Edit List" link next to the text formatting 
drop-down.

Set SmartDown to "Yes" in the "Field Formatting Options" list, click update, and 
you’ll be returned to the "Edit a Custom Field" page. You can now select 
SmartDown from the text formatting drop-down.

## Option 2: Template tag pair ##
Specifying SmartDown as the default text formatting for a custom field will 
cause ExpressionEngine to automatically format your text using SmartDown, with 
no need for additional template tags.

If you’d like to format an arbitrary text string using SmartDown, you can do so 
directly in your templates using the `exp:smartdown` tag pair.

The SmartDown tag pair supports the following parameters:

`disable:markdown`
: Set to `yes` to disable Markdown. Default is `no`.

`disable:smartypants`
: Set to `yes` to disable SmartyPants. Default is `no`.

`ee_tags:encode`
: When set to `yes`, the curly braces for all ExpressionEngine tags and 
variables are converted into HTML entities. Default is `no`. See below for more 
information about tag encoding.

`ee_tags:encode_path`
: When set to `yes`, the ExpressionEngine `{path=...}` tags will be encoded. 
Default is no, even if `ee_tags:encode` is set to `yes`.

`smart_quotes`
: Fine-grained control over SmartyPants' handling of smart quotes. Will never be 
used by 99% of you. Nosey types should take a look at the SmartyPants source 
code.

## Configuring SmartDown using `config.php` ##
You can specify default SmartDown parameters in your `config.php` file. These 
defaults can be overridden on a per-template tag basis.

    // config.php
    $config['smartdown'] = array(
      'disable:markdown'    => TRUE,
      'disable:smartypants' => TRUE,
      'ee_tags:encode'      => TRUE,
      'smart_quotes'        => 1
    );

    {!-- Overriding the config.php defaults. --}
    {exp:smartdown
        disable:markdown='no'
        disable:smartpants='no'
        ee_tags:encode='no'
        ee_tags:encode_path='no'
        smart_quotes='2'
    }

## A note about tag encoding ##
When dealing with database content, ExpressionEngine automatically encodes 
anything that looks like an EE tag or variable.

As such, you should typically leave the `ee_tags:encode` option set to its 
default value of `no`. The only time you might want to change this is when 
processing an arbitrary string of hard-coded text.

For example:

    {!-- The curly braces around "this_tag" will be encoded. --}
    {exp:smartdown ee_tags:encode='yes'}
        Encode {this_tag} please.
    {/exp:smartdown}

### Tag encoding and code samples ###
One unfortunate side-effect of ExpressionEngine’s fastidious encoding of curly 
braces is that it can cause problems with Markdown-formatted sample EE code. 
Curly braces may be double-encoded, causing all manner of display problems, and 
a general malaise.

SmartDown undoes the damage done by EE to your precious code samples, so they 
display correctly.

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


  /**
   * Determines whether the supplied argument is, or can be evaluated to,
   * a valid integer.
   *
   * @param mixed   $value    The value to check.
   * @param mixed   $min    The minimum permissible value.
   * @param mixed   $max    The maximum permissible value.
   * @return  bool
   */
  private function _valid_int($value, $min = NULL, $max = NULL)
  {
    $valid = (is_int($value) OR (is_numeric($value) && intval($value) == $value));
    
    // If no bounds have been set, we're done.
    if ( ! $valid OR (is_null($min) && is_null($max)))
    {
      return $valid;
    }
    
    $min = is_null($min) ? -INF : ($this->_valid_int($min) ? intval($min) : -INF);
    $max = is_null($max) ? INF : ($this->_valid_int($max) ? intval($max) : INF);
    
    $value    = intval($value);
    $real_min = min($min, $max);
    $real_max = max($min, $max);
    
    return $valid && (min(max($value, $real_min), $real_max) === $value);
  }

  
}


/* End of file      : pi.smartdown.php */
/* File location    : third_party/smartdown/pi.smartdown.php */

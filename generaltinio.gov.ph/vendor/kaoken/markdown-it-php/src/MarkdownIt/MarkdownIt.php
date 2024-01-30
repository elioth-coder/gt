<?php
// Main parser class

namespace Kaoken\MarkdownIt;
use Kaoken\Punycode\Punycode;
use Kaoken\MarkdownIt\Common\Utils;
use Kaoken\MarkdownIt\Helpers\Helpers;
use Kaoken\LinkifyIt\LinkifyIt;
use Kaoken\MDUrl\MDUrl;
use \Exception;
use \stdClass;

/**
 * class MarkdownIt
 *
 * Main parser/renderer class.
 *
 * ##### Usage
 *
 * ```PHP
 * $md = new MarkdownIt();
 * $result = $md->render('# markdown-it rulezz!');
 * ```
 *
 * Single line rendering, without paragraph wrap:
 *
 * ```PHP
 * $md = new MarkdownIt();
 * $result = $md->renderInline('__markdown-it__ rulezz!');
 * ```
 **/
class MarkdownIt extends stdClass
{
    /**
     * @array
     */
    protected array $configList = [
        'default'      => \Kaoken\MarkdownIt\Presets\PresetDefault::class,
        'zero'         => \Kaoken\MarkdownIt\Presets\Zero::class,
        'commonmark'   =>\Kaoken\MarkdownIt\Presets\CommonMark::class
    ];

    protected $config;

    /**
     * Instance of [[ParserInline]]. You may need it to add new rules when
     * writing plugins. For simple rules control use [[MarkdownIt.disable]] and
     * [[MarkdownIt.enable]].
     * @var ParserInline
     **/
    public ?ParserInline $inline = null;

  /**
   * MarkdownIt#block -> ParserBlock
   * Instance of [[ParserBlock]]. You may need it to add new rules when
   * writing plugins. For simple rules control use [[MarkdownIt.disable]] and
   * [[MarkdownIt.enable]].
   * @vare ParserBlock
   **/
    public ?ParserBlock $block = null;

  /**
   * Instance of [[Core]] chain executor. You may need it to add new rules when
   * writing plugins. For simple rules control use [[MarkdownIt.disable]] and
   * [[MarkdownIt.enable]].
   *
   * @var ParserCore
   **/
    public ?ParserCore $core = null;

    /**
     * Instance of [[Renderer]]. Use it to modify output look. Or to add rendering
     * rules for new token types, generated by plugins.
     *
     * ##### Example
     *
     * ```PHP
     * $md = new MarkdownIt();
     *
     * function myToken($tokens, $idx, $options, $env, $self) {
     *   //...
     *   return $result;
     * }
     *
     * $md->renderer->rules['my_token'] = myToken
     * ```
     *
     * See [[Renderer]] docs and [source code](https://github.com/markdown-it/markdown-it/blob/master/lib/renderer.mjs).
     * @var Renderer
     **/
    public Renderer $renderer;

    /**
     * MarkdownIt#linkify -> LinkifyIt
     *
     * [linkify-it](https://github.com/markdown-it/linkify-it) instance.
     * Used by [linkify](https://github.com/markdown-it/markdown-it/blob/master/lib/rules_core/linkify.mjs)
     * rule.
     * @LinkifyIt
     **/
    public ?LinkifyIt $linkify = null;

    // Expose utils & helpers for easy acces from plugins

    /**
     * MarkdownIt#utils -> utils
     *
     * Assorted utility functions, useful to write plugins. See details
     * [here](https://github.com/markdown-it/markdown-it/blob/master/lib/common/utils.mjs).
     **/
    public ?Utils $utils = null;

    /**
     * Link components parser functions, useful to write plugins. See details
     * [here](https://github.com/markdown-it/markdown-it/blob/master/lib/helpers).
     *
     * @var Helpers
     **/
    public Helpers $helpers;

    /**
     * @var MDUrl
     */
    protected MDUrl $mdurl;

    /**
     * @var Punycode
     */
    protected Punycode $punycode;

    /**
     * @var array
     */
    public $options;

    ////////////////////////////////////////////////////////////////////////////////
    //
    // This validator can prohibit more than really needed to prevent XSS. It's a
    // tradeoff to keep code simple and to be secure by default.
    //
    // If you need different setup - override validator method as you wish. Or
    // replace it with dummy function and use external sanitizer.
    //

    const BAD_PROTO_RE = '/^(vbscript|javascript|file|data):/';
    const GOOD_DATA_RE = '/^data:image\/(gif|png|jpeg|webp);/';
    const RECODE_HOSTNAME_FOR = [ 'http:', 'https:', 'mailto:' ];

    /**
     * Link validation function. CommonMark allows too much in links. By default
     * we disable `javascript:`, `vbscript:`, `file:` schemas, and almost all `data:...` schemas
     * except some embedded image types.
     *
     * You can change this behaviour:
     *
     * ```PHP
     * $md = new MarkdownIt();
     * // enable everything
     * $md->validateLink = function () { return true; }
     * ```
     * @param string $url
     * @return boolean
     */
    public function validateLink(string $url): bool
    {
        if( property_exists($this,'validateLink') && is_callable($this->validateLink)){
            $fn = $this->validateLink;
            return $fn($url);
        }
        // url should be normalized at this point, and existing entities are decoded
        $str = strtolower(trim($url));

        return preg_match(self::BAD_PROTO_RE, $str) ? (preg_match(self::GOOD_DATA_RE, $str) ? true : false) : true;
    }

////////////////////////////////////////////////////////////////////////////////



    /**
     * Function used to encode link url to a machine-readable format,
     * which includes url-encoding, punycode, etc.
     * @param string $url
     * @return string
     **/
    public function normalizeLink($url): string
    {
        if( property_exists($this,'normalizeLink') && is_callable($this->normalizeLink)){
            $fn = $this->normalizeLink;
            return $fn($url);
        }
        $parsed = $this->mdurl->parse($url, true);

        if ($parsed->hostname) {
            // Encode hostnames in urls like:
            // `http://host/`, `https://host/`, `mailto:user@host`, `//host/`
            //
            // We don't encode unknown schemas, because it's likely that we encode
            // something we shouldn't (e.g. `skype:name` treated as `skype:host`)
            //
            $is = false;
            foreach (self::RECODE_HOSTNAME_FOR as &$v) {
                if( ($is = $v === $parsed->protocol) ) break;
            }
            if (empty($parsed->protocol) || $is) {
                try {
                    $parsed->hostname = $this->punycode->toASCII($parsed->hostname);
                } catch (Exception $e) { /**/ }
            }
        }
        return $this->mdurl->encode($this->mdurl->format($parsed));
    }

    /**validateLink
     * @param string $url
     * @return string
     **/
    public function normalizeLinkText($url): string
    {
        if( property_exists($this,'normalizeLinkText') && is_callable($this->normalizeLinkText)){
            $fn = $this->normalizeLinkText;
            return $fn($url);
        }
        $parsed = $this->mdurl->parse($url, true);

        if ($parsed->hostname) {
            // Encode hostnames in urls like:
            // `http://host/`, `https://host/`, `mailto:user@host`, `//host/`
            //
            // We don't encode unknown schemas, because it's likely that we encode
            // something we shouldn't (e.g. `skype:name` treated as `skype:host`)
            //
            $is = false;
            foreach (self::RECODE_HOSTNAME_FOR as &$v) {
                if( ($is = $v === $parsed->protocol) ) break;
            }
            if (empty($parsed->protocol) || $is) {
                try {
                    $parsed->hostname = $this->punycode->toUnicode($parsed->hostname);
                } catch (Exception $e) { /**/ }
            }
        }
        // add '%' to exclude list because of https://github.com/markdown-it/markdown-it/issues/720
        return $this->mdurl->decode($this->mdurl->format($parsed), $this->mdurl->decodeDefaultChars() . '%');
    }

    /**
     * Creates parser instanse with given config. Can be called without `new`.
     *
     * ##### presetName
     *
     * MarkdownIt provides named presets as a convenience to quickly
     * enable/disable active syntax rules and options for common use cases.
     *
     * - ["commonmark"](https://github.com/markdown-it/markdown-it/blob/master/lib/presets/commonmark.mjs) -
     *   configures parser to strict [CommonMark](http://commonmark.org/) mode.
     * - [default](https://github.com/markdown-it/markdown-it/blob/master/lib/presets/default.mjs) -
     *   similar to GFM, used when no preset name given. Enables all available rules,
     *   but still without html, typographer & autolinker.
     * - ["zero"](https://github.com/markdown-it/markdown-it/blob/master/lib/presets/zero.mjs) -
     *   all rules disabled. Useful to quickly setup your config via `.enable()`.
     *   For example, when you need only `bold` and `italic` markup and nothing else.
     *
     * ##### options:
     *
     * - __html__ - `false`. Set `true` to enable HTML tags in source. Be careful!
     *   That's not safe! You may need external sanitizer to protect output from XSS.
     *   It's better to extend features via plugins, instead of enabling HTML.
     * - __xhtmlOut__ - `false`. Set `true` to add '/' when closing single tags
     *   (`<br />`). This is needed only for full CommonMark compatibility. In real
     *   world you will need HTML output.
     * - __breaks__ - `false`. Set `true` to convert `\n` in paragraphs into `<br>`.
     * - __langPrefix__ - `language-`. CSS language class prefix for fenced blocks.
     *   Can be useful for external highlighters.
     * - __linkify__ - `false`. Set `true` to autoconvert URL-like text to links.
     * - __typographer__  - `false`. Set `true` to enable [some language-neutral
     *   replacement](https://github.com/markdown-it/markdown-it/blob/master/lib/rules_core/replacements.mjs) +
     *   quotes beautification (smartquotes).
     * - __quotes__ - `“”‘’`, String or Array. Double + single quotes replacement
     *   pairs, when typographer enabled and smartquotes on. For example, you can
     *   use `'«»„“'` for Russian, `'„“‚‘'` for German, and
     *   `['«\xA0', '\xA0»', '‹\xA0', '\xA0›']` for French (including nbsp).
     * - __highlight__ - `null`. Highlighter function for fenced code blocks.
     *   Highlighter `function (str, lang)` should return escaped HTML. It can also
     *   return empty string if the source was not changed and should be escaped
     *   externaly. If result starts with <pre... internal wrapper is skipped.
     *
     * ##### Example
     *
     * ```PHP
     * // commonmark mode
     * $md = new MarkdownIt('commonmark');
     *
     * // default mode
     * $md = new MarkdownIt();
     *
     * // enable everything
     * $md = new MarkdownIt({
     *   html: true,
     *   linkify: true,
     *   typographer: true
     * });
     * ```
     *
     * ##### Syntax highlighting
     *
     * ```PHP
     * hljs = require('highlight.js') // https://highlightjs.org/
     *
     * $md = new MarkdownIt({
     *   highlight: function (str, lang) {
     *     if (lang && hljs.getLanguage(lang)) {
     *       try {
     *         return hljs.highlight(str, [ 'language' => 'lang', 'ignoreIllegals' => true ]).value;
     *       } catch (__) {}
     *     }
     *
     *     return ''; // use external default escaping
     *   }
     * });
     * ```
     *
     *  Or with full wrapper override (if you need assign class to `<pre>` or `<code>`):
     *
     * ```PHP
     * hljs = require('highlight.js') // https://highlightjs.org/
     *
     * // Actual default values
     * $md = new MarkdownIt({
     *   highlight: function (str, lang) {
     *     if (lang && hljs.getLanguage(lang)) {
     *       try {
     *          return '<pre><code class="hljs">' +
     *                hljs.highlight(str, [ 'language' => 'lang', 'ignoreIllegals' => true ]).value +
     *                '</code></pre>';
     *       } catch (__) {}
     *     }
     *
     *      return '<pre><code class="hljs">' + md.utils.escapeHtml(str) + '</code></pre>';
     *   }
     * });
     * ```
     *
     *
     * MarkdownIt constructor.
     * @param string $presetName optional, `commonmark` / `zero`
     * @param null|array $options
     * @throws Exception
     */
    public function __construct($presetName='default', $options=null)
    {
        if( is_array($presetName) ) $presetName = (object)$presetName;
        if( is_array($options) ) $options = (object)$options;
        if (!is_string($presetName)) {
            $options = is_object($presetName) ? $presetName : new stdClass();
            $presetName = 'default';
        }
        $this->inline = new ParserInline();
        $this->block = new ParserBlock();
        $this->core = new ParserCore();
        $this->renderer = new Renderer();
        $this->linkify = new LinkifyIt();
        $this->utils = Utils::getInstance();
        $this->helpers = new Helpers();
        $this->options = new stdClass();
        $this->punycode = new Punycode();
        $this->mdurl = new MDUrl();

        $this->configure($presetName);

        if (isset($options)) { $this->set($options); }
    }

    /** chainable
     * MarkdownIt.set(options)
     *
     * Set parser options (in the same format as in constructor). Probably, you
     * will never need it, but you can change options after constructor call.
     *
     * ##### Example
     *
     * ```PHP
     * $md = new MarkdownIt();
     * $md->set([ "html" => true, "breaks" => true ])
     *    ->set([ "typographer", "true" ]);
     * ```
     *
     * __Note:__ To achieve the best possible performance, don't modify a
     * `markdown-it` instance options on the fly. If you need multiple configurations
     * it's best to create multiple instances and initialize each with separate
     * config.
     * @param stdClass $options
     * @return $this
     * @throws Exception
     */
    public function set($options): MarkdownIt
    {
        if( is_array($options) )$options = (object)$options;//json_decode(json_encode($options));
        $this->utils->assign($this->options, $options);
        return $this;
    }
    /**
     * Batch load of all options and compenent settings. This is internal method,
     * and you probably will not need it. But if you will - see available presets
     * and data structure [here](https://github.com/markdown-it/markdown-it/tree/master/lib/presets)
     *
     * We strongly recommend to use presets instead of direct config loads. That
     * will give better compatibility with next versions.
     * @param string|stdClass $presets
     * @return $this
     * @throws Exception
     */
    public function configure($presets=null): MarkdownIt
    {
        if (is_string($presets)) {
            $presetName = $presets;
            if(!array_key_exists ($presetName, $this->configList)){
                throw new Exception('Wrong `markdown-it` preset "' . $presetName . '", check name');
            }
            $class = $this->configList[$presetName];
            $presets = $class::get();
        }else if( !is_null($presets) && empty($presets) ){
            return $this;
        }

        if (is_null($presets)) { throw new Exception('Wrong `markdown-it` preset, can\'t be empty'); }

        if (is_object($presets->options)) { $this->set($presets->options); }

        if (isset($presets->components)) {
            foreach ($presets->components as $name=>&$val) {
                if( !property_exists($this,$name) ) continue;
                if( isset($presets->components->{$name}->rules)  ){
                    $this->{$name}->ruler->enableOnly($presets->components->{$name}->rules);
                }
                if( isset($presets->components->{$name}->rules2)  ){
                    $this->{$name}->ruler2->enableOnly($presets->components->{$name}->rules2);
                }
            }
        }
        return $this;
    }



    /** chainable
     * Enable list or rules. It will automatically find appropriate components,
     * containing rules with given names. If rule not found, and `ignoreInvalid`
     * not set - throws exception.
     *
     * ##### Example
     *
     * ```PHP
     * $md = new MarkdownIt();
     * $md->enable(['sub', 'sup'])
     *    ->disable('smartquotes');
     * ```
     * @param string|array $list          rule name or list of rule names to enable
     * @param boolean      $ignoreInvalid set `true` to ignore errors when rule not found.
     * @return $this
     * @throws Exception
     */
    public function enable($list, $ignoreInvalid=false): MarkdownIt
    {
        $result = [];

        if (!is_array($list)) { $list = [ $list ]; }

        foreach ([ 'core', 'block', 'inline' ] as &$chain) {
            if( !property_exists($this,$chain) ) continue;
            $result = array_merge($result, $this->{$chain}->ruler->enable($list, true));
        }


        $result = array_merge($result, $this->inline->ruler2->enable($list, true) );

        $missed = array_filter ($list, function ($name) use(&$result) { return array_search ($name, $result) === false; });

        if (count($missed) !== 0 && !$ignoreInvalid) {
            throw new Exception('MarkdownIt. Failed to enable unknown rule(s): ' . implode(', ', $missed));
        }

        return $this;
    }


    /** chainable
     * The same as [[MarkdownIt.enable]], but turn specified rules off.
     *
     * @param string|array $list          rule name or list of rule names to enable
     * @param boolean      $ignoreInvalid set `true` to ignore errors when rule not found.
     * @return $this
     * @throws Exception
     */
    public function disable($list, $ignoreInvalid=false): MarkdownIt
    {
        $result = [];

        if (!is_array($list)) { $list = [ $list ]; }

        foreach ([ 'core', 'block', 'inline' ] as &$chain) {
            if( !property_exists($this,$chain) ) continue;
            $result = array_merge($result, $this->{$chain}->ruler->disable($list, true));
        }
        $result = array_merge($result, $this->inline->ruler2->disable($list, true) );

        $missed = array_filter ($list, function ($name) use(&$result) { return array_search ($name, $result) === false; });

        if (count($missed) !== 0 && !$ignoreInvalid) {
            throw new Exception('MarkdownIt. Failed to disable unknown rule(s): ' . implode(', ', $missed));
        }
        return $this;
    }


    /** chainable
     * Load specified plugin with given params into current parser instance.
     * It's just a sugar to call `plugin($md, $params)` with curring.
     *
     * ##### Example
     *
     * ```PHP
     * class Test{
     *   public function plugin($md, $ruleName, $tokenType, $iteartor){
     *     $scan = function($state) use($md, $ruleName, $tokenType, $iteartor) {
     *         for ($blkIdx = count($state->tokens) - 1; $blkIdx >= 0; $blkIdx--) {
     *             if ($state->tokens[$blkIdx]->type !== 'inline') {
     *                 continue;
     *             }
     *
     *             $inlineTokens = $state->tokens[$blkIdx]->children;
     *
     *             for ($i = count($inlineTokens) - 1; $i >= 0; $i--) {
     *                 if ($inlineTokens[$i]->type !== $tokenType) {
     *                     continue;
     *                 }
     *
     *                 $iteartor($inlineTokens, $i);
     *             }
     *         }
     *     };
     *
     *     $md->core->ruler->push($ruleName, $scan);
     *   }
     * }
     * $md = new MarkdownIt();
     * $md->plugin(new Test(),'foo_replace', 'text', function ($tokens, $idx) {
     *   $tokens[$idx]->content = preg_replace("/foo/", 'bar', $tokens[$idx]->content);
     * });
     * ```
     *
     * @param callable|object $plugin
     * @param array ...$args
     * @return $this
     * @throws Exception
     */
    public function plugin($plugin, ...$args): MarkdownIt
    {
        array_unshift($args, $this);
        if( is_callable($plugin) ){
            call_user_func_array($plugin, $args);
        }else if( is_object($plugin) ){
            call_user_func_array([$plugin, 'plugin'], $args);
        }else{
            throw new Exception();
        }
        return $this;
    }


    /** internal
     * Parse input string and return list of block tokens (special token type
     * "inline" will contain list of inline tokens). You should not call this
     * method directly, until you write custom renderer (for example, to produce
     * AST).
     *
     * `env` is used to pass data between "distributed" rules and return additional
     * metadata like reference info, needed for the renderer. It also can be used to
     * inject data in specific cases. Usually, you will be ok to pass `{}`,
     * and then pass updated object to renderer.
     *
     * @param null|string $src source string
     * @param null $env environment sandbox
     * @return array
     * @throws Exception
     */
    public function &parse(?string $src, $env=null): array
    {
        if ( !is_string($src)) {
            throw new Exception('Input data should be a String');
        }

        $state = $this->core->createState($src, $this, $env);

        $this->core->process($state);

        return $state->tokens;
    }


    /**
     * Render markdown string into html. It does all magic for you :).
     *
     * `env` can be used to inject additional metadata (`{}` by default).
     * But you will not need it with high probability. See also comment
     * in [[MarkdownIt.parse]].
     *
     * @param null|string $src source string
     * @param null $env environment sandbox
     * @return string
     * @throws Exception
     */
    public function render(?string $src, $env=null): string
    {
        $env = is_object($env) ? $env : new stdClass();

        return $this->renderer->render($this->parse($src, $env), $this->options, $env);
    }


    /** internal
     * The same as [[MarkdownIt.parse]] but skip all block rules. It returns the
     * block tokens list with the single `inline` element, containing parsed inline
     * tokens in `children` property. Also updates `env` object.
     *
     * @param string $src source string
     * @param object $env environment sandbox
     * @return array
     */
    public function &parseInline(string $src, object $env): array
    {
        $state = $this->core->createState($src, $this, $env);

        $state->inlineMode = true;
        $this->core->process($state);

        return $state->tokens;
    }


    /**
     * Similar to [[MarkdownIt.render]] but for single paragraph content. Result
     * will NOT be wrapped into `<p>` tags.
     *
     * @param string $src source string
     * @param null $env environment sandbox
     * @return string
     */
    public function renderInline(string $src, $env=null): string
    {
        $env = is_object($env) ? $env : new stdClass();

        $tokens = $this->parseInline($src, $env);
        return $this->renderer->render($tokens, $this->options, $env);
    }
}
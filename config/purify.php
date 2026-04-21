<?php

use App\Support\TiptapPurifyDefinition;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Config
    |--------------------------------------------------------------------------
    |
    | This option defines the default config that is provided to HTMLPurifier.
    |
    */

    'default' => 'blog',

    /*
    |--------------------------------------------------------------------------
    | Config sets
    |--------------------------------------------------------------------------
    |
    | Here you may configure various sets of configuration for differentiated use of HTMLPurifier.
    | A specific set of configuration can be applied by calling the "config($name)" method on
    | a Purify instance. Feel free to add/remove/customize these attributes as you wish.
    |
    | Documentation: http://htmlpurifier.org/live/configdoc/plain.html
    |
    |   Core.Encoding               The encoding to convert input to.
    |   HTML.Doctype                Doctype to use during filtering.
    |   HTML.Allowed                The allowed HTML Elements with their allowed attributes.
    |   HTML.ForbiddenElements      The forbidden HTML elements. Elements that are listed in this
    |                               string will be removed, however their content will remain.
    |   CSS.AllowedProperties       The Allowed CSS properties.
    |   AutoFormat.AutoParagraph    Newlines are converted in to paragraphs whenever possible.
    |   AutoFormat.RemoveEmpty      Remove empty elements that contribute no semantic information to the document.
    |
    */

    'configs' => [

        'default' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,b,u,strong,i,em,s,del,a[href|title],ul,ol,li,p[style],br,span,img[width|height|alt|src],blockquote',
            'HTML.ForbiddenElements' => '',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ],

        'blog' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h2,h3,h4,p[class|style],b,strong,i,em,u,s,del,sub,sup,a[href|title|rel|target],ul,ol,li,blockquote,img[src|alt|title|width|height|loading|class],figure[class],figcaption,iframe[src|width|height|allowfullscreen|frameborder|allow|title|style|data-aspect-width|data-aspect-height],table,thead,tbody,tr,td,th,code,pre,hr,br,div[class|data-type|data-label|data-youtube-video|data-vimeo-video|data-native-video|style],span[class|style|data-type],details[open],summary,video[src|controls|width|height|poster|autoplay|loop|muted|playsinline]',
            'HTML.DefinitionRev' => 3,
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/|w\.soundcloud\.com/player/)%',
            'CSS.AllowedProperties' => 'text-align,color,background-color,font-weight,font-style,text-decoration,aspect-ratio,width,height,max-width,min-width',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
            'HTML.TargetNoopener' => true,
            'HTML.TargetNoreferrer' => true,
            'HTML.DefinitionID' => 'tiptap-blog',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier definitions
    |--------------------------------------------------------------------------
    |
    | Here you may specify a class that augments the HTML definitions used by
    | HTMLPurifier. Additional HTML5 definitions are provided out of the box.
    | When specifying a custom class, make sure it implements the interface:
    |
    |   \Stevebauman\Purify\Definitions\Definition
    |
    | Note that these definitions are applied to every Purifier instance.
    |
    | Documentation: http://htmlpurifier.org/docs/enduser-customize.html
    |
    */

    'definitions' => TiptapPurifyDefinition::class,

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier CSS definitions
    |--------------------------------------------------------------------------
    |
    | Here you may specify a class that augments the CSS definitions used by
    | HTMLPurifier. When specifying a custom class, make sure it implements
    | the interface:
    |
    |   \Stevebauman\Purify\Definitions\CssDefinition
    |
    | Note that these definitions are applied to every Purifier instance.
    |
    | CSS should be extending $definition->info['css-attribute'] = values
    | See HTMLPurifier_CSSDefinition for further explanation
    |
    */

    'css-definitions' => null,

    /*
    |--------------------------------------------------------------------------
    | Serializer
    |--------------------------------------------------------------------------
    |
    | The storage implementation where HTMLPurifier can store its serializer files.
    | If the filesystem cache is in use, the path must be writable through the
    | storage disk by the web server, otherwise an exception will be thrown.
    |
    */

    'serializer' => [
        'driver' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
        'cache' => \Stevebauman\Purify\Cache\CacheDefinitionCache::class,
    ],

    // 'serializer' => [
    //    'disk' => env('FILESYSTEM_DISK', 'local'),
    //    'path' => 'purify',
    //    'cache' => \Stevebauman\Purify\Cache\FilesystemDefinitionCache::class,
    // ],

];
